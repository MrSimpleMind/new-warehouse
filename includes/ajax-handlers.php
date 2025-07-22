<?php
/**
 * Gestori AJAX per il plugin Warehouse Manager
 * 
 * Gestisce tutte le chiamate AJAX per modal, form Frontend Admin
 * e operazioni sui tablet.
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inizializza gli handler AJAX
 */
function mwm_init_ajax_handlers() {
    // Handler per modal dettagli (mantenuto)
    add_action('wp_ajax_mwm_get_tablet_details', 'mwm_ajax_get_tablet_details');
    
    // Nuovi handler per modal forms
    add_action('wp_ajax_mwm_get_edit_form', 'mwm_ajax_get_edit_form');
    add_action('wp_ajax_mwm_get_movement_form', 'mwm_ajax_get_movement_form'); 
    add_action('wp_ajax_mwm_get_add_form', 'mwm_ajax_get_add_form');
    
    // Handler per azioni di gruppo
    add_action('wp_ajax_mwm_bulk_action', 'mwm_ajax_bulk_action');
    
    // Handler per refresh dashboard dopo submit form
    add_action('wp_ajax_mwm_refresh_dashboard', 'mwm_ajax_refresh_dashboard');
}

// Inizializza handler
mwm_init_ajax_handlers();

/**
 * AJAX: Ottiene dettagli tablet per modal visualizzazione
 */
function mwm_ajax_get_tablet_details() {
    $tablet_id = intval($_POST['tablet_id']);
    
    if (!$tablet_id || get_post_type($tablet_id) !== 'tablet') {
        wp_send_json_error('ID tablet non valido');
    }
    
    // Ottieni tutti i dati del tablet
    $tablet_data = mwm_get_complete_tablet_data($tablet_id);
    
    // Renderizza template
    ob_start();
    include(MWM_PLUGIN_PATH . 'templates/modal-tablet-view.php');
    $html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $html,
        'tablet_title' => get_the_title($tablet_id)
    ));
}

/**
 * AJAX: Carica form di modifica tablet in modal
 */
function mwm_ajax_get_edit_form() {
    $tablet_id = intval($_POST['tablet_id']);
    
    if (!$tablet_id || get_post_type($tablet_id) !== 'tablet') {
        wp_send_json_error('ID tablet non valido');
    }
    
    // Genera shortcode Frontend Admin con post_id
    $shortcode = '[frontend_admin form=110 post_id="' . $tablet_id . '"]';
    $form_html = do_shortcode($shortcode);
    
    wp_send_json_success(array(
        'html' => $form_html,
        'tablet_title' => get_the_title($tablet_id),
        'tablet_id' => $tablet_id
    ));
}

/**
 * AJAX: Carica form movimento tablet in modal
 */
function mwm_ajax_get_movement_form() {
    $tablet_id = intval($_POST['tablet_id']);
    
    if (!$tablet_id || get_post_type($tablet_id) !== 'tablet') {
        wp_send_json_error('ID tablet non valido');
    }
    
    // Genera shortcode Frontend Admin per movimento
    $shortcode = '[frontend_admin form=125 post_id="' . $tablet_id . '"]';
    $form_html = do_shortcode($shortcode);
    
    wp_send_json_success(array(
        'html' => $form_html,
        'tablet_title' => get_the_title($tablet_id),
        'tablet_id' => $tablet_id
    ));
}

/**
 * AJAX: Carica form aggiunta nuovo tablet
 */
function mwm_ajax_get_add_form() {
    // Genera shortcode Frontend Admin per nuovo tablet
    $shortcode = '[frontend_admin form=204]';
    $form_html = do_shortcode($shortcode);
    
    wp_send_json_success(array(
        'html' => $form_html
    ));
}

/**
 * AJAX: Gestisce azioni di gruppo sui tablet
 */
function mwm_ajax_bulk_action() {
    $action = sanitize_text_field($_POST['bulk_action']);
    $tablet_ids = array_map('intval', $_POST['tablet_ids']);
    
    if (empty($tablet_ids) || empty($action)) {
        wp_send_json_error('Parametri mancanti');
    }
    
    $updated_count = 0;
    
    foreach ($tablet_ids as $tablet_id) {
        if (get_post_type($tablet_id) !== 'tablet') {
            continue;
        }
        
        $success = false;
        
        switch ($action) {
            case 'kiosk_on':
                $success = update_field('modalita_kiosk', 1, $tablet_id);
                break;
                
            case 'kiosk_off':
                $success = update_field('modalita_kiosk', 0, $tablet_id);
                break;
                
            case 'sim_active_on':
                update_field('sim_inserita', 1, $tablet_id);
                $success = update_field('sim_attiva', 1, $tablet_id);
                break;
                
            case 'sim_active_off':
                $success = update_field('sim_attiva', 0, $tablet_id);
                break;
        }
        
        if ($success) {
            $updated_count++;
        }
    }
    
    wp_send_json_success(array(
        'message' => sprintf('Aggiornati %d tablet su %d selezionati', $updated_count, count($tablet_ids)),
        'updated_count' => $updated_count
    ));
}

