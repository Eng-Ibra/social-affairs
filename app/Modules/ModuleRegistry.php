<?php

namespace App\Modules;

/**
 * Metadata-driven module definitions that power the generic CRUD engine
 * (list/create/edit/view/delete/export/import) in CrudController.
 *
 * Field types: text, textarea, number, decimal, date, select, relation, file, boolean
 */
class ModuleRegistry
{
    public static function all(): array
    {
        static $modules = null;
        if ($modules !== null) {
            return $modules;
        }
        return $modules = [
            'sections' => self::sections(),
            'camps' => self::camps(),
            'villages' => self::villages(),
            'host_communities' => self::hostCommunities(),
            'refugees' => self::refugees(),
            'pwd' => self::pwd(),
            'organizations' => self::organizations(),
            'projects' => self::projects(),
            'activities' => self::activities(),
            'needs_assessments' => self::needsAssessments(),
            'beneficiaries' => self::beneficiaries(),
            'complaints' => self::complaints(),
            'schools' => self::schools(),
            'health_facilities' => self::healthFacilities(),
            'events' => self::events(),
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    private static function common(): array
    {
        return [
            'soft_deletes' => true,
            'default_sort' => 'created_at DESC',
        ];
    }

    private static function sections(): array
    {
        return array_merge(self::common(), [
            'key' => 'sections', 'table' => 'sections', 'label' => 'Sections', 'label_singular' => 'Section',
            'icon' => 'fa-sitemap', 'title_field' => 'section_name', 'code_field' => 'section_code', 'code_prefix' => 'SEC',
            'fields' => [
                ['name' => 'section_name', 'label' => 'Section Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'section_code', 'label' => 'Section Code', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'section_head', 'label' => 'Section Head', 'type' => 'text', 'list' => true],
                ['name' => 'deputy_assistant', 'label' => 'Deputy/Assistant', 'type' => 'text'],
                ['name' => 'staff_members', 'label' => 'Staff Members', 'type' => 'textarea'],
                ['name' => 'contact_info', 'label' => 'Contact Information', 'type' => 'text'],
                ['name' => 'responsibilities', 'label' => 'Responsibilities', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'list' => true, 'filterable' => true, 'default' => 'active'],
            ],
        ]);
    }

    private static function camps(): array
    {
        return array_merge(self::common(), [
            'key' => 'camps', 'table' => 'camps', 'label' => 'Camps', 'label_singular' => 'Camp',
            'icon' => 'fa-campground', 'title_field' => 'camp_name', 'code_field' => 'camp_code', 'code_prefix' => 'CMP',
            'has_map' => true,
            'fields' => [
                ['name' => 'camp_code', 'label' => 'Camp ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'camp_name', 'label' => 'Camp Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'searchable' => true, 'list' => true],
                ['name' => 'area_neighborhood', 'label' => 'Area/Neighborhood', 'type' => 'text'],
                ['name' => 'gps_lat', 'label' => 'GPS Latitude', 'type' => 'decimal'],
                ['name' => 'gps_lng', 'label' => 'GPS Longitude', 'type' => 'decimal'],
                ['name' => 'coordinator_name', 'label' => 'Camp Coordinator', 'type' => 'text'],
                ['name' => 'committee_info', 'label' => 'Camp Committee', 'type' => 'textarea'],
                ['name' => 'contact_phone', 'label' => 'Contact Phone', 'type' => 'text', 'sensitive' => true],
                ['name' => 'households', 'label' => 'Number of Households', 'type' => 'number', 'default' => 0],
                ['name' => 'population', 'label' => 'Population', 'type' => 'number', 'list' => true, 'default' => 0],
                ['name' => 'male_count', 'label' => 'Male', 'type' => 'number', 'default' => 0],
                ['name' => 'female_count', 'label' => 'Female', 'type' => 'number', 'default' => 0],
                ['name' => 'children_count', 'label' => 'Children', 'type' => 'number', 'default' => 0],
                ['name' => 'elderly_count', 'label' => 'Elderly', 'type' => 'number', 'default' => 0],
                ['name' => 'pwd_count', 'label' => 'Persons with Disabilities', 'type' => 'number', 'default' => 0],
                ['name' => 'vulnerable_groups', 'label' => 'Vulnerable Groups', 'type' => 'textarea'],
                ['name' => 'main_needs', 'label' => 'Main Needs', 'type' => 'textarea'],
                ['name' => 'service_providers', 'label' => 'Service Providers', 'type' => 'textarea'],
                ['name' => 'organizations_working', 'label' => 'Organizations Working in Camp', 'type' => 'textarea'],
                ['name' => 'land_ownership_status', 'label' => 'Land Ownership/Status', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'closed' => 'Closed', 'planned' => 'Planned'], 'list' => true, 'filterable' => true, 'default' => 'active'],
                ['name' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date', 'list' => true, 'default' => 'today'],
            ],
        ]);
    }

    private static function villages(): array
    {
        return array_merge(self::common(), [
            'key' => 'villages', 'table' => 'villages', 'label' => 'Villages', 'label_singular' => 'Village',
            'icon' => 'fa-house-chimney', 'title_field' => 'village_name', 'code_field' => 'village_code', 'code_prefix' => 'VLG',
            'has_map' => true,
            'fields' => [
                ['name' => 'village_code', 'label' => 'Village ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'village_name', 'label' => 'Village Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'searchable' => true, 'list' => true],
                ['name' => 'gps_lat', 'label' => 'GPS Latitude', 'type' => 'decimal'],
                ['name' => 'gps_lng', 'label' => 'GPS Longitude', 'type' => 'decimal'],
                ['name' => 'population', 'label' => 'Population', 'type' => 'number', 'list' => true, 'default' => 0],
                ['name' => 'households', 'label' => 'Households', 'type' => 'number', 'default' => 0],
                ['name' => 'idp_households', 'label' => 'IDP Households', 'type' => 'number', 'default' => 0],
                ['name' => 'host_households', 'label' => 'Host Community Households', 'type' => 'number', 'default' => 0],
                ['name' => 'vulnerable_households', 'label' => 'Vulnerable Households', 'type' => 'number', 'default' => 0],
                ['name' => 'main_needs', 'label' => 'Main Needs', 'type' => 'textarea'],
                ['name' => 'village_leader', 'label' => 'Village Leader', 'type' => 'text', 'list' => true],
                ['name' => 'contact_phone', 'label' => 'Contact', 'type' => 'text', 'sensitive' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'list' => true, 'filterable' => true, 'default' => 'active'],
                ['name' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date', 'default' => 'today'],
            ],
        ]);
    }

    private static function hostCommunities(): array
    {
        return array_merge(self::common(), [
            'key' => 'host_communities', 'table' => 'host_communities', 'label' => 'Host Communities', 'label_singular' => 'Host Community Neighborhood',
            'icon' => 'fa-people-group', 'title_field' => 'neighborhood_name', 'code_field' => 'neighborhood_code', 'code_prefix' => 'HC',
            'has_map' => true,
            'fields' => [
                ['name' => 'neighborhood_code', 'label' => 'Neighborhood ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'neighborhood_name', 'label' => 'Neighborhood Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'village_id', 'label' => 'Parent Village', 'type' => 'relation', 'relation_table' => 'villages', 'relation_display' => 'village_name', 'list' => true, 'filterable' => true],
                ['name' => 'chairman_name', 'label' => 'Chairman/Gudoomiye', 'type' => 'text', 'list' => true],
                ['name' => 'chairman_phone', 'label' => 'Chairman Phone', 'type' => 'text', 'sensitive' => true],
                ['name' => 'deputy_chairman_name', 'label' => 'Deputy Chairman', 'type' => 'text'],
                ['name' => 'deputy_chairman_phone', 'label' => 'Deputy Phone', 'type' => 'text', 'sensitive' => true],
                ['name' => 'secretary_name', 'label' => 'Secretary/Xoghayaha', 'type' => 'text'],
                ['name' => 'secretary_phone', 'label' => 'Secretary Phone', 'type' => 'text', 'sensitive' => true],
                ['name' => 'households', 'label' => 'Households', 'type' => 'number', 'default' => 0],
                ['name' => 'population', 'label' => 'Population', 'type' => 'number', 'list' => true, 'default' => 0],
                ['name' => 'male_count', 'label' => 'Male', 'type' => 'number', 'default' => 0],
                ['name' => 'female_count', 'label' => 'Female', 'type' => 'number', 'default' => 0],
                ['name' => 'children_count', 'label' => 'Children', 'type' => 'number', 'default' => 0],
                ['name' => 'elderly_count', 'label' => 'Elderly', 'type' => 'number', 'default' => 0],
                ['name' => 'pwd_count', 'label' => 'Persons with Disabilities', 'type' => 'number', 'default' => 0],
                ['name' => 'main_needs', 'label' => 'Main Needs', 'type' => 'textarea'],
                ['name' => 'gps_lat', 'label' => 'GPS Latitude', 'type' => 'decimal'],
                ['name' => 'gps_lng', 'label' => 'GPS Longitude', 'type' => 'decimal'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'list' => true, 'filterable' => true, 'default' => 'active'],
                ['name' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date', 'default' => 'today'],
            ],
        ]);
    }

    private static function refugees(): array
    {
        return array_merge(self::common(), [
            'key' => 'refugees', 'table' => 'refugees', 'label' => 'Refugees', 'label_singular' => 'Refugee',
            'icon' => 'fa-person-walking-luggage', 'title_field' => 'full_name', 'code_field' => 'refugee_code', 'code_prefix' => 'REF',
            'fields' => [
                ['name' => 'refugee_code', 'label' => 'Refugee ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'full_name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['male' => 'Male', 'female' => 'Female'], 'required' => true, 'list' => true, 'filterable' => true],
                ['name' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'sensitive' => true],
                ['name' => 'nationality', 'label' => 'Nationality', 'type' => 'text', 'list' => true, 'filterable' => true],
                ['name' => 'household_size', 'label' => 'Household Size', 'type' => 'number', 'default' => 1],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'sensitive' => true],
                ['name' => 'village_id', 'label' => 'Village/Area', 'type' => 'relation', 'relation_table' => 'villages', 'relation_display' => 'village_name', 'filterable' => true],
                ['name' => 'camp_id', 'label' => 'Camp (if applicable)', 'type' => 'relation', 'relation_table' => 'camps', 'relation_display' => 'camp_name', 'filterable' => true],
                ['name' => 'vulnerability', 'label' => 'Vulnerability', 'type' => 'text'],
                ['name' => 'disability_status', 'label' => 'Disability Status', 'type' => 'select', 'options' => ['none' => 'None', 'has_disability' => 'Has Disability'], 'default' => 'none'],
                ['name' => 'needs', 'label' => 'Needs', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'relocated' => 'Relocated', 'departed' => 'Departed', 'inactive' => 'Inactive'], 'list' => true, 'filterable' => true, 'default' => 'active'],
                ['name' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date', 'list' => true, 'default' => 'today'],
            ],
        ]);
    }

    private static function pwd(): array
    {
        return array_merge(self::common(), [
            'key' => 'pwd', 'table' => 'persons_with_disabilities', 'label' => 'Persons with Disabilities', 'label_singular' => 'Person with Disability',
            'icon' => 'fa-wheelchair', 'title_field' => 'full_name', 'code_field' => 'pwd_code', 'code_prefix' => 'PWD',
            'fields' => [
                ['name' => 'pwd_code', 'label' => 'Person ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'full_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['male' => 'Male', 'female' => 'Female'], 'required' => true, 'list' => true, 'filterable' => true],
                ['name' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'sensitive' => true],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'sensitive' => true],
                ['name' => 'village_id', 'label' => 'Village', 'type' => 'relation', 'relation_table' => 'villages', 'relation_display' => 'village_name', 'filterable' => true],
                ['name' => 'camp_id', 'label' => 'Camp', 'type' => 'relation', 'relation_table' => 'camps', 'relation_display' => 'camp_name', 'filterable' => true],
                ['name' => 'host_community_id', 'label' => 'Neighborhood', 'type' => 'relation', 'relation_table' => 'host_communities', 'relation_display' => 'neighborhood_name', 'filterable' => true],
                ['name' => 'disability_category_id', 'label' => 'Disability Type', 'type' => 'relation', 'relation_table' => 'categories', 'relation_display' => 'name', 'relation_where' => "type='disability'", 'list' => true, 'filterable' => true, 'required' => true],
                ['name' => 'severity', 'label' => 'Severity/Category', 'type' => 'select', 'options' => ['mild' => 'Mild', 'moderate' => 'Moderate', 'severe' => 'Severe'], 'list' => true, 'filterable' => true, 'default' => 'moderate'],
                ['name' => 'assistive_device_required', 'label' => 'Assistive Device Required', 'type' => 'text'],
                ['name' => 'services_received', 'label' => 'Services Received', 'type' => 'textarea'],
                ['name' => 'organization_id', 'label' => 'Supporting Organization', 'type' => 'relation', 'relation_table' => 'organizations', 'relation_display' => 'org_name', 'filterable' => true],
                ['name' => 'main_needs', 'label' => 'Main Needs', 'type' => 'textarea'],
                ['name' => 'referral_status', 'label' => 'Referral Status', 'type' => 'select', 'options' => ['none' => 'None', 'referred' => 'Referred', 'in_service' => 'In Service', 'completed' => 'Completed'], 'list' => true, 'filterable' => true, 'default' => 'none'],
                ['name' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date', 'default' => 'today'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
        ]);
    }

    private static function organizations(): array
    {
        return array_merge(self::common(), [
            'key' => 'organizations', 'table' => 'organizations', 'label' => 'Organizations', 'label_singular' => 'Organization',
            'icon' => 'fa-building-shield', 'title_field' => 'org_name', 'code_field' => 'org_code', 'code_prefix' => 'ORG',
            'fields' => [
                ['name' => 'org_code', 'label' => 'Organization ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'org_name', 'label' => 'Organization Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'acronym', 'label' => 'Acronym', 'type' => 'text', 'list' => true],
                ['name' => 'org_type_id', 'label' => 'Organization Type', 'type' => 'relation', 'relation_table' => 'categories', 'relation_display' => 'name', 'relation_where' => "type='organization_type'", 'list' => true, 'filterable' => true],
                ['name' => 'contact_person', 'label' => 'Contact Person', 'type' => 'text'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'text'],
                ['name' => 'office_address', 'label' => 'Office Address', 'type' => 'text'],
                ['name' => 'areas_of_operation', 'label' => 'Areas of Operation', 'type' => 'textarea'],
                ['name' => 'sectors', 'label' => 'Sectors', 'type' => 'textarea'],
                ['name' => 'current_projects', 'label' => 'Current Projects', 'type' => 'textarea'],
                ['name' => 'target_locations', 'label' => 'Target Locations', 'type' => 'textarea'],
                ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'End Date', 'type' => 'date'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'closed' => 'Closed'], 'list' => true, 'filterable' => true, 'default' => 'active'],
                ['name' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date', 'default' => 'today'],
            ],
        ]);
    }

    private static function projects(): array
    {
        return array_merge(self::common(), [
            'key' => 'projects', 'table' => 'projects', 'label' => 'Projects', 'label_singular' => 'Project',
            'icon' => 'fa-diagram-project', 'title_field' => 'project_name', 'code_field' => 'project_code', 'code_prefix' => 'PRJ',
            'fields' => [
                ['name' => 'project_code', 'label' => 'Project ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'organization_id', 'label' => 'Organization', 'type' => 'relation', 'relation_table' => 'organizations', 'relation_display' => 'org_name', 'list' => true, 'filterable' => true],
                ['name' => 'donor', 'label' => 'Donor', 'type' => 'text'],
                ['name' => 'sector_id', 'label' => 'Sector', 'type' => 'relation', 'relation_table' => 'categories', 'relation_display' => 'name', 'relation_where' => "type='sector'", 'list' => true, 'filterable' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'location_type', 'label' => 'Location Type', 'type' => 'select', 'options' => ['camp' => 'Camp', 'village' => 'Village', 'host_community' => 'Host Community', 'citywide' => 'Citywide']],
                ['name' => 'location_id', 'label' => 'Location', 'type' => 'polymorphic_location'],
                ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => true, 'list' => true],
                ['name' => 'end_date', 'label' => 'End Date', 'type' => 'date', 'required' => true, 'list' => true],
                ['name' => 'budget', 'label' => 'Budget', 'type' => 'decimal', 'default' => 0],
                ['name' => 'target_beneficiaries', 'label' => 'Target Beneficiaries', 'type' => 'number', 'default' => 0],
                ['name' => 'actual_beneficiaries', 'label' => 'Actual Beneficiaries', 'type' => 'number', 'default' => 0],
                ['name' => 'project_manager_id', 'label' => 'Project Manager', 'type' => 'relation', 'relation_table' => 'users', 'relation_display' => 'name'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['upcoming' => 'Upcoming', 'active' => 'Active', 'near_deadline' => 'Near Deadline', 'completed' => 'Completed', 'overdue' => 'Overdue'], 'readonly' => true, 'list' => true, 'filterable' => true],
                ['name' => 'progress_percent', 'label' => 'Progress %', 'type' => 'decimal', 'readonly' => true, 'list' => true],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
                ['name' => 'attachment_path', 'label' => 'Attachment', 'type' => 'file'],
            ],
        ]);
    }

    private static function activities(): array
    {
        return array_merge(self::common(), [
            'key' => 'activities', 'table' => 'activities', 'label' => 'Activities', 'label_singular' => 'Activity',
            'icon' => 'fa-list-check', 'title_field' => 'activity_title', 'code_field' => 'activity_code', 'code_prefix' => 'ACT',
            'fields' => [
                ['name' => 'activity_code', 'label' => 'Activity ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'activity_title', 'label' => 'Activity Title', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'project_id', 'label' => 'Project', 'type' => 'relation', 'relation_table' => 'projects', 'relation_display' => 'project_name', 'list' => true, 'filterable' => true],
                ['name' => 'organization_id', 'label' => 'Organization', 'type' => 'relation', 'relation_table' => 'organizations', 'relation_display' => 'org_name', 'filterable' => true],
                ['name' => 'responsible_person_id', 'label' => 'Responsible Person', 'type' => 'relation', 'relation_table' => 'users', 'relation_display' => 'name', 'list' => true],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text'],
                ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => true],
                ['name' => 'due_date', 'label' => 'Due Date', 'type' => 'date', 'required' => true, 'list' => true],
                ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'], 'list' => true, 'filterable' => true, 'default' => 'medium'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'overdue' => 'Overdue'], 'readonly' => true, 'list' => true, 'filterable' => true],
                ['name' => 'completion_percent', 'label' => 'Completion %', 'type' => 'decimal', 'list' => true, 'default' => 0],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
                ['name' => 'attachment_path', 'label' => 'Attachment', 'type' => 'file'],
            ],
        ]);
    }

    private static function needsAssessments(): array
    {
        return array_merge(self::common(), [
            'key' => 'needs_assessments', 'table' => 'needs_assessments', 'label' => 'Needs Assessments', 'label_singular' => 'Needs Assessment',
            'icon' => 'fa-clipboard-question', 'title_field' => 'assessment_code', 'code_field' => 'assessment_code', 'code_prefix' => 'NA',
            'fields' => [
                ['name' => 'assessment_code', 'label' => 'Assessment ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'location_type', 'label' => 'Population Group Type', 'type' => 'select', 'required' => true, 'list' => true, 'filterable' => true, 'options' => [
                    'camp' => 'Camp', 'village' => 'Village', 'host_community' => 'Host Community',
                    'household' => 'Household', 'beneficiary' => 'Beneficiary', 'pwd' => 'Person with Disability', 'refugee' => 'Refugee',
                ]],
                ['name' => 'location_id', 'label' => 'Specific Location', 'type' => 'polymorphic_location', 'required' => true],
                ['name' => 'population_group', 'label' => 'Population Group', 'type' => 'text'],
                ['name' => 'assessor_id', 'label' => 'Assessor', 'type' => 'relation', 'relation_table' => 'users', 'relation_display' => 'name'],
                ['name' => 'assessment_date', 'label' => 'Assessment Date', 'type' => 'date', 'required' => true, 'list' => true, 'default' => 'today'],
                ['name' => 'need_category_id', 'label' => 'Need Category', 'type' => 'relation', 'relation_table' => 'categories', 'relation_display' => 'name', 'relation_where' => "type='need'", 'required' => true, 'list' => true, 'filterable' => true],
                ['name' => 'need_description', 'label' => 'Need Description', 'type' => 'textarea'],
                ['name' => 'number_affected', 'label' => 'Number Affected', 'type' => 'number', 'required' => true, 'list' => true, 'default' => 0],
                ['name' => 'severity', 'label' => 'Severity (1-5)', 'type' => 'select', 'options' => ['1' => '1 - Very Low', '2' => '2 - Low', '3' => '3 - Moderate', '4' => '4 - High', '5' => '5 - Critical'], 'list' => true, 'default' => '3'],
                ['name' => 'urgency', 'label' => 'Urgency (1-5)', 'type' => 'select', 'options' => ['1' => '1 - Very Low', '2' => '2 - Low', '3' => '3 - Moderate', '4' => '4 - High', '5' => '5 - Critical'], 'list' => true, 'default' => '3'],
                ['name' => 'existing_services', 'label' => 'Existing Services', 'type' => 'textarea'],
                ['name' => 'service_gaps', 'label' => 'Service Gaps', 'type' => 'textarea'],
                ['name' => 'recommended_intervention', 'label' => 'Recommended Intervention', 'type' => 'textarea'],
                ['name' => 'responsible_organization_id', 'label' => 'Organization Responsible', 'type' => 'relation', 'relation_table' => 'organizations', 'relation_display' => 'org_name', 'filterable' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['reported' => 'Reported', 'under_review' => 'Under Review', 'addressed' => 'Addressed', 'closed' => 'Closed'], 'list' => true, 'filterable' => true, 'default' => 'reported'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
        ]);
    }

    private static function beneficiaries(): array
    {
        return array_merge(self::common(), [
            'key' => 'beneficiaries', 'table' => 'beneficiaries', 'label' => 'Beneficiaries', 'label_singular' => 'Beneficiary',
            'icon' => 'fa-hand-holding-heart', 'title_field' => 'full_name', 'code_field' => 'beneficiary_code', 'code_prefix' => 'BEN',
            'fields' => [
                ['name' => 'beneficiary_code', 'label' => 'Beneficiary ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'full_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['male' => 'Male', 'female' => 'Female'], 'required' => true, 'list' => true, 'filterable' => true],
                ['name' => 'age', 'label' => 'Age', 'type' => 'number'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'sensitive' => true, 'searchable' => true],
                ['name' => 'household_size', 'label' => 'Household Size', 'type' => 'number', 'default' => 1],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'sensitive' => true],
                ['name' => 'camp_id', 'label' => 'Camp', 'type' => 'relation', 'relation_table' => 'camps', 'relation_display' => 'camp_name', 'filterable' => true],
                ['name' => 'village_id', 'label' => 'Village', 'type' => 'relation', 'relation_table' => 'villages', 'relation_display' => 'village_name', 'filterable' => true],
                ['name' => 'host_community_id', 'label' => 'Host Community', 'type' => 'relation', 'relation_table' => 'host_communities', 'relation_display' => 'neighborhood_name', 'filterable' => true],
                ['name' => 'vulnerability', 'label' => 'Vulnerability', 'type' => 'text', 'list' => true],
                ['name' => 'disability_status', 'label' => 'Disability', 'type' => 'select', 'options' => ['none' => 'None', 'has_disability' => 'Has Disability'], 'default' => 'none'],
                ['name' => 'needs', 'label' => 'Needs', 'type' => 'textarea'],
                ['name' => 'assistance_received', 'label' => 'Assistance Received', 'type' => 'textarea'],
                ['name' => 'organization_id', 'label' => 'Organization', 'type' => 'relation', 'relation_table' => 'organizations', 'relation_display' => 'org_name', 'filterable' => true],
                ['name' => 'project_id', 'label' => 'Project', 'type' => 'relation', 'relation_table' => 'projects', 'relation_display' => 'project_name', 'filterable' => true],
                ['name' => 'activity_id', 'label' => 'Activity', 'type' => 'relation', 'relation_table' => 'activities', 'relation_display' => 'activity_title'],
                ['name' => 'service_date', 'label' => 'Date', 'type' => 'date', 'list' => true, 'default' => 'today'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'filterable' => true, 'default' => 'active'],
            ],
        ]);
    }

    private static function complaints(): array
    {
        return array_merge(self::common(), [
            'key' => 'complaints', 'table' => 'complaints', 'label' => 'Complaints', 'label_singular' => 'Complaint',
            'icon' => 'fa-comment-dots', 'title_field' => 'complainant_name', 'code_field' => 'complaint_code', 'code_prefix' => 'CMP-C',
            'fields' => [
                ['name' => 'complaint_code', 'label' => 'Complaint ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'complainant_name', 'label' => 'Complainant Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'sensitive' => true],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'sensitive' => true],
                ['name' => 'location_type', 'label' => 'Location Type', 'type' => 'select', 'options' => ['camp' => 'Camp', 'village' => 'Village', 'host_community' => 'Host Community']],
                ['name' => 'location_id', 'label' => 'Location', 'type' => 'polymorphic_location'],
                ['name' => 'category_id', 'label' => 'Complaint Category', 'type' => 'relation', 'relation_table' => 'categories', 'relation_display' => 'name', 'relation_where' => "type='complaint'", 'list' => true, 'filterable' => true],
                ['name' => 'description', 'label' => 'Complaint Description', 'type' => 'textarea', 'required' => true],
                ['name' => 'date_received', 'label' => 'Date Received', 'type' => 'date', 'required' => true, 'list' => true, 'default' => 'today'],
                ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'], 'list' => true, 'filterable' => true, 'default' => 'medium'],
                ['name' => 'assigned_officer_id', 'label' => 'Assigned Officer', 'type' => 'relation', 'relation_table' => 'users', 'relation_display' => 'name', 'list' => true, 'filterable' => true],
                ['name' => 'responsible_organization_id', 'label' => 'Responsible Organization', 'type' => 'relation', 'relation_table' => 'organizations', 'relation_display' => 'org_name'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['new' => 'New', 'pending' => 'Pending', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed', 'referred' => 'Referred'], 'list' => true, 'filterable' => true, 'default' => 'new'],
                ['name' => 'resolution', 'label' => 'Resolution', 'type' => 'textarea'],
                ['name' => 'follow_up_date', 'label' => 'Follow-up Date', 'type' => 'date'],
                ['name' => 'closing_date', 'label' => 'Closing Date', 'type' => 'date'],
                ['name' => 'attachment_path', 'label' => 'Attachment', 'type' => 'file'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
        ]);
    }

    private static function schools(): array
    {
        return array_merge(self::common(), [
            'key' => 'schools', 'table' => 'schools', 'label' => 'Schools', 'label_singular' => 'School',
            'icon' => 'fa-school', 'title_field' => 'school_name', 'code_field' => 'school_code', 'code_prefix' => 'SCH',
            'has_map' => true,
            'fields' => [
                ['name' => 'school_code', 'label' => 'School ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'school_name', 'label' => 'School Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'village_id', 'label' => 'Village', 'type' => 'relation', 'relation_table' => 'villages', 'relation_display' => 'village_name', 'filterable' => true],
                ['name' => 'host_community_id', 'label' => 'Neighborhood', 'type' => 'relation', 'relation_table' => 'host_communities', 'relation_display' => 'neighborhood_name', 'filterable' => true],
                ['name' => 'gps_lat', 'label' => 'GPS Latitude', 'type' => 'decimal'],
                ['name' => 'gps_lng', 'label' => 'GPS Longitude', 'type' => 'decimal'],
                ['name' => 'principal_name', 'label' => 'Principal', 'type' => 'text', 'list' => true],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                ['name' => 'students_male', 'label' => 'Male Students', 'type' => 'number', 'default' => 0],
                ['name' => 'students_female', 'label' => 'Female Students', 'type' => 'number', 'default' => 0],
                ['name' => 'teachers_count', 'label' => 'Teachers', 'type' => 'number', 'default' => 0],
                ['name' => 'class_levels', 'label' => 'Class Levels', 'type' => 'text'],
                ['name' => 'facilities', 'label' => 'Facilities', 'type' => 'textarea'],
                ['name' => 'water_availability', 'label' => 'Water Availability', 'type' => 'select', 'options' => ['yes' => 'Yes', 'no' => 'No', 'partial' => 'Partial'], 'default' => 'no'],
                ['name' => 'sanitation', 'label' => 'Sanitation', 'type' => 'select', 'options' => ['yes' => 'Yes', 'no' => 'No', 'partial' => 'Partial'], 'default' => 'no'],
                ['name' => 'electricity', 'label' => 'Electricity', 'type' => 'select', 'options' => ['yes' => 'Yes', 'no' => 'No', 'partial' => 'Partial'], 'default' => 'no'],
                ['name' => 'main_needs', 'label' => 'Main Needs', 'type' => 'textarea'],
                ['name' => 'supporting_organization_id', 'label' => 'Supporting Organization', 'type' => 'relation', 'relation_table' => 'organizations', 'relation_display' => 'org_name'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'list' => true, 'filterable' => true, 'default' => 'active'],
                ['name' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date', 'default' => 'today'],
            ],
        ]);
    }

    private static function healthFacilities(): array
    {
        return array_merge(self::common(), [
            'key' => 'health_facilities', 'table' => 'health_facilities', 'label' => 'Health Facilities', 'label_singular' => 'Health Facility',
            'icon' => 'fa-hospital', 'title_field' => 'facility_name', 'code_field' => 'facility_code', 'code_prefix' => 'HF',
            'has_map' => true,
            'fields' => [
                ['name' => 'facility_code', 'label' => 'Facility ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'facility_name', 'label' => 'Facility Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'facility_type_id', 'label' => 'Type', 'type' => 'relation', 'relation_table' => 'categories', 'relation_display' => 'name', 'relation_where' => "type='facility_type'", 'list' => true, 'filterable' => true],
                ['name' => 'village_id', 'label' => 'Village', 'type' => 'relation', 'relation_table' => 'villages', 'relation_display' => 'village_name', 'filterable' => true],
                ['name' => 'host_community_id', 'label' => 'Neighborhood', 'type' => 'relation', 'relation_table' => 'host_communities', 'relation_display' => 'neighborhood_name', 'filterable' => true],
                ['name' => 'gps_lat', 'label' => 'GPS Latitude', 'type' => 'decimal'],
                ['name' => 'gps_lng', 'label' => 'GPS Longitude', 'type' => 'decimal'],
                ['name' => 'manager_name', 'label' => 'Manager', 'type' => 'text', 'list' => true],
                ['name' => 'contact_phone', 'label' => 'Contact', 'type' => 'text'],
                ['name' => 'staff_count', 'label' => 'Staff', 'type' => 'number', 'default' => 0],
                ['name' => 'services', 'label' => 'Services', 'type' => 'textarea'],
                ['name' => 'mch_availability', 'label' => 'MCH Availability', 'type' => 'select', 'options' => ['yes' => 'Yes', 'no' => 'No'], 'default' => 'no'],
                ['name' => 'medicine_availability', 'label' => 'Medicine Availability', 'type' => 'select', 'options' => ['yes' => 'Yes', 'no' => 'No', 'partial' => 'Partial'], 'default' => 'no'],
                ['name' => 'water_availability', 'label' => 'Water', 'type' => 'select', 'options' => ['yes' => 'Yes', 'no' => 'No', 'partial' => 'Partial'], 'default' => 'no'],
                ['name' => 'electricity', 'label' => 'Electricity', 'type' => 'select', 'options' => ['yes' => 'Yes', 'no' => 'No', 'partial' => 'Partial'], 'default' => 'no'],
                ['name' => 'equipment', 'label' => 'Equipment', 'type' => 'textarea'],
                ['name' => 'main_needs', 'label' => 'Main Needs', 'type' => 'textarea'],
                ['name' => 'supporting_organization_id', 'label' => 'Supporting Organization', 'type' => 'relation', 'relation_table' => 'organizations', 'relation_display' => 'org_name'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['functional' => 'Functional', 'non_functional' => 'Non-Functional', 'partial' => 'Partial'], 'list' => true, 'filterable' => true, 'default' => 'functional'],
                ['name' => 'registration_date', 'label' => 'Registration Date', 'type' => 'date', 'default' => 'today'],
            ],
        ]);
    }

    private static function events(): array
    {
        return array_merge(self::common(), [
            'key' => 'events', 'table' => 'events', 'label' => 'Events', 'label_singular' => 'Event',
            'icon' => 'fa-calendar-days', 'title_field' => 'event_name', 'code_field' => 'event_code', 'code_prefix' => 'EVT',
            'fields' => [
                ['name' => 'event_code', 'label' => 'Event ID', 'type' => 'text', 'list' => true, 'auto' => true],
                ['name' => 'event_name', 'label' => 'Event Name', 'type' => 'text', 'required' => true, 'list' => true, 'searchable' => true],
                ['name' => 'event_type_id', 'label' => 'Event Type', 'type' => 'relation', 'relation_table' => 'categories', 'relation_display' => 'name', 'relation_where' => "type='event_type'", 'list' => true, 'filterable' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text'],
                ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => true, 'list' => true],
                ['name' => 'end_date', 'label' => 'End Date', 'type' => 'date', 'required' => true, 'list' => true],
                ['name' => 'organizer', 'label' => 'Organizer', 'type' => 'text'],
                ['name' => 'participants_count', 'label' => 'Participants', 'type' => 'number', 'default' => 0],
                ['name' => 'target_group', 'label' => 'Target Group', 'type' => 'text'],
                ['name' => 'section_id', 'label' => 'Responsible Section', 'type' => 'relation', 'relation_table' => 'sections', 'relation_display' => 'section_name', 'filterable' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'], 'list' => true, 'filterable' => true, 'default' => 'upcoming'],
                ['name' => 'attachment_path', 'label' => 'Attachment', 'type' => 'file'],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
            ],
        ]);
    }
}
