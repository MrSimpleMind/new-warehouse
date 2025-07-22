<?php
/**
 * Funzioni helper per il plugin Warehouse Manager
 * 
 * Contiene funzioni utility riutilizzabili in tutto il plugin
 * per formattazione, validazione e operazioni comuni.
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verifica se l'utente corrente può accedere alle funzionalità del warehouse
 */
function mwm_user_can_access_warehouse() {
    return current_user_can('administrator');
}

/**
 * Genera messaggio di errore per accesso negato
 */
function mwm_access_denied_message($custom_message = '') {
    $default_message = 'Accesso negato. È richiesto il login come amministratore per accedere a questa funzionalità.';
    $message = $custom_message ?: $default_message;
    
    return sprintf(
        '<div class="mwm-error" style="color: red; font-weight: bold; padding: 15px; background: #ffe6e6; border: 1px solid #ff9999; border-radius: 4px; margin: 15px 0;">%s</div>',
        esc_html($message)
    );
}

/**
 * Valida se un tablet ID è valido
 */
function mwm_is_valid_tablet_id($tablet_id) {
    if (!$tablet_id || !is_numeric($tablet_id)) {
        return false;
    }
    
    $tablet = get_post($tablet_id);
    
    return $tablet && $tablet->post_type === 'tablet' && $tablet->post_status === 'publish';
}

/**
 * Valida se un movimento ID è valido
 */
function mwm_is_valid_movimento_id($movimento_id) {
    if (!$movimento_id || !is_numeric($movimento_id)) {
        return false;
    }
    
    $movimento = get_post($movimento_id);
    
    return $movimento && $movimento->post_type === 'movimento_magazzino' && $movimento->post_status === 'publish';
}

/**
 * Ottiene le opzioni disponibili per il campo stato_dispositivo
 */
function mwm_get_stato_dispositivo_options() {
    // Queste dovrebbero corrispondere alle opzioni configurate in ACF
    return array(
        'disponibile' => 'Disponibile',
        'assegnato' => 'Assegnato',
        'in_manutenzione' => 'In Manutenzione',
        'venduto' => 'Venduto',
        'rientrato' => 'Rientrato'
    );
}

/**
 * Ottiene le opzioni disponibili per il campo tipo_di_movimento
 */
function mwm_get_tipo_movimento_options() {
    // Queste dovrebbero corrispondere alle opzioni configurate in ACF
    return array(
        'assegnazione' => 'Assegnazione',
        'vendita' => 'Vendita', 
        'rientro' => 'Rientro',
        'manutenzione' => 'Manutenzione',
        'spedizione' => 'Spedizione',
        'assegnazione_affitto' => 'Assegnazione Affitto'
    );
}

/**
 * Ottiene le opzioni per tipologia tablet
 */
function mwm_get_tipologia_tablet_options() {
    return array(
        'tablet_android' => 'Tablet Android',
        'tablet_ios' => 'Tablet iOS', 
        'tablet_windows' => 'Tablet Windows'
    );
}

/**
 * Formatta una data per la visualizzazione
 */
function mwm_format_date($date_string, $format = 'd/m/Y') {
    if (!$date_string) {
        return '-';
    }
    
    $timestamp = is_numeric($date_string) ? $date_string : strtotime($date_string);
    
    if (!$timestamp) {
        return '-';
    }
    
    return date($format, $timestamp);
}

/**
 * Formatta data e ora per la visualizzazione
 */
function mwm_format_datetime($date_string, $format = 'd/m/Y H:i') {
    return mwm_format_date($date_string, $format);
}

/**
 * Genera un ID univoco per un nuovo movimento
 */