/**
 * AJAX: Refresh dashboard dopo submit form
 */
function mwm_ajax_refresh_dashboard() {
    // Restituisci HTML aggiornato per statistiche e tabella
    $tablets = mwm_get_all_tablets_for_dashboard();
    
    ob_start();
    echo mwm_generate_tablet_stats($tablets);
    $stats_html = ob_get_clean();
    
    ob_start();
    if (empty($tablets)) {
        echo '<tr><td colspan="9" class="mwm-no-data">Nessun tablet trovato.</td></tr>';
    } else {
        foreach ($tablets as $tablet) {
            echo mwm_generate_tablet_row($tablet);
        }
    }
    $table_html = ob_get_clean();
    
    wp_send_json_success(array(
        'stats_html' => $stats_html,
        'table_html' => $table_html
    ));
}

/**
 * Utility: Ottiene dati completi tablet per modal
 */
function mwm_get_complete_tablet_data($tablet_id) {
    $tablet = get_post($tablet_id);
    
    if (!$tablet) {
        return array();
    }
    
    // Ottieni tutti i custom field
    $tablet_data = array(
        'tablet_id' => $tablet_id,
        'tablet_title' => $tablet->post_title,
        'post_date' => $tablet->post_date,
        'post_modified' => $tablet->post_modified,
        
        // Campi ACF
        'stato_dispositivo' => get_field('stato_dispositivo', $tablet_id),
        'tipologia' => get_field('tipologia', $tablet_id),
        'imei_tablet' => get_field('imei_tablet', $tablet_id),
        'data_di_carico' => get_field('data_di_carico', $tablet_id),
        'modalita_kiosk' => get_field('modalita_kiosk', $tablet_id),
        'sim_inserita' => get_field('sim_inserita', $tablet_id),
        'sim_attiva' => get_field('sim_attiva', $tablet_id),
        'sn_sim' => get_field('sn_sim', $tablet_id),
        'pin_sim' => get_field('pin_sim', $tablet_id),
        'puk_sim' => get_field('puk_sim', $tablet_id),
        'cover' => get_field('cover', $tablet_id),
        'scatola' => get_field('scatola', $tablet_id),
        'note_generali_tablet' => get_field('note_generali_tablet', $tablet_id),
        
        // Nuova tassonomia unificata
        'dove' => get_field('dove', $tablet_id) // Oggetto termine
    );
    
    return $tablet_data;
}

/**
 * Hook Frontend Admin: Dopo salvataggio form
 */
function mwm_handle_frontend_admin_save($post_id, $form) {
    // Hook chiamato automaticamente da Frontend Admin dopo ogni salvataggio
    
    if (get_post_type($post_id) === 'tablet') {
        // Log dell'operazione
        error_log('MWM: Tablet aggiornato via modal form - ID: ' . $post_id);
        
        // Aggiorna timestamp ultima modifica
        update_field('ultima_modifica', current_time('mysql'), $post_id);
        
        // Trigger custom hook per estensioni future
        do_action('mwm_tablet_updated_via_modal', $post_id, $form);
    }
}

// Hook salvataggio Frontend Admin
add_action('frontend_admin/forms/edit_post/after_save', 'mwm_handle_frontend_admin_save', 10, 2);
add_action('frontend_admin/forms/new_post/after_save', 'mwm_handle_frontend_admin_save', 10, 2);

/**
 * Customizza output Frontend Admin per modal
 */
function mwm_customize_frontend_admin_for_modal($html, $form) {
    // Aggiungi classi CSS per styling modal
    $html = str_replace('<form', '<form class="mwm-modal-form"', $html);
    
    // Aggiungi JavaScript per gestire submit e chiusura modal
    $js = "
    <script>
    jQuery(document).ready(function($) {
        // Gestisce submit form nelle modal
        $('.mwm-modal-form').on('submit', function(e) {
            // Il form viene gestito da Frontend Admin
            // Aggiungiamo solo feedback visivo
            var submitBtn = $(this).find('button[type=\"submit\"], input[type=\"submit\"]');
            submitBtn.prop('disabled', true).text('Salvando...');
        });
        
        // Refresh dashboard dopo salvataggio riuscito
        $(document).on('frontend_admin_form_success', function() {
            setTimeout(function() {
                WarehouseManager.refreshDashboard();
                WarehouseManager.closeAllModals();
                WarehouseManager.showSuccess('Operazione completata con successo');
            }, 1000);
        });
    });
    </script>
    ";
    
    return $html . $js;
}

add_filter('frontend_admin_form_output', 'mwm_customize_frontend_admin_for_modal', 10, 2);
