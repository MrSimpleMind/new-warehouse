<?php
/**
 * Funzioni helper per il plugin Warehouse Manager
 * 
 * Contiene funzioni utility riutilizzabili in tutto il plugin
 * per formattazione, validazione e operazioni comuni.
 * Versione senza controlli di sicurezza per sviluppo.
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
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
 * Ottiene le opzioni disponibili per il campo stato_dispositivo
 */
function mwm_get_stato_dispositivo_options() {
    return array(
        'disponibile' => 'Disponibile',
        'assegnato' => 'Assegnato',
        'in_manutenzione' => 'In Manutenzione',
        'venduto' => 'Venduto',
        'rientrato' => 'Rientrato'
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
 * Ottiene tutti i termini della tassonomia destinazioni come array di opzioni
 */
function mwm_get_destinazione_options($include_empty = true) {
    $options = array();
    
    if ($include_empty) {
        $options[''] = '--- Non Assegnato ---';
    }
    
    $terms = get_terms(array(
        'taxonomy' => 'destinazione',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    if (is_wp_error($terms)) {
        mwm_log('Errore nel recupero termini per tassonomia destinazione', 'error');
        return $options;
    }
    
    foreach ($terms as $term) {
        $prefix = '';
        
        // Aggiungi prefisso per termini gerarchici (se esistono)
        if ($term->parent) {
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
        'magazzino-tablet'
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
        'magazzino-tablet' => 'Dashboard Magazzino'
    );
    
    $current_page = isset($breadcrumb_map[$post->post_name]) ? $breadcrumb_map[$post->post_name] : $post->post_title;
    
    $breadcrumb = '<nav class="mwm-breadcrumb">';
    $breadcrumb .= '<span class="mwm-breadcrumb-current">' . esc_html($current_page) . '</span>';
    $breadcrumb .= '</nav>';
    
    return $breadcrumb;
}

/**
 * Ottiene statistiche rapide sui tablet
 */
function mwm_get_tablet_statistics() {
    $tablets = get_posts(array(
        'post_type' => 'tablet',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ));
    
    $stats = array(
        'total' => count($tablets),
        'disponibili' => 0,
        'assegnati' => 0,
        'in_manutenzione' => 0,
        'con_kiosk' => 0,
        'con_sim_attiva' => 0
    );
    
    foreach ($tablets as $tablet) {
        $stato = get_field('stato_dispositivo', $tablet->ID);
        $kiosk = get_field('modalita_kiosk', $tablet->ID);
        $sim_attiva = get_field('sim_attiva', $tablet->ID);
        
        // Conta stati
        if ($stato === 'disponibile') $stats['disponibili']++;
        if ($stato === 'assegnato') $stats['assegnati']++;
        if ($stato === 'in_manutenzione') $stats['in_manutenzione']++;
        
        // Conta configurazioni
        if ($kiosk) $stats['con_kiosk']++;
        if ($sim_attiva) $stats['con_sim_attiva']++;
    }
    
    return $stats;
}

/**
 * Ottiene lista destinazioni più utilizzate
 */
function mwm_get_popular_destinations($limit = 5) {
    $tablets = get_posts(array(
        'post_type' => 'tablet',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ));
    
    $destinations = array();
    
    foreach ($tablets as $tablet) {
        $destinazione = get_field('dove', $tablet->ID);
        
        if ($destinazione && is_object($destinazione)) {
            $dest_name = $destinazione->name;
            
            if (!isset($destinations[$dest_name])) {
                $destinations[$dest_name] = 0;
            }
            
            $destinations[$dest_name]++;
        }
    }
    
    // Ordina per popolarità
    arsort($destinations);
    
    // Limita risultati
    if ($limit > 0) {
        $destinations = array_slice($destinations, 0, $limit, true);
    }
    
    return $destinations;
}

/**
 * Formatta informazioni tablet per export
 */
function mwm_format_tablet_for_export($tablet_id) {
    $tablet = get_post($tablet_id);
    
    if (!$tablet) {
        return null;
    }
    
    $destinazione = get_field('dove', $tablet_id);
    $destinazione_nome = $destinazione ? $destinazione->name : 'Non assegnato';
    
    return array(
        'ID' => $tablet->post_title,
        'IMEI' => get_field('imei_tablet', $tablet_id) ?: '',
        'Stato' => get_field('stato_dispositivo', $tablet_id) ?: '',
        'Tipologia' => get_field('tipologia', $tablet_id) ?: '',
        'Destinazione' => $destinazione_nome,
        'Modalità Kiosk' => get_field('modalita_kiosk', $tablet_id) ? 'Sì' : 'No',
        'SIM Inserita' => get_field('sim_inserita', $tablet_id) ? 'Sì' : 'No',
        'SIM Attiva' => get_field('sim_attiva', $tablet_id) ? 'Sì' : 'No',
        'Cover' => get_field('cover', $tablet_id) ? 'Sì' : 'No',
        'Scatola' => get_field('scatola', $tablet_id) ? 'Sì' : 'No',
        'Data Carico' => mwm_format_date(get_field('data_di_carico', $tablet_id)),
        'Note' => get_field('note_generali_tablet', $tablet_id) ?: ''
    );
}

/**
 * Cerca tablet per termine di ricerca
 */
function mwm_search_tablets($search_term, $limit = 10) {
    $args = array(
        'post_type' => 'tablet',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        's' => $search_term
    );
    
    // Cerca anche nei campi ACF
    $meta_query = array(
        'relation' => 'OR',
        array(
            'key' => 'imei_tablet',
            'value' => $search_term,
            'compare' => 'LIKE'
        )
    );
    
    $args['meta_query'] = $meta_query;
    
    return get_posts($args);
}

/**
 * Valida configurazione tablet
 */
function mwm_validate_tablet_config($tablet_id) {
    $errors = array();
    
    $imei = get_field('imei_tablet', $tablet_id);
    $stato = get_field('stato_dispositivo', $tablet_id);
    $sim_inserita = get_field('sim_inserita', $tablet_id);
    $sim_attiva = get_field('sim_attiva', $tablet_id);
    
    // Validazioni
    if (!$imei || strlen($imei) !== 15) {
        $errors[] = 'IMEI deve essere di 15 cifre';
    }
    
    if (!$stato) {
        $errors[] = 'Stato dispositivo è obbligatorio';
    }
    
    if ($sim_attiva && !$sim_inserita) {
        $errors[] = 'SIM non può essere attiva se non inserita';
    }
    
    return $errors;
}