function mwm_generate_movimento_id($tablet_id) {
    $tablet = get_post($tablet_id);
    $tablet_title = $tablet ? $tablet->post_title : 'Unknown';
    $date = current_time('Y-m-d');
    
    // Conta i movimenti esistenti per questo tablet oggi
    $existing_movements = get_posts(array(
        'post_type' => 'movimento_magazzino',
        'meta_query' => array(
            array(
                'key' => 'tablet_coinvolto',
                'value' => $tablet_id,
                'compare' => '='
            ),
            array(
                'key' => 'data_movimento',
                'value' => $date,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    ));
    
    $sequence = count($existing_movements) + 1;
    
    return sprintf('Movimento %s - %s-%02d', $tablet_title, $date, $sequence);
}

/**
 * Sanitizza i dati di input per i form
 */
function mwm_sanitize_form_data($data, $expected_fields = array()) {
    $sanitized = array();
    
    foreach ($expected_fields as $field => $type) {
        if (!isset($data[$field])) {
            continue;
        }
        
        switch ($type) {
            case 'text':
            case 'select':
                $sanitized[$field] = sanitize_text_field($data[$field]);
                break;
                
            case 'textarea':
                $sanitized[$field] = sanitize_textarea_field($data[$field]);
                break;
                
            case 'email':
                $sanitized[$field] = sanitize_email($data[$field]);
                break;
                
            case 'url':
                $sanitized[$field] = esc_url_raw($data[$field]);
                break;
                
            case 'int':
                $sanitized[$field] = intval($data[$field]);
                break;
                
            case 'bool':
                $sanitized[$field] = (bool) $data[$field];
                break;
                
            case 'date':
                // Valida formato data
                $date = DateTime::createFromFormat('Y-m-d', $data[$field]);
                $sanitized[$field] = ($date && $date->format('Y-m-d') === $data[$field]) ? $data[$field] : '';
                break;
                
            default:
                $sanitized[$field] = sanitize_text_field($data[$field]);
        }
    }
    
    return $sanitized;
}

/**
 * Logga eventi del warehouse per debugging
 */
function mwm_log($message, $level = 'info') {
    if (WP_DEBUG) {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_message = sprintf('[%s] MWM %s: %s', $timestamp, strtoupper($level), $message);
        error_log($log_message);
    }
}

/**
 * Ottiene tutti i termini di una tassonomia come array di opzioni
 */
function mwm_get_taxonomy_options($taxonomy, $include_empty = true) {
    $options = array();
    
    if ($include_empty) {
        $options[''] = '--- Seleziona ---';
    }
    
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    if (is_wp_error($terms)) {
        mwm_log('Errore nel recupero termini per tassonomia: ' . $taxonomy, 'error');
        return $options;
    }
    
    foreach ($terms as $term) {
        $prefix = '';
        
        // Aggiungi prefisso per termini gerarchici
        if ($term->parent && $taxonomy === 'destinazioni_interne') {
            $prefix = '— ';
        }
        
        $options[$term->term_id] = $prefix . $term->name;
    }
    
    return $options;
}

/**
 * Ottiene il nome di un termine da taxonomy + term_id
 */
function mwm_get_term_name($taxonomy, $term_id) {
    if (!$term_id) {
        return '';
    }
    
    $term = get_term($term_id, $taxonomy);
    
    return is_wp_error($term) ? '' : $term->name;
}

/**
 * Genera opzioni HTML per un select da un array
 */
function mwm_generate_select_options($options, $selected_value = '') {
    $html = '';
    
    foreach ($options as $value => $label) {
        $selected = selected($selected_value, $value, false);
        $html .= sprintf('<option value="%s"%s>%s</option>', esc_attr($value), $selected, esc_html($label));
    }
    
    return $html;
}

/**
 * Controlla se una pagina specifica del warehouse è attualmente visualizzata
 */
function mwm_is_warehouse_page($page_slugs = null) {
    if (!is_page()) {
        return false;
    }
    
    $warehouse_pages = array(
        'magazzino-tablet',
        'modifica-tablet', 
        'esegui-movimento',
        'cronologia-movimenti',
        'aggiungi-nuovo-tablet',
        'visualizza-tablet',
        'spostamento-di-gruppo'
    );
    
    if ($page_slugs) {
        $warehouse_pages = is_array($page_slugs) ? $page_slugs : array($page_slugs);
    }
    
    global $post;
    
    return in_array($post->post_name, $warehouse_pages);
}

/**
 * Ottiene URL delle pagine del warehouse
 */
function mwm_get_warehouse_page_url($page_slug, $params = array()) {
    $url = home_url('/' . $page_slug . '/');
    
    if (!empty($params)) {
        $url = add_query_arg($params, $url);
    }
    
    return $url;
}

/**
 * Genera breadcrumb per le pagine del warehouse
 */
function mwm_generate_breadcrumb() {
    if (!mwm_is_warehouse_page()) {
        return '';
    }
    
    global $post;
    
    $breadcrumb_map = array(
        'magazzino-tablet' => 'Dashboard Magazzino',
        'modifica-tablet' => 'Modifica Tablet',
        'esegui-movimento' => 'Registra Movimento', 
        'cronologia-movimenti' => 'Cronologia Movimenti',
        'aggiungi-nuovo-tablet' => 'Aggiungi Tablet',
        'spostamento-di-gruppo' => 'Spostamento di Gruppo'
    );
    
    $current_page = isset($breadcrumb_map[$post->post_name]) ? $breadcrumb_map[$post->post_name] : $post->post_title;
    
    $breadcrumb = '<nav class="mwm-breadcrumb">';
    $breadcrumb .= '<a href="' . mwm_get_warehouse_page_url('magazzino-tablet') . '">Dashboard</a>';
    $breadcrumb .= ' <span class="mwm-breadcrumb-separator">></span> ';
    $breadcrumb .= '<span class="mwm-breadcrumb-current">' . esc_html($current_page) . '</span>';
    $breadcrumb .= '</nav>';
    
    return $breadcrumb;
}