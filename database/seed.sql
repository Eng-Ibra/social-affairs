-- =====================================================================
-- Seed data: roles, permissions, default categories, settings, admin
-- =====================================================================
USE social_affairs;

-- ---------------------------------------------------------------------
-- Roles
-- ---------------------------------------------------------------------
INSERT INTO roles (id, name, slug, description, is_system) VALUES
(1, 'Super Admin', 'super_admin', 'Full system access, manages roles, permissions and users', 1),
(2, 'Admin', 'admin', 'Manages most modules, reports and settings', 1),
(3, 'Department Head', 'department_head', 'Views and manages assigned department data', 1),
(4, 'Section/Unit Head', 'section_head', 'Access to their assigned section only', 1),
(5, 'Staff/User', 'staff', 'Access to modules assigned to them', 1),
(6, 'Data Entry Officer', 'data_entry', 'Registers and updates assigned records', 1),
(7, 'Viewer/Report User', 'viewer', 'Read-only access to dashboards and reports', 1);

-- ---------------------------------------------------------------------
-- Modules & capabilities -> permissions
-- ---------------------------------------------------------------------
-- module_key list mirrors app/Modules/*.php registry keys.
INSERT INTO permissions (module_key, capability, label)
SELECT m.module_key, c.capability, CONCAT(m.label, ' - ', c.capability)
FROM (
    SELECT 'users' AS module_key, 'Users' AS label UNION ALL
    SELECT 'roles', 'Roles & Permissions' UNION ALL
    SELECT 'sections', 'Sections' UNION ALL
    SELECT 'camps', 'Camps' UNION ALL
    SELECT 'villages', 'Villages' UNION ALL
    SELECT 'host_communities', 'Host Communities' UNION ALL
    SELECT 'refugees', 'Refugees' UNION ALL
    SELECT 'pwd', 'Persons with Disabilities' UNION ALL
    SELECT 'complaints', 'Complaints' UNION ALL
    SELECT 'organizations', 'Organizations' UNION ALL
    SELECT 'projects', 'Projects' UNION ALL
    SELECT 'activities', 'Activities' UNION ALL
    SELECT 'needs_assessments', 'Needs Assessments' UNION ALL
    SELECT 'priorities', 'Priorities' UNION ALL
    SELECT 'beneficiaries', 'Beneficiaries' UNION ALL
    SELECT 'schools', 'Schools' UNION ALL
    SELECT 'health_facilities', 'Health Facilities' UNION ALL
    SELECT 'events', 'Events' UNION ALL
    SELECT 'calendar', 'Calendar' UNION ALL
    SELECT 'reports', 'Reports' UNION ALL
    SELECT 'audit_logs', 'Audit Logs' UNION ALL
    SELECT 'settings', 'Settings'
) m
CROSS JOIN (
    SELECT 'view' AS capability UNION ALL SELECT 'view_all' UNION ALL SELECT 'create' UNION ALL
    SELECT 'edit' UNION ALL SELECT 'delete' UNION ALL SELECT 'approve' UNION ALL
    SELECT 'export' UNION ALL SELECT 'import' UNION ALL SELECT 'manage'
) c;

-- ---------------------------------------------------------------------
-- Role -> Permission assignment
-- ---------------------------------------------------------------------
-- Super Admin: everything
INSERT INTO role_permissions (role_id, permission_id) SELECT 1, id FROM permissions;

-- Admin: everything except role/permission management ("manage" on roles) - approve allowed if granted
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions
WHERE NOT (module_key = 'roles' AND capability = 'manage');

-- Department Head: view/view_all/create/edit/export on operational modules; no delete, no approve, no roles/settings manage
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions
WHERE module_key NOT IN ('roles','settings','users','audit_logs')
  AND capability IN ('view','view_all','create','edit','export');
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE module_key = 'audit_logs' AND capability = 'view';

-- Section/Unit Head: view/create/edit/export on operational modules (own section scope enforced in app layer)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions
WHERE module_key NOT IN ('roles','settings','users','audit_logs')
  AND capability IN ('view','create','edit','export');

-- Staff/User: sees the shared department registry (view_all), can create/edit, export allowed, no delete
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions
WHERE module_key NOT IN ('roles','settings','users','audit_logs')
  AND capability IN ('view','view_all','create','edit','export');

-- Data Entry Officer: sees the shared registry (view_all), can create/edit/import, no delete
INSERT INTO role_permissions (role_id, permission_id)
SELECT 6, id FROM permissions
WHERE module_key NOT IN ('roles','settings','users','audit_logs','priorities')
  AND capability IN ('view','view_all','create','edit','import','export');

-- Viewer: view + export (reports) only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 7, id FROM permissions
WHERE module_key NOT IN ('roles','settings','users','audit_logs')
  AND capability IN ('view','view_all','export');

-- ---------------------------------------------------------------------
-- Default department & sections
-- ---------------------------------------------------------------------
INSERT INTO departments (id, name, description) VALUES
(1, 'Social Affairs Department', 'Local Government Social Affairs Department');

INSERT INTO sections (section_name, section_code, description, status) VALUES
('IDP/Displacement Affairs', 'SEC-IDP', 'Handles IDP and displacement matters', 'active'),
('Community Affairs', 'SEC-COMM', 'Community engagement and outreach', 'active'),
('Child Protection', 'SEC-CP', 'Child protection services', 'active'),
('Disability & Inclusion', 'SEC-DIS', 'Disability and inclusion services', 'active'),
('Social Protection', 'SEC-SP', 'Social protection programs', 'active'),
('Community Development', 'SEC-CD', 'Community development initiatives', 'active'),
('Coordination', 'SEC-COORD', 'Inter-agency coordination', 'active'),
('Data & Information Management', 'SEC-DATA', 'Data collection and management', 'active');

-- ---------------------------------------------------------------------
-- Categories (need, disability, complaint, sector, org type, facility type, event type)
-- ---------------------------------------------------------------------
INSERT INTO categories (type, name) VALUES
('need','Food'), ('need','Water'), ('need','WASH'), ('need','Shelter'), ('need','Health'),
('need','Education'), ('need','Protection'), ('need','Livelihood'), ('need','NFIs'),
('need','Disability Services'), ('need','Child Protection'), ('need','Sanitation'),
('need','Electricity/Lighting'), ('need','Emergency Assistance'), ('need','Legal Assistance'), ('need','Other'),
('disability','Physical'), ('disability','Visual'), ('disability','Hearing'), ('disability','Intellectual'),
('disability','Speech'), ('disability','Multiple'), ('disability','Psychosocial'),
('complaint','Service Delivery'), ('complaint','Protection Concern'), ('complaint','Corruption/Fraud'),
('complaint','Discrimination'), ('complaint','Land/Shelter Dispute'), ('complaint','Aid Distribution'), ('complaint','Other'),
('sector','Food Security'), ('sector','WASH'), ('sector','Health'), ('sector','Education'),
('sector','Protection'), ('sector','Shelter/NFI'), ('sector','Livelihoods'), ('sector','Nutrition'),
('organization_type','UN Agency'), ('organization_type','International NGO'), ('organization_type','National NGO'),
('organization_type','Government'), ('organization_type','Community-Based Organization'), ('organization_type','Donor'),
('facility_type','Primary Health Unit'), ('facility_type','Health Center'), ('facility_type','Hospital'), ('facility_type','Mobile Clinic'),
('event_type','Training'), ('event_type','Awareness Campaign'), ('event_type','Coordination Meeting'), ('event_type','Distribution'), ('event_type','Community Dialogue');

-- ---------------------------------------------------------------------
-- Settings
-- ---------------------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
('system_name', 'Social Affairs Management System'),
('organization_name', 'Local Government Social Affairs Department'),
('district_name', 'District Headquarters'),
('contact_email', 'info@socialaffairs.gov'),
('contact_phone', ''),
('logo_path', ''),
('theme_default', 'light'),
('project_deadline_warning_days', '7'),
('activity_deadline_warning_days', '2'),
('max_login_attempts', '5'),
('login_lockout_minutes', '15');

-- ---------------------------------------------------------------------
-- First Super Admin (password: ChangeMe#2026 — MUST be changed after first login)
-- Hash generated with PHP password_hash('ChangeMe#2026', PASSWORD_BCRYPT)
-- ---------------------------------------------------------------------
INSERT INTO users (name, email, phone, password_hash, department_id, position, role_id, status, approved_by, approved_at)
VALUES ('System Administrator', 'admin@socialaffairs.gov', '0000000000',
        '$2y$12$nqlPXovLAPzP7Crp6S06BOQFjubXvG6M0OQYgHTF/0.6VWG5sSqpK',
        1, 'System Administrator', 1, 'approved', NULL, NOW());
