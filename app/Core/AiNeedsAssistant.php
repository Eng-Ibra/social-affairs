<?php

namespace App\Core;

use PDO;

/**
 * Rule-based (non-LLM) Needs Assessment analysis engine. Every figure it
 * reports is a direct SQL aggregate over needs_assessments and related
 * tables — nothing is invented. Recommendations are template-generated
 * from those same figures and are clearly labelled as such in the UI,
 * per the "AI must not invent statistics" requirement. This also keeps
 * the feature fully offline / not dependent on any external API.
 */
class AiNeedsAssistant
{
    public static function analyze(PDO $pdo): array
    {
        $mostCommon = $pdo->query(
            "SELECT c.name AS category, COUNT(*) AS reports, SUM(n.number_affected) AS affected
             FROM needs_assessments n JOIN categories c ON c.id = n.need_category_id
             WHERE n.deleted_at IS NULL GROUP BY c.name ORDER BY reports DESC LIMIT 5"
        )->fetchAll();

        $mostUrgent = $pdo->query(
            "SELECT c.name AS category, AVG(n.urgency) AS avg_urgency, AVG(n.severity) AS avg_severity, COUNT(*) AS reports
             FROM needs_assessments n JOIN categories c ON c.id = n.need_category_id
             WHERE n.deleted_at IS NULL GROUP BY c.name
             HAVING avg_urgency >= 3.5 ORDER BY avg_urgency DESC, avg_severity DESC LIMIT 5"
        )->fetchAll();

        $locationRows = $pdo->query(
            "SELECT location_type, location_id, SUM(number_affected) AS affected, COUNT(*) AS reports
             FROM needs_assessments WHERE deleted_at IS NULL
             GROUP BY location_type, location_id ORDER BY affected DESC LIMIT 8"
        )->fetchAll();
        $mostAffectedLocations = [];
        foreach ($locationRows as $r) {
            $label = LocationResolver::resolve($r['location_type'], (int) $r['location_id']);
            $mostAffectedLocations[] = [
                'label' => $label ?? (LocationResolver::typeLabel($r['location_type']) . ' #' . $r['location_id']),
                'type' => LocationResolver::typeLabel($r['location_type']),
                'affected' => (int) $r['affected'],
                'reports' => (int) $r['reports'],
            ];
        }

        $vulnerableGroups = $pdo->query(
            "SELECT vulnerability AS label, COUNT(*) AS total FROM (
                SELECT vulnerability FROM beneficiaries WHERE deleted_at IS NULL AND vulnerability IS NOT NULL AND vulnerability != ''
                UNION ALL
                SELECT vulnerability FROM refugees WHERE deleted_at IS NULL AND vulnerability IS NOT NULL AND vulnerability != ''
             ) t GROUP BY vulnerability ORDER BY total DESC LIMIT 6"
        )->fetchAll();

        $trendCurrent = $pdo->query(
            "SELECT c.name AS category, COUNT(*) AS total FROM needs_assessments n JOIN categories c ON c.id = n.need_category_id
             WHERE n.deleted_at IS NULL AND n.assessment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY c.name"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        $trendPrevious = $pdo->query(
            "SELECT c.name AS category, COUNT(*) AS total FROM needs_assessments n JOIN categories c ON c.id = n.need_category_id
             WHERE n.deleted_at IS NULL AND n.assessment_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND n.assessment_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY c.name"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        $trends = [];
        foreach ($trendCurrent as $cat => $count) {
            $prev = $trendPrevious[$cat] ?? 0;
            $direction = $count > $prev ? 'increasing' : ($count < $prev ? 'decreasing' : 'stable');
            $trends[] = ['category' => $cat, 'current_30d' => (int) $count, 'previous_30d' => (int) $prev, 'direction' => $direction];
        }
        usort($trends, fn ($a, $b) => $b['current_30d'] <=> $a['current_30d']);

        $serviceGaps = $pdo->query(
            "SELECT c.name AS category, n.service_gaps, n.assessment_date
             FROM needs_assessments n JOIN categories c ON c.id = n.need_category_id
             WHERE n.deleted_at IS NULL AND n.service_gaps IS NOT NULL AND n.service_gaps != ''
             ORDER BY n.assessment_date DESC LIMIT 6"
        )->fetchAll();

        $topPriorities = $pdo->query(
            "SELECT p.*, c.name AS category_name, COALESCE(p.override_level, p.computed_level) AS effective_level
             FROM priorities p JOIN categories c ON c.id = p.need_category_id
             ORDER BY p.computed_score DESC LIMIT 3"
        )->fetchAll();

        $interventions = $pdo->query(
            "SELECT c.name AS category, n.recommended_intervention, n.assessment_date
             FROM needs_assessments n JOIN categories c ON c.id = n.need_category_id
             WHERE n.deleted_at IS NULL AND n.recommended_intervention IS NOT NULL AND n.recommended_intervention != ''
             ORDER BY n.assessment_date DESC LIMIT 6"
        )->fetchAll();

        $relevantOrgs = [];
        foreach ($topPriorities as $p) {
            $stmt = $pdo->prepare(
                "SELECT org_name, acronym FROM organizations
                 WHERE deleted_at IS NULL AND status = 'active' AND (sectors LIKE ? OR areas_of_operation LIKE ?)
                 LIMIT 5"
            );
            $like = '%' . $p['category_name'] . '%';
            $stmt->execute([$like, $like]);
            $orgs = $stmt->fetchAll();
            if ($orgs) {
                $relevantOrgs[$p['category_name']] = $orgs;
            }
        }

        $summary = self::buildSummary($topPriorities, $mostCommon, $mostAffectedLocations);

        return [
            'most_common' => $mostCommon,
            'most_urgent' => $mostUrgent,
            'most_affected_locations' => $mostAffectedLocations,
            'vulnerable_groups' => $vulnerableGroups,
            'trends' => array_slice($trends, 0, 6),
            'service_gaps' => $serviceGaps,
            'top_priorities' => $topPriorities,
            'interventions' => $interventions,
            'relevant_organizations' => $relevantOrgs,
            'summary' => $summary,
            'generated_at' => date('Y-m-d H:i'),
        ];
    }

    private static function buildSummary(array $topPriorities, array $mostCommon, array $locations): string
    {
        if (!$topPriorities && !$mostCommon) {
            return 'No needs assessment data has been recorded yet. Once field teams submit assessments, this summary will automatically describe the most pressing needs, based only on recorded figures.';
        }

        $parts = [];
        if ($topPriorities) {
            $top = $topPriorities[0];
            $locCount = (int) $top['locations_count'];
            $affected = (int) $top['total_affected'];
            $parts[] = sprintf(
                '%s is the highest-priority reported need, affecting approximately %s people across %d location%s (priority score %.1f/100, %s priority).',
                $top['category_name'],
                number_format($affected),
                $locCount,
                $locCount === 1 ? '' : 's',
                $top['computed_score'],
                $top['effective_level']
            );
        }
        if (isset($topPriorities[1])) {
            $second = $topPriorities[1];
            $parts[] = sprintf(
                'This is followed by %s, affecting approximately %s people.',
                $second['category_name'],
                number_format((int) $second['total_affected'])
            );
        }
        if ($locations) {
            $topLoc = $locations[0];
            $parts[] = sprintf(
                'The most affected location on record is %s (%s), with %s people reported affected across %d assessment%s.',
                $topLoc['label'],
                $topLoc['type'],
                number_format($topLoc['affected']),
                $topLoc['reports'],
                $topLoc['reports'] === 1 ? '' : 's'
            );
        }
        return implode(' ', $parts);
    }
}
