<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rbac_schema.php';

/**
 * Backwards-compatible helper: require_admin_access
 * Accepts optional array of allowed roles. Returns the current role string.
 */
if (!function_exists('require_admin_access')) {
    function require_admin_access(array $allowedRoles = null): string
    {
        // Accept either admin_* session keys or user_* session keys
        $loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
        $role = $_SESSION['admin_role'] ?? $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;

        if (!$loggedIn || $userId <= 0 || $role === '') {
            json_response(false, 'يجب تسجيل الدخول للوصول إلى هذه الصفحة.', [], 403);
        }

        $normalized = strtolower(trim((string) $role));
        if ($allowedRoles === null) {
            $allowedRoles = ['director', 'secretary'];
        }
        $allowed = array_map('strtolower', $allowedRoles);
        if (!in_array($normalized, $allowed, true)) {
            json_response(false, 'ليس لديك صلاحية للوصول إلى هذه المورد.', [], 403);
        }

        return $normalized;
    }
}

/**
 * RBAC Middleware for Role-Based Access Control
 */

/**
 * Check if current user has a specific permission
 */
function has_permission(string $permission): bool
{
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role'])) {
        return false;
    }

    $role = $_SESSION['admin_role'];
    
    // Director has all permissions
    if ($role === 'director') {
        return true;
    }

    // Check database for role permission
    try {
        $result = db_query(
            "SELECT COUNT(*) as has_perm
             FROM role_permissions rp
             JOIN roles r ON rp.role_id = r.id
             JOIN permissions p ON rp.permission_id = p.id
             WHERE r.name = :role AND p.name = :permission",
            ['role' => $role, 'permission' => $permission]
        )->fetch();

        return (int) ($result['has_perm'] ?? 0) > 0;
    } catch (Throwable $e) {
        error_log('Permission check error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Require a specific permission, deny access if not granted
 */
function require_permission(string $permission): void
{
    if (!has_permission($permission)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access Denied: You do not have permission to perform this action.',
            'required_permission' => $permission
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Check if current user has any of the specified permissions
 */
function has_any_permission(array $permissions): bool
{
    foreach ($permissions as $permission) {
        if (has_permission($permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Require any of the specified permissions
 */
function require_any_permission(array $permissions): void
{
    if (!has_any_permission($permissions)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access Denied: You do not have permission to perform this action.',
            'required_permissions' => $permissions
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Get all permissions for the current user's role
 */
function get_user_permissions(): array
{
    if (!isset($_SESSION['admin_role'])) {
        return [];
    }

    $role = $_SESSION['admin_role'];

    try {
        $permissions = db_query(
            "SELECT p.name, p.display_name, p.module
             FROM role_permissions rp
             JOIN roles r ON rp.role_id = r.id
             JOIN permissions p ON rp.permission_id = p.id
             WHERE r.name = :role
             ORDER BY p.module, p.display_name",
            ['role' => $role]
        )->fetchAll();

        return $permissions;
    } catch (Throwable $e) {
        error_log('Get user permissions error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Check if current user is Director
 */
function is_director(): bool
{
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'director';
}

/**
 * Check if current user is Secretary
 */
function is_secretary(): bool
{
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'secretary';
}

/**
 * Require Director role
 */
function require_director(): void
{
    if (!is_director()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access Denied: This action is restricted to Directors only.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Get current user role
 */
function get_current_role(): ?string
{
    return $_SESSION['admin_role'] ?? null;
}

/**
 * Get current admin user info
 */
function get_current_admin_user(): ?array
{
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'] ?? '',
        'email' => $_SESSION['admin_email'] ?? '',
        'role' => $_SESSION['admin_role'] ?? ''
    ];
}
