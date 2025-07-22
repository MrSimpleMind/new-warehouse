<?php
/**
 * Controlli di sicurezza per il plugin Warehouse Manager
 * 
 * Gestisce autenticazione, autorizzazione, validazione nonce
 * e protezione dalle principali vulnerabilità.
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inizializza i controlli di sicurezza
 */
function mwm_init_security() {
    // Hook per controllare accesso alle pagine del warehouse
    add_action('template_redirect', 'mwm_check_page_access');
    
    // Hook per validare parametri nelle richieste
    add_action('wp', 'mwm_validate_request_parameters');
    
    // Protezione CSRF per form personalizzati
    add_action('wp_ajax_mwm_verify_nonce', 'mwm_ajax_verify_nonce');
}

// Inizializza sicurezza
mwm_init_security();

/**
 * Controlla accesso alle pagine del warehouse
 */
function mwm_check_page_access() {
    // Lista delle pagine che richiedono autenticazione
    $protected_pages = array(
        'magazzino-tablet',
        'modifica-tablet',
        'esegui-movimento', 
        'cronologia-movimenti',
        'spostamento-di-gruppo',
        'visualizza-tablet'
    );
    
    // Controlla se siamo su una pagina protetta
    if (!is_page() || !mwm_is_warehouse_page($protected_pages)) {
        return;
    }
    
    // Verifica login
    if (!is_user_logged_in()) {
        mwm_redirect_to_login('È richiesto il login per accedere a questa pagina');
        return;
    }
    
    // Verifica permessi amministratore o capacità di modifica tablet
    if (!current_user_can('manage_options') && !current_user_can('edit_tablet')) {
        mwm_show_access_denied_page('Accesso negato. Sono richieste capacità di gestione o modifica tablet.');
        return;
    }
    
    // Log accesso per audit
    mwm_log_user_access();
}

/**
 * Valida parametri delle richieste
 */
function mwm_validate_request_parameters() {
    global $post;
    
    if (!$post || !mwm_is_warehouse_page()) {
        return;
    }
    
    // Validazione per pagina modifica-tablet
    if ($post->post_name === 'modifica-tablet') {
        mwm_validate_tablet_edit_request();
    }
    
    // Validazione per pagina esegui-movimento  
    if ($post->post_name === 'esegui-movimento') {
        mwm_validate_movimento_request();
    }
}

/**
 * Valida richiesta di modifica tablet
 */
function mwm_validate_tablet_edit_request() {
    if (!isset($_GET['post_id'])) {
        mwm_show_error_page('Parametro post_id mancante nell\'URL');
        return;
    }
    
    $tablet_id = intval($_GET['post_id']);
    
    if (!mwm_is_valid_tablet_id($tablet_id)) {
        mwm_show_error_page('ID tablet non valido o tablet non esistente');
        return;
    }
    
    // Verifica che l'utente possa modificare questo tablet
    if (!mwm_user_can_edit_tablet($tablet_id)) {
        mwm_show_access_denied_page('Non hai i permessi per modificare questo tablet');
        return;
    }
}

/**
 * Valida richiesta di movimento
 */
function mwm_validate_movimento_request() {
    if (!isset($_GET['post_id'])) {
        mwm_show_error_page('Parametro post_id mancante nell\'URL');
        return;
    }
    
    $tablet_id = intval($_GET['post_id']);
    
    if (!mwm_is_valid_tablet_id($tablet_id)) {
        mwm_show_error_page('ID tablet non valido o tablet non esistente');
        return;
    }
}

/**
 * Verifica se l'utente può modificare un tablet
 */
function mwm_user_can_edit_tablet($tablet_id) {
    // Verifica capacità standard di WordPress
    return current_user_can('manage_options') || current_user_can('edit_tablet');
}

/**
 * Reindirizza alla pagina di login con messaggio
 */
function mwm_redirect_to_login($message = '') {
    $login_url = wp_login_url($_SERVER['REQUEST_URI']);
    
    if ($message) {
        $login_url = add_query_arg('mwm_message', urlencode($message), $login_url);
    }
    
    wp_redirect($login_url);
    exit;
}

/**
 * Mostra pagina di accesso negato
 */
function mwm_show_access_denied_page($message = '') {
    $default_message = 'Accesso negato. Non hai i permessi necessari per visualizzare questa pagina.';
    $final_message = $message ?: $default_message;
    
    // Header HTTP per accesso negato
    status_header(403);
    
    // Output della pagina di errore
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Accesso Negato - <?php bloginfo('name'); ?></title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: #f1f1f1;
                margin: 0;
                padding: 40px;
            }
            .mwm-error-container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
            }
            .mwm-error-icon {
                font-size: 64px;
                color: #dc3232;
                margin-bottom: 20px;
            }
            .mwm-error-title {
                font-size: 24px;
                color: #23282d;
                margin-bottom: 15px;
            }
            .mwm-error-message {
                color: #666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .mwm-error-actions a {
                display: inline-block;
                padding: 12px 24px;
                background: #0073aa;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin: 0 10px;
            }
            .mwm-error-actions a:hover {
                background: #005a87;
            }
        </style>
    </head>
    <body>
        <div class="mwm-error-container">
            <div class="mwm-error-icon">🚫</div>
            <h1 class="mwm-error-title">Accesso Negato</h1>
            <p class="mwm-error-message"><?php echo esc_html($final_message); ?></p>
            <div class="mwm-error-actions">
                <a href="<?php echo home_url(); ?>">Torna alla Home</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo wp_logout_url(home_url()); ?>">Logout</a>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(); ?>">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Mostra pagina di errore generico
 */
