-- =====================================================================
-- Social Affairs Management System — Database Schema
-- Local Government Social Affairs Department
-- Engine: MySQL 8+ / MariaDB 10.11+  |  Charset: utf8mb4
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS social_affairs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE social_affairs;

-- =====================================================================
-- 1. RBAC: ROLES, PERMISSIONS, USERS
-- =====================================================================

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0, -- system roles cannot be deleted
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- One row per (module, capability) that exists in the system.
CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_key VARCHAR(60) NOT NULL,
    capability ENUM('view','view_all','create','edit','delete','approve','export','import','manage') NOT NULL,
    label VARCHAR(150) NOT NULL,
    UNIQUE KEY uniq_module_capability (module_key, capability)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NULL,
    section_name VARCHAR(150) NOT NULL,
    section_code VARCHAR(30) NOT NULL UNIQUE,
    description TEXT NULL,
    section_head VARCHAR(150) NULL,
    deputy_assistant VARCHAR(150) NULL,
    staff_members TEXT NULL,
    contact_info VARCHAR(150) NULL,
    responsibilities TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_sections_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    department_id INT UNSIGNED NULL,
    section_id INT UNSIGNED NULL,
    position VARCHAR(120) NULL,
    role_id INT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
    approved_by INT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason VARCHAR(255) NULL,
    failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_users_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL,
    CONSTRAINT fk_users_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE sections ADD CONSTRAINT fk_sections_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE sections ADD CONSTRAINT fk_sections_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pwreset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_email_time (email, created_at),
    INDEX idx_login_attempts_ip_time (ip_address, created_at)
) ENGINE=InnoDB;

-- =====================================================================
-- 2. LOOKUP CATEGORIES (configurable from Settings)
-- =====================================================================
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('need','disability','complaint','sector','organization_type','facility_type','event_type','vulnerability') NOT NULL,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(40) NULL,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_type_name (type, name)
) ENGINE=InnoDB;

CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 3. GEOGRAPHIC / STRUCTURAL ENTITIES
-- =====================================================================

CREATE TABLE villages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    village_code VARCHAR(30) NOT NULL UNIQUE,
    village_name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NULL,
    gps_lat DECIMAL(10,7) NULL,
    gps_lng DECIMAL(10,7) NULL,
    population INT UNSIGNED NULL DEFAULT 0,
    households INT UNSIGNED NULL DEFAULT 0,
    idp_households INT UNSIGNED NULL DEFAULT 0,
    host_households INT UNSIGNED NULL DEFAULT 0,
    vulnerable_households INT UNSIGNED NULL DEFAULT 0,
    main_needs TEXT NULL,
    village_leader VARCHAR(150) NULL,
    contact_phone VARCHAR(30) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    registration_date DATE NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_villages_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_villages_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE host_communities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    neighborhood_code VARCHAR(30) NOT NULL UNIQUE,
    neighborhood_name VARCHAR(150) NOT NULL,
    village_id INT UNSIGNED NULL,
    chairman_name VARCHAR(150) NULL,
    chairman_phone VARCHAR(30) NULL,
    deputy_chairman_name VARCHAR(150) NULL,
    deputy_chairman_phone VARCHAR(30) NULL,
    secretary_name VARCHAR(150) NULL,
    secretary_phone VARCHAR(30) NULL,
    households INT UNSIGNED NULL DEFAULT 0,
    population INT UNSIGNED NULL DEFAULT 0,
    male_count INT UNSIGNED NULL DEFAULT 0,
    female_count INT UNSIGNED NULL DEFAULT 0,
    children_count INT UNSIGNED NULL DEFAULT 0,
    elderly_count INT UNSIGNED NULL DEFAULT 0,
    pwd_count INT UNSIGNED NULL DEFAULT 0,
    main_needs TEXT NULL,
    gps_lat DECIMAL(10,7) NULL,
    gps_lng DECIMAL(10,7) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    registration_date DATE NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_hc_village FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE SET NULL,
    CONSTRAINT fk_hc_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_hc_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE camps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    camp_code VARCHAR(30) NOT NULL UNIQUE,
    camp_name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NULL,
    area_neighborhood VARCHAR(150) NULL,
    gps_lat DECIMAL(10,7) NULL,
    gps_lng DECIMAL(10,7) NULL,
    coordinator_name VARCHAR(150) NULL,
    committee_info TEXT NULL,
    contact_phone VARCHAR(30) NULL,
    households INT UNSIGNED NULL DEFAULT 0,
    population INT UNSIGNED NULL DEFAULT 0,
    male_count INT UNSIGNED NULL DEFAULT 0,
    female_count INT UNSIGNED NULL DEFAULT 0,
    children_count INT UNSIGNED NULL DEFAULT 0,
    elderly_count INT UNSIGNED NULL DEFAULT 0,
    pwd_count INT UNSIGNED NULL DEFAULT 0,
    vulnerable_groups TEXT NULL,
    main_needs TEXT NULL,
    service_providers TEXT NULL,
    organizations_working TEXT NULL,
    land_ownership_status VARCHAR(150) NULL,
    status ENUM('active','closed','planned') NOT NULL DEFAULT 'active',
    registration_date DATE NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_camps_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_camps_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 4. PEOPLE: REFUGEES, PWD, BENEFICIARIES
