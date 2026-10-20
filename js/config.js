/**
 * Configuration Globale - Edu Institution
 * Centralise tous les paramètres de connexion
 */

// Configuration de base
const APP_CONFIG = {
    // URLs (toujours pointer vers XAMPP 8080)
    BASE_URL: 'http://localhost:8080/edu-institution/',
    API_BASE: 'http://localhost:8080/edu-institution/backend/',
    
    // Ports
    APACHE_PORT: 8080,
    MYSQL_PORT: 3308,
    
    // Debug
    DEBUG: true,
    LOG_ERRORS: true
};

/**
 * Construit une URL complète pour l'API
 * @param {string} endpoint - Chemin relatif (ex: 'admin_auth.php')
 * @returns {string} URL complète
 */
function buildApiUrl(endpoint) {
    // Remove leading slashes
    const cleanEndpoint = endpoint.replace(/^\/+/, '');
    return APP_CONFIG.API_BASE + cleanEndpoint;
}

/**
 * Construit une URL complète pour une page
 * @param {string} page - Chemin relatif (ex: 'admin.html')
 * @returns {string} URL complète
 */
function buildPageUrl(page) {
    const cleanPage = page.replace(/^\/+/, '');
    return APP_CONFIG.BASE_URL + cleanPage;
}

/**
 * Log debugging information
 */
function logDebug(info) {
    if (APP_CONFIG.DEBUG) {
        console.log('[EduInstitution]', info);
    }
}

// Auto-log configuration on load
console.log('📋 Edu Institution Config loaded:');
console.log('   Base URL:', APP_CONFIG.BASE_URL);
console.log('   API Base:', APP_CONFIG.API_BASE);
console.log('   MySQL Port:', APP_CONFIG.MYSQL_PORT);