function mwm_show_error_page($message = '') {
    $default_message = 'Si è verificato un errore. Riprova più tardi.';
    $final_message = $message ?: $default_message;
    
    status_header(400);
    
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Errore - <?php bloginfo('name'); ?></title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: #f1f1f1;
                margin: 0;
                padding: 40px;
            }
            .mwm-error-container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
            }
            .mwm-error-icon {
                font-size: 64px;
                color: #ffb900;
                margin-bottom: 20px;
            }
            .mwm-error-title {
                font-size: 24px;
                color: #23282d;
                margin-bottom: 15px;
            }
            .mwm-error-message {
                color: #666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .mwm-error-actions a {
                display: inline-block;
                padding: 12px 24px;
                background: #0073aa;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin: 0 10px;
            }
            .mwm-error-actions a:hover {
                background: #005a87;
            }
        </style>
    </head>
    <body>
        <div class="mwm-error-container">
            <div class="mwm-error-icon">⚠️</div>
            <h1 class="mwm-error-title">Errore</h1>
            <p class="mwm-error-message"><?php echo esc_html($final_message); ?></p>
            <div class="mwm-error-actions">
                <a href="javascript:history.back()">Indietro</a>
                <a href="/magazzino-tablet/">Dashboard</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Logga accesso utente per audit trail
 */
function mwm_log_user_access() {
    if (!WP_DEBUG) {
        return;
    }
    
    $user = wp_get_current_user();
    $page = get_post()->post_name;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_data = array(
        'user_id' => $user->ID,
        'username' => $user->user_login,
        'page' => $page,
        'ip' => $ip,
        'user_agent' => substr($user_agent, 0, 200), // Limita lunghezza
        'timestamp' => current_time('mysql')
    );
    
    mwm_log('User access: ' . json_encode($log_data), 'info');
}

/**
 * Verifica nonce AJAX
 */
function mwm_ajax_verify_nonce() {
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    $action = isset($_POST['action_name']) ? $_POST['action_name'] : 'warehouse_manager_nonce';
    
    if (wp_verify_nonce($nonce, $action)) {
        wp_send_json_success('Nonce valido');
    } else {
        wp_send_json_error('Nonce non valido');
    }
}

/**
 * Sanitizza input dei form per prevenire XSS
 */
function mwm_sanitize_input($input, $type = 'text') {
    switch ($type) {
        case 'html':
            return wp_kses_post($input);
        case 'textarea':
            return sanitize_textarea_field($input);
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'int':
            return intval($input);
        case 'float':
            return floatval($input);
        case 'bool':
            return (bool) $input;
        case 'text':
        default:
            return sanitize_text_field($input);
    }
}

/**
 * Valida token per operazioni sensibili
 */
function mwm_validate_operation_token($token, $operation, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $expected_token = mwm_generate_operation_token($operation, $user_id);
    
    return hash_equals($expected_token, $token);
}

/**
 * Genera token per operazioni sensibili
 */
function mwm_generate_operation_token($operation, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $data = $operation . '|' . $user_id . '|' . date('Y-m-d');
    
    return wp_hash($data, 'nonce');
}

/**
 * Rate limiting semplice per azioni sensibili
 */
function mwm_check_rate_limit($action, $limit = 10, $window = 300) {
    $user_id = get_current_user_id();
    $cache_key = "mwm_rate_limit_{$action}_{$user_id}";
    
    $count = get_transient($cache_key);
    
    if ($count === false) {
        set_transient($cache_key, 1, $window);
        return true;
    }
    
    if ($count >= $limit) {
        mwm_log("Rate limit exceeded for action: {$action}, user: {$user_id}", 'warning');
        return false;
    }
    
    set_transient($cache_key, $count + 1, $window);
    return true;
}

/**
 * Blocca richieste sospette basate su pattern
 */
function mwm_detect_suspicious_activity() {
    // Controlla user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $suspicious_agents = array('bot', 'crawler', 'spider', 'scraper');
    
    foreach ($suspicious_agents as $agent) {
        if (stripos($user_agent, $agent) !== false) {
            mwm_log("Suspicious user agent detected: {$user_agent}", 'warning');
            return true;
        }
    }
    
    // Controlla referer per CSRF
    if (isset($_POST['action']) && !check_ajax_referer('warehouse_manager_nonce', 'nonce', false)) {
        mwm_log("Possible CSRF attack detected", 'warning');
        return true;
    }
    
    return false;
}