-- =====================================================================

CREATE TABLE refugees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    refugee_code VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    gender ENUM('male','female') NOT NULL,
    date_of_birth DATE NULL,
    phone VARCHAR(30) NULL,
    nationality VARCHAR(80) NULL,
    household_size INT UNSIGNED NULL DEFAULT 1,
    address VARCHAR(255) NULL,
    village_id INT UNSIGNED NULL,
    camp_id INT UNSIGNED NULL,
    vulnerability VARCHAR(150) NULL,
    disability_status ENUM('none','has_disability') NOT NULL DEFAULT 'none',
    needs TEXT NULL,
    status ENUM('active','relocated','departed','inactive') NOT NULL DEFAULT 'active',
    registration_date DATE NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_refugees_village FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE SET NULL,
    CONSTRAINT fk_refugees_camp FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE SET NULL,
    CONSTRAINT fk_refugees_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_refugees_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE persons_with_disabilities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pwd_code VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    gender ENUM('male','female') NOT NULL,
    date_of_birth DATE NULL,
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    village_id INT UNSIGNED NULL,
    camp_id INT UNSIGNED NULL,
    host_community_id INT UNSIGNED NULL,
    disability_category_id INT UNSIGNED NULL,
    severity ENUM('mild','moderate','severe') NOT NULL DEFAULT 'moderate',
    assistive_device_required VARCHAR(150) NULL,
    services_received TEXT NULL,
    organization_id INT UNSIGNED NULL,
    main_needs TEXT NULL,
    referral_status ENUM('none','referred','in_service','completed') NOT NULL DEFAULT 'none',
    registration_date DATE NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_pwd_village FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE SET NULL,
    CONSTRAINT fk_pwd_camp FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE SET NULL,
    CONSTRAINT fk_pwd_host_community FOREIGN KEY (host_community_id) REFERENCES host_communities(id) ON DELETE SET NULL,
    CONSTRAINT fk_pwd_category FOREIGN KEY (disability_category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_pwd_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_pwd_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 5. ORGANIZATIONS / PROJECTS / ACTIVITIES
-- =====================================================================

CREATE TABLE organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_code VARCHAR(30) NOT NULL UNIQUE,
    org_name VARCHAR(180) NOT NULL,
    acronym VARCHAR(30) NULL,
    org_type_id INT UNSIGNED NULL,
    contact_person VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    office_address VARCHAR(255) NULL,
    areas_of_operation TEXT NULL,
    sectors TEXT NULL,
    current_projects TEXT NULL,
    target_locations TEXT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    status ENUM('active','inactive','closed') NOT NULL DEFAULT 'active',
    registration_date DATE NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_org_type FOREIGN KEY (org_type_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_org_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_org_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_code VARCHAR(30) NOT NULL UNIQUE,
    project_name VARCHAR(180) NOT NULL,
    organization_id INT UNSIGNED NULL,
    donor VARCHAR(150) NULL,
    sector_id INT UNSIGNED NULL,
    description TEXT NULL,
    location_type ENUM('camp','village','host_community','citywide') NULL,
    location_id INT UNSIGNED NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    budget DECIMAL(14,2) NULL DEFAULT 0,
    target_beneficiaries INT UNSIGNED NULL DEFAULT 0,
    actual_beneficiaries INT UNSIGNED NULL DEFAULT 0,
    project_manager_id INT UNSIGNED NULL,
    status ENUM('upcoming','active','near_deadline','completed','overdue') NOT NULL DEFAULT 'upcoming',
    progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    deadline_notified TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_projects_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_sector FOREIGN KEY (sector_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_manager FOREIGN KEY (project_manager_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_code VARCHAR(30) NOT NULL UNIQUE,
    activity_title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    project_id INT UNSIGNED NULL,
    organization_id INT UNSIGNED NULL,
    responsible_person_id INT UNSIGNED NULL,
    location VARCHAR(255) NULL,
    start_date DATE NOT NULL,
    due_date DATE NOT NULL,
    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status ENUM('pending','in_progress','completed','overdue') NOT NULL DEFAULT 'pending',
    completion_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    deadline_notified TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_activities_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    CONSTRAINT fk_activities_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    CONSTRAINT fk_activities_responsible FOREIGN KEY (responsible_person_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_activities_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_activities_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 6. NEEDS ASSESSMENT & PRIORITY ENGINE
-- =====================================================================

CREATE TABLE needs_assessments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_code VARCHAR(30) NOT NULL UNIQUE,
    location_type ENUM('camp','village','host_community','household','beneficiary','pwd','refugee') NOT NULL,
    location_id INT UNSIGNED NOT NULL,
    population_group VARCHAR(120) NULL,
    assessor_id INT UNSIGNED NULL,
    assessment_date DATE NOT NULL,
    need_category_id INT UNSIGNED NOT NULL,
    need_description TEXT NULL,
    number_affected INT UNSIGNED NOT NULL DEFAULT 0,
    severity TINYINT UNSIGNED NOT NULL DEFAULT 3, -- 1..5
    urgency TINYINT UNSIGNED NOT NULL DEFAULT 3,  -- 1..5
    existing_services TEXT NULL,
    service_gaps TEXT NULL,
    recommended_intervention TEXT NULL,
    responsible_organization_id INT UNSIGNED NULL,
    status ENUM('reported','under_review','addressed','closed') NOT NULL DEFAULT 'reported',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_needs_category FOREIGN KEY (need_category_id) REFERENCES categories(id),
    CONSTRAINT fk_needs_assessor FOREIGN KEY (assessor_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_needs_org FOREIGN KEY (responsible_organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    CONSTRAINT fk_needs_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_needs_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_needs_location (location_type, location_id),
    INDEX idx_needs_category (need_category_id)
) ENGINE=InnoDB;

-- Aggregated & auto-computed priority per (need_category, location); can be manually overridden with audit trail.
CREATE TABLE priorities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    need_category_id INT UNSIGNED NOT NULL,
    location_type ENUM('camp','village','host_community','household','beneficiary','pwd','refugee','all') NOT NULL DEFAULT 'all',
    location_id INT UNSIGNED NULL,
    total_affected INT UNSIGNED NOT NULL DEFAULT 0,
    avg_severity DECIMAL(4,2) NOT NULL DEFAULT 0,
    avg_urgency DECIMAL(4,2) NOT NULL DEFAULT 0,
    locations_count INT UNSIGNED NOT NULL DEFAULT 0,
    reports_count INT UNSIGNED NOT NULL DEFAULT 0,
    computed_score DECIMAL(8,2) NOT NULL DEFAULT 0,
    computed_level ENUM('high','medium','low') NOT NULL DEFAULT 'low',
    override_level ENUM('high','medium','low') NULL,
    override_by INT UNSIGNED NULL,
    override_reason VARCHAR(255) NULL,
    override_at TIMESTAMP NULL,
    calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_priorities_category FOREIGN KEY (need_category_id) REFERENCES categories(id),
    CONSTRAINT fk_priorities_override_by FOREIGN KEY (override_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_priority_scope (need_category_id, location_type, location_id)
) ENGINE=InnoDB;

CREATE TABLE priority_overrides_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    priority_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    old_level VARCHAR(20) NULL,
    new_level VARCHAR(20) NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pol_priority FOREIGN KEY (priority_id) REFERENCES priorities(id) ON DELETE CASCADE,
    CONSTRAINT fk_pol_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- =====================================================================
-- 7. BENEFICIARIES
-- =====================================================================

CREATE TABLE beneficiaries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    beneficiary_code VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    gender ENUM('male','female') NOT NULL,
    age INT UNSIGNED NULL,
    phone VARCHAR(30) NULL,
    household_size INT UNSIGNED NULL DEFAULT 1,
    address VARCHAR(255) NULL,
    camp_id INT UNSIGNED NULL,
    village_id INT UNSIGNED NULL,
    host_community_id INT UNSIGNED NULL,
    vulnerability VARCHAR(150) NULL,
    disability_status ENUM('none','has_disability') NOT NULL DEFAULT 'none',
    needs TEXT NULL,
    assistance_received TEXT NULL,
    organization_id INT UNSIGNED NULL,
    project_id INT UNSIGNED NULL,
    activity_id INT UNSIGNED NULL,
    service_date DATE NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    duplicate_flag TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_beneficiaries_camp FOREIGN KEY (camp_id) REFERENCES camps(id) ON DELETE SET NULL,
    CONSTRAINT fk_beneficiaries_village FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE SET NULL,
    CONSTRAINT fk_beneficiaries_host_community FOREIGN KEY (host_community_id) REFERENCES host_communities(id) ON DELETE SET NULL,
    CONSTRAINT fk_beneficiaries_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    CONSTRAINT fk_beneficiaries_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    CONSTRAINT fk_beneficiaries_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE SET NULL,
    CONSTRAINT fk_beneficiaries_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_beneficiaries_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_beneficiaries_name_phone (full_name, phone)
) ENGINE=InnoDB;

-- =====================================================================
-- 8. COMPLAINTS
-- =====================================================================

CREATE TABLE complaints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_code VARCHAR(30) NOT NULL UNIQUE,
    complainant_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    location_type ENUM('camp','village','host_community') NULL,
    location_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NULL,
    description TEXT NOT NULL,
    date_received DATE NOT NULL,
    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    assigned_officer_id INT UNSIGNED NULL,
    responsible_organization_id INT UNSIGNED NULL,
    status ENUM('new','pending','in_progress','resolved','closed','referred') NOT NULL DEFAULT 'new',
    resolution TEXT NULL,
    follow_up_date DATE NULL,
    closing_date DATE NULL,
    attachment_path VARCHAR(255) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_complaints_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_complaints_officer FOREIGN KEY (assigned_officer_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_complaints_org FOREIGN KEY (responsible_organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    CONSTRAINT fk_complaints_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_complaints_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 9. SCHOOLS / HEALTH FACILITIES / EVENTS
-- =====================================================================

CREATE TABLE schools (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_code VARCHAR(30) NOT NULL UNIQUE,
    school_name VARCHAR(180) NOT NULL,
    village_id INT UNSIGNED NULL,
    host_community_id INT UNSIGNED NULL,
    gps_lat DECIMAL(10,7) NULL,
    gps_lng DECIMAL(10,7) NULL,
    principal_name VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    students_male INT UNSIGNED NULL DEFAULT 0,
    students_female INT UNSIGNED NULL DEFAULT 0,
    teachers_count INT UNSIGNED NULL DEFAULT 0,
    class_levels VARCHAR(150) NULL,
    facilities TEXT NULL,
    water_availability ENUM('yes','no','partial') NOT NULL DEFAULT 'no',
    sanitation ENUM('yes','no','partial') NOT NULL DEFAULT 'no',
    electricity ENUM('yes','no','partial') NOT NULL DEFAULT 'no',
    main_needs TEXT NULL,
    supporting_organization_id INT UNSIGNED NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    registration_date DATE NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_schools_village FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE SET NULL,
    CONSTRAINT fk_schools_host_community FOREIGN KEY (host_community_id) REFERENCES host_communities(id) ON DELETE SET NULL,
    CONSTRAINT fk_schools_org FOREIGN KEY (supporting_organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    CONSTRAINT fk_schools_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_schools_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE health_facilities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    facility_code VARCHAR(30) NOT NULL UNIQUE,
    facility_name VARCHAR(180) NOT NULL,
    facility_type_id INT UNSIGNED NULL,
    village_id INT UNSIGNED NULL,
    host_community_id INT UNSIGNED NULL,
    gps_lat DECIMAL(10,7) NULL,
    gps_lng DECIMAL(10,7) NULL,
    manager_name VARCHAR(150) NULL,
    contact_phone VARCHAR(30) NULL,
    staff_count INT UNSIGNED NULL DEFAULT 0,
    services TEXT NULL,
    mch_availability ENUM('yes','no') NOT NULL DEFAULT 'no',
    medicine_availability ENUM('yes','no','partial') NOT NULL DEFAULT 'no',
    water_availability ENUM('yes','no','partial') NOT NULL DEFAULT 'no',
    electricity ENUM('yes','no','partial') NOT NULL DEFAULT 'no',
    equipment TEXT NULL,
    main_needs TEXT NULL,
    supporting_organization_id INT UNSIGNED NULL,
    status ENUM('functional','non_functional','partial') NOT NULL DEFAULT 'functional',
    registration_date DATE NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_hf_type FOREIGN KEY (facility_type_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_hf_village FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE SET NULL,
    CONSTRAINT fk_hf_host_community FOREIGN KEY (host_community_id) REFERENCES host_communities(id) ON DELETE SET NULL,
    CONSTRAINT fk_hf_org FOREIGN KEY (supporting_organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    CONSTRAINT fk_hf_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_hf_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_code VARCHAR(30) NOT NULL UNIQUE,
    event_name VARCHAR(180) NOT NULL,
    event_type_id INT UNSIGNED NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    organizer VARCHAR(150) NULL,
    participants_count INT UNSIGNED NULL DEFAULT 0,
    target_group VARCHAR(150) NULL,
    section_id INT UNSIGNED NULL,
    status ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    attachment_path VARCHAR(255) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_events_type FOREIGN KEY (event_type_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_events_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL,
    CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_events_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Standalone calendar entries (meetings/reminders) not tied to other modules.
CREATE TABLE calendar_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    item_date DATE NOT NULL,
    end_date DATE NULL,
    item_type ENUM('meeting','reminder','follow_up','other') NOT NULL DEFAULT 'meeting',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calendar_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 10. AUDIT LOG / NOTIFICATIONS / IMPORT HISTORY
-- =====================================================================

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(40) NOT NULL, -- created, updated, deleted, approved, rejected, exported, imported, restored, login, logout, login_failed
    module VARCHAR(60) NOT NULL,
    record_id INT UNSIGNED NULL,
    description VARCHAR(255) NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_module_record (module, record_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL, -- NULL = broadcast to role_target
    role_target INT UNSIGNED NULL, -- notify all users of this role
    title VARCHAR(180) NOT NULL,
    message VARCHAR(500) NOT NULL,
    type VARCHAR(40) NOT NULL DEFAULT 'info',
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_role FOREIGN KEY (role_target) REFERENCES roles(id) ON DELETE CASCADE,
    INDEX idx_notifications_user_read (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE import_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(60) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    success_count INT UNSIGNED NOT NULL DEFAULT 0,
    error_count INT UNSIGNED NOT NULL DEFAULT 0,
    errors_json JSON NULL,
    imported_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_import_user FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
