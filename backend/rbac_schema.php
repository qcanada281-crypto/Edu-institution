<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * RBAC Database Schema Setup
 * Creates users, roles, and permissions tables for role-based access control
 */

function ensure_rbac_tables(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        // Create roles table
        db_query(
            "CREATE TABLE IF NOT EXISTS roles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                display_name VARCHAR(100) NOT NULL,
                description TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Create permissions table
        db_query(
            "CREATE TABLE IF NOT EXISTS permissions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                display_name VARCHAR(150) NOT NULL,
                description TEXT NULL,
                module VARCHAR(50) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Create role_permissions table (many-to-many)
        db_query(
            "CREATE TABLE IF NOT EXISTS role_permissions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                role_id INT UNSIGNED NOT NULL,
                permission_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_role_permission (role_id, permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Seed default roles
        seed_default_roles();
        
        // Seed default permissions
        seed_default_permissions();
        
        // Assign permissions to roles
        seed_role_permissions();

    } catch (Throwable $exception) {
        error_log('RBAC tables migration error: ' . $exception->getMessage());
    }
}

function seed_default_roles(): void
{
    $roles = [
        ['name' => 'director', 'display_name' => 'المدير', 'description' => 'مدير المؤسسة - صلاحيات كاملة'],
        ['name' => 'secretary', 'display_name' => 'السكرتارية', 'description' => 'السكرتارية - صلاحيات محدودة']
    ];

    foreach ($roles as $role) {
        $existing = db_query(
            'SELECT id FROM roles WHERE name = :name LIMIT 1',
            ['name' => $role['name']]
        )->fetch();

        if (!$existing) {
            db_query(
                'INSERT INTO roles (name, display_name, description) VALUES (:name, :display_name, :description)',
                $role
            );
        }
    }
}

function seed_default_permissions(): void
{
    $permissions = [
        // Student permissions
        ['name' => 'students.create', 'display_name' => 'إضافة تلاميذ', 'description' => 'إضافة تلاميذ جدد', 'module' => 'students'],
        ['name' => 'students.edit', 'display_name' => 'تعديل التلاميذ', 'description' => 'تعديل بيانات التلاميذ', 'module' => 'students'],
        ['name' => 'students.delete', 'display_name' => 'حذف التلاميذ', 'description' => 'حذف بيانات التلاميذ', 'module' => 'students'],
        ['name' => 'students.view', 'display_name' => 'عرض التلاميذ', 'description' => 'عرض قائمة التلاميذ', 'module' => 'students'],
        
        // Teacher permissions
        ['name' => 'teachers.create', 'display_name' => 'إضافة أساتذة', 'description' => 'إضافة أساتذة جدد', 'module' => 'teachers'],
        ['name' => 'teachers.edit', 'display_name' => 'تعديل الأساتذة', 'description' => 'تعديل بيانات الأساتذة', 'module' => 'teachers'],
        ['name' => 'teachers.delete', 'display_name' => 'حذف الأساتذة', 'description' => 'حذف بيانات الأساتذة', 'module' => 'teachers'],
        ['name' => 'teachers.view', 'display_name' => 'عرض الأساتذة', 'description' => 'عرض قائمة الأساتذة', 'module' => 'teachers'],
        
        // Class permissions
        ['name' => 'classes.create', 'display_name' => 'إشاء أقسام', 'description' => 'إنشاء أقسام جديدة', 'module' => 'classes'],
        ['name' => 'classes.edit', 'display_name' => 'تعديل الأقسام', 'description' => 'تعديل بيانات الأقسام', 'module' => 'classes'],
        ['name' => 'classes.delete', 'display_name' => 'حذف الأقسام', 'description' => 'حذف الأقسام', 'module' => 'classes'],
        ['name' => 'classes.view', 'display_name' => 'عرض الأقسام', 'description' => 'عرض قائمة الأقسام', 'module' => 'classes'],
        
        // Grade permissions
        ['name' => 'grades.create', 'display_name' => 'إضافة نقط', 'description' => 'إضافة نقط التلاميذ', 'module' => 'grades'],
        ['name' => 'grades.edit', 'display_name' => 'تعديل النقط', 'description' => 'تعديل النقط المسجلة', 'module' => 'grades'],
        ['name' => 'grades.delete', 'display_name' => 'حذف النقط', 'description' => 'حذف النقط المسجلة', 'module' => 'grades'],
        ['name' => 'grades.view', 'display_name' => 'عرض النقط', 'description' => 'عرض النقط المسجلة', 'module' => 'grades'],
        
        // Attendance permissions
        ['name' => 'attendance.create', 'display_name' => 'إضافة غياب', 'description' => 'تسجيل غياب التلاميذ', 'module' => 'attendance'],
        ['name' => 'attendance.edit', 'display_name' => 'تعديل الغياب', 'description' => 'تعديل سجلات الغياب', 'module' => 'attendance'],
        ['name' => 'attendance.delete', 'display_name' => 'حذف الغياب', 'description' => 'حذف سجلات الغياب', 'module' => 'attendance'],
        ['name' => 'attendance.view', 'display_name' => 'عرض الغياب', 'description' => 'عرض سجلات الغياب', 'module' => 'attendance'],
        
        // Registration permissions
        ['name' => 'registrations.approve', 'display_name' => 'الموافقة على التسجيلات', 'description' => 'الموافقة على طلبات التسجيل', 'module' => 'registrations'],
        ['name' => 'registrations.view', 'display_name' => 'عرض التسجيلات', 'description' => 'عرض طلبات التسجيل', 'module' => 'registrations'],
        
        // Settings permissions
        ['name' => 'settings.manage', 'display_name' => 'إدارة الإعدادات', 'description' => 'إدارة إعدادات النظام', 'module' => 'settings'],
        
        // Account permissions
        ['name' => 'accounts.create', 'display_name' => 'إنشاء حسابات', 'description' => 'إنشاء حسابات جديدة', 'module' => 'accounts'],
        ['name' => 'accounts.manage', 'display_name' => 'إدارة الحسابات', 'description' => 'إدارة جميع الحسابات', 'module' => 'accounts'],
        
        // Reports permissions
        ['name' => 'reports.view', 'display_name' => 'عرض التقارير', 'description' => 'عرض جميع التقارير والإحصائيات', 'module' => 'reports'],
        
        // Lessons permissions
        ['name' => 'lessons.create', 'display_name' => 'إضافة دروس', 'description' => 'إضافة دروس جديدة', 'module' => 'lessons'],
        ['name' => 'lessons.edit', 'display_name' => 'تعديل الدروس', 'description' => 'تعديل الدروس المسجلة', 'module' => 'lessons'],
        ['name' => 'lessons.delete', 'display_name' => 'حذف الدروس', 'description' => 'حذف الدروس المسجلة', 'module' => 'lessons'],
        ['name' => 'lessons.view', 'display_name' => 'عرض الدروس', 'description' => 'عرض الدروس المسجلة', 'module' => 'lessons'],
        
        // Messages permissions
        ['name' => 'messages.view', 'display_name' => 'عرض الرسائل', 'description' => 'عرض رسائل التواصل', 'module' => 'messages'],
        ['name' => 'messages.manage', 'display_name' => 'إدارة الرسائل', 'description' => 'إدارة رسائل التواصل', 'module' => 'messages']
    ];

    foreach ($permissions as $permission) {
        $existing = db_query(
            'SELECT id FROM permissions WHERE name = :name LIMIT 1',
            ['name' => $permission['name']]
        )->fetch();

        if (!$existing) {
            db_query(
                'INSERT INTO permissions (name, display_name, description, module) VALUES (:name, :display_name, :description, :module)',
                $permission
            );
        }
    }
}

function seed_role_permissions(): void
{
    // Get role IDs
    $directorRole = db_query('SELECT id FROM roles WHERE name = "director" LIMIT 1')->fetch();
    $secretaryRole = db_query('SELECT id FROM roles WHERE name = "secretary" LIMIT 1')->fetch();

    if (!$directorRole || !$secretaryRole) {
        return;
    }

    // Director gets ALL permissions
    $allPermissions = db_query('SELECT id FROM permissions')->fetchAll();
    foreach ($allPermissions as $permission) {
        $existing = db_query(
            'SELECT id FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id LIMIT 1',
            ['role_id' => $directorRole['id'], 'permission_id' => $permission['id']]
        )->fetch();

        if (!$existing) {
            db_query(
                'INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)',
                ['role_id' => $directorRole['id'], 'permission_id' => $permission['id']]
            );
        }
    }

    // Secretary gets LIMITED permissions
    $secretaryPermissions = [
        'students.create', 'students.view',
        'teachers.create', 'teachers.view',
        'classes.create', 'classes.view',
        'grades.create', 'grades.view',
        'attendance.create', 'attendance.view',
        'lessons.create', 'lessons.view',
        'messages.view'
    ];

    foreach ($secretaryPermissions as $permName) {
        $permission = db_query('SELECT id FROM permissions WHERE name = :name LIMIT 1', ['name' => $permName])->fetch();
        
        if ($permission) {
            $existing = db_query(
                'SELECT id FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id LIMIT 1',
                ['role_id' => $secretaryRole['id'], 'permission_id' => $permission['id']]
            )->fetch();

            if (!$existing) {
                db_query(
                    'INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)',
                    ['role_id' => $secretaryRole['id'], 'permission_id' => $permission['id']]
                );
            }
        }
    }
}

// Initialize RBAC tables
ensure_rbac_tables();
