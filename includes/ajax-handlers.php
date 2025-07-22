<?php
/**
 * Gestori AJAX per il plugin Warehouse Manager - VERSIONE DEBUG
 * 
 * Versione corretta con logging e gestione errori migliorata
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inizializza gli handler AJAX - CON DEBUG
 */
function mwm_init_ajax_handlers() {
    // Handler per modal dettagli (mantenuto)
    add_action('wp_ajax_mwm_get_tablet_details', 'mwm_ajax_get_tablet_details');
    add_action('wp_ajax_nopriv_mwm_get_tablet_details', 'mwm_ajax_get_tablet_details'); // Per utenti non loggati se necessario
    
    // NUOVI: Handler per modal forms
    add_action('wp_ajax_mwm_get_edit_form', 'mwm_ajax_get_edit_form');
    add_action('wp_ajax_nopriv_mwm_get_edit_form', 'mwm_ajax_get_edit_form');
    
    add_action('wp_ajax_mwm_get_movement_form', 'mwm_ajax_get_movement_form');
    add_action('wp_ajax_nopriv_mwm_get_movement_form', 'mwm_ajax_get_movement_form');
    
    add_action('wp_ajax_mwm_get_add_form', 'mwm_ajax_get_add_form');
    add_action('wp_ajax_nopriv_mwm_get_add_form', 'mwm_ajax_get_add_form');
    
    // Handler per azioni di gruppo
    add_action('wp_ajax_mwm_bulk_action', 'mwm_ajax_bulk_action');
    add_action('wp_ajax_nopriv_mwm_bulk_action', 'mwm_ajax_bulk_action');
    
    // Handler per refresh dashboard dopo submit form
    add_action('wp_ajax_mwm_refresh_dashboard', 'mwm_ajax_refresh_dashboard');
    add_action('wp_ajax_nopriv_mwm_refresh_dashboard', 'mwm_ajax_refresh_dashboard');
    
    // Debug: Log che gli handler sono stati registrati
    if (WP_DEBUG) {
        error_log('MWM: AJAX handlers registrati correttamente');
    }
}

// Inizializza handler
mwm_init_ajax_handlers();

/**
 * AJAX: Ottiene dettagli tablet per modal visualizzazione
 */
function mwm_ajax_get_tablet_details() {
    if (WP_DEBUG) {
        error_log('MWM: AJAX get_tablet_details chiamato');
        error_log('MWM: POST data: ' . print_r($_POST, true));
    }
    
    // Verifica nonce
    if (!wp_verify_nonce($_POST['nonce'], 'warehouse_manager_nonce')) {
        if (WP_DEBUG) {
            error_log('MWM: Nonce verification failed for tablet details');
        }
        wp_send_json_error('Nonce non valido');
    }
    
    $tablet_id = intval($_POST['tablet_id']);
    
    if (!$tablet_id || get_post_type($tablet_id) !== 'tablet') {
        if (WP_DEBUG) {
            error_log('MWM: Invalid tablet ID: ' . $tablet_id);
        }
        wp_send_json_error('ID tablet non valido');
    }
    
    // Ottieni tutti i dati del tablet
    $tablet_data = mwm_get_complete_tablet_data($tablet_id);
    
    if (empty($tablet_data)) {
        if (WP_DEBUG) {
            error_log('MWM: No tablet data found for ID: ' . $tablet_id);
        }
        wp_send_json_error('Dati tablet non trovati');
    }
    
    // Renderizza template
    ob_start();
    include(MWM_PLUGIN_PATH . 'templates/modal-tablet-view.php');
    $html = ob_get_clean();
    
    if (WP_DEBUG) {
        error_log('MWM: Template rendered successfully for tablet: ' . $tablet_id);
    }
    
    wp_send_json_success(array(
        'html' => $html,
        'tablet_title' => get_the_title($tablet_id)
    ));
}

/**
 * AJAX: Carica form di modifica tablet in modal - CORRETTO
 */
function mwm_ajax_get_edit_form() {
    if (WP_DEBUG) {
        error_log('MWM: AJAX get_edit_form chiamato');
        error_log('MWM: POST data: ' . print_r($_POST, true));
    }
    
    // Verifica nonce
    if (!wp_verify_nonce($_POST['nonce'], 'warehouse_manager_nonce')) {
        if (WP_DEBUG) {
            error_log('MWM: Nonce verification failed for edit form');
        }
        wp_send_json_error('Nonce non valido');
    }
    
    $tablet_id = intval($_POST['tablet_id']);
    
    if (!$tablet_id || get_post_type($tablet_id) !== 'tablet') {
        if (WP_DEBUG) {
            error_log('MWM: Invalid tablet ID for edit: ' . $tablet_id);
        }
        wp_send_json_error('ID tablet non valido');
    }
    
    // Verifica che Frontend Admin sia attivo
    if (!function_exists('do_shortcode')) {
        if (WP_DEBUG) {
            error_log('MWM: WordPress shortcode function not available');
        }
        wp_send_json_error('Shortcode non disponibile');
    }
    
    // Genera shortcode Frontend Admin con post_id
    $shortcode = '[frontend_admin form=110 post_id="' . $tablet_id . '"]';
    
    if (WP_DEBUG) {
        error_log('MWM: Generating edit shortcode: ' . $shortcode);
    }
    
    $form_html = do_shortcode($shortcode);
    
    // Verifica che il shortcode abbia prodotto output
    if (empty($form_html) || $form_html === $shortcode) {
        if (WP_DEBUG) {
            error_log('MWM: Frontend Admin shortcode failed to render. Form ID 110 might not exist or plugin not active.');
        }
        wp_send_json_error('Form non configurato correttamente. Verifica che Frontend Admin sia attivo e che il form ID 110 esista.');
    }
    
    if (WP_DEBUG) {
        error_log('MWM: Edit form generated successfully for tablet: ' . $tablet_id);
    }
    
    wp_send_json_success(array(
        'html' => $form_html,
        'tablet_title' => get_the_title($tablet_id),
        'tablet_id' => $tablet_id
    ));
}

/**
 * AJAX: Carica form movimento tablet in modal - CORRETTO
 */
function mwm_ajax_get_movement_form() {
    if (WP_DEBUG) {
        error_log('MWM: AJAX get_movement_form chiamato');
        error_log('MWM: POST data: ' . print_r($_POST, true));
    }
    
    // Verifica nonce
    if (!wp_verify_nonce($_POST['nonce'], 'warehouse_manager_nonce')) {
        if (WP_DEBUG) {
            error_log('MWM: Nonce verification failed for movement form');
        }
        wp_send_json_error('Nonce non valido');
    }
    
    $tablet_id = intval($_POST['tablet_id']);
    
    if (!$tablet_id || get_post_type($tablet_id) !== 'tablet') {
        if (WP_DEBUG) {
            error_log('MWM: Invalid tablet ID for movement: ' . $tablet_id);
        }
        wp_send_json_error('ID tablet non valido');
    }
    
    // Genera shortcode Frontend Admin per movimento
    $shortcode = '[frontend_admin form=125 post_id="' . $tablet_id . '"]';
    
    if (WP_DEBUG) {
        error_log('MWM: Generating movement shortcode: ' . $shortcode);
    }
    
    $form_html = do_shortcode($shortcode);
    
    // Verifica che il shortcode abbia prodotto output
    if (empty($form_html) || $form_html === $shortcode) {
        if (WP_DEBUG) {
            error_log('MWM: Frontend Admin movement shortcode failed to render. Form ID 125 might not exist or plugin not active.');
        }
        wp_send_json_error('Form movimento non configurato correttamente. Verifica che Frontend Admin sia attivo e che il form ID 125 esista.');
    }
    
    if (WP_DEBUG) {
        error_log('MWM: Movement form generated successfully for tablet: ' . $tablet_id);
    }
    
    wp_send_json_success(array(
        'html' => $form_html,
        'tablet_title' => get_the_title($tablet_id),
        'tablet_id' => $tablet_id
    ));
}

/**
 * AJAX: Carica form aggiunta nuovo tablet - CORRETTO
 */
function mwm_ajax_get_add_form() {
    if (WP_DEBUG) {
        error_log('MWM: AJAX get_add_form chiamato');
        error_log('MWM: POST data: ' . print_r($_POST, true));
    }
    
    // Verifica nonce
    if (!wp_verify_nonce($_POST['nonce'], 'warehouse_manager_nonce')) {
        if (WP_DEBUG) {
            error_log('MWM: Nonce verification failed for add form');
        }
        wp_send_json_error('Nonce non valido');
    }
    
    // Genera shortcode Frontend Admin per nuovo tablet
    $shortcode = '[frontend_admin form=204]';
    
    if (WP_DEBUG) {
        error_log('MWM: Generating add shortcode: ' . $shortcode);
    }
    
    $form_html = do_shortcode($shortcode);
    
    // Verifica che il shortcode abbia prodotto output
    if (empty($form_html) || $form_html === $shortcode) {
        if (WP_DEBUG) {
            error_log('MWM: Frontend Admin add shortcode failed to render. Form ID 204 might not exist or plugin not active.');
        }
        wp_send_json_error('Form aggiunta non configurato correttamente. Verifica che Frontend Admin sia attivo e che il form ID 204 esista.');
    }
    
    if (WP_DEBUG) {
        error_log('MWM: Add form generated successfully');
    }
    
    wp_send_json_success(array(
        'html' => $form_html
    ));
}

/**
 * AJAX: Gestisce azioni di gruppo sui tablet
 */
function mwm_ajax_bulk_action() {
    if (WP_DEBUG) {
        error_log('MWM: AJAX bulk_action chiamato');
        error_log('MWM: POST data: ' . print_r($_POST, true));
    }
    
    // Verifica nonce
    if (!wp_verify_nonce($_POST['nonce'], 'warehouse_manager_nonce')) {
        if (WP_DEBUG) {
            error_log('MWM: Nonce verification failed for bulk action');
        }
        wp_send_json_error('Nonce non valido');
    }
    
    $action = sanitize_text_field($_POST['bulk_action']);
    $tablet_ids = array_map('intval', $_POST['tablet_ids']);
    
    if (empty($tablet_ids) || empty($action)) {
        if (WP_DEBUG) {
            error_log('MWM: Missing parameters for bulk action - Action: ' . $action . ', IDs: ' . print_r($tablet_ids, true));
        }
        wp_send_json_error('Parametri mancanti');
    }
    
    $updated_count = 0;
    
    foreach ($tablet_ids as $tablet_id) {
        if (get_post_type($tablet_id) !== 'tablet') {
            if (WP_DEBUG) {
                error_log('MWM: Skipping invalid tablet ID: ' . $tablet_id);
            }
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
            if (WP_DEBUG) {
                error_log('MWM: Bulk action ' . $action . ' applied to tablet: ' . $tablet_id);
            }
        }
    }
    
    if (WP_DEBUG) {
        error_log('MWM: Bulk action completed. Updated: ' . $updated_count . ' of ' . count($tablet_ids));
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
    if (WP_DEBUG) {
        error_log('MWM: AJAX refresh_dashboard chiamato');
    }
    
    // Verifica nonce
    if (!wp_verify_nonce($_POST['nonce'], 'warehouse_manager_nonce')) {
        if (WP_DEBUG) {
            error_log('MWM: Nonce verification failed for dashboard refresh');
        }
        wp_send_json_error('Nonce non valido');
    }
    
    // Verifica che le funzioni necessarie esistano
    if (!function_exists('mwm_get_all_tablets_for_dashboard') || 
        !function_exists('mwm_generate_tablet_stats') || 
        !function_exists('mwm_generate_tablet_row')) {
        if (WP_DEBUG) {
            error_log('MWM: Required functions not available for dashboard refresh');
        }
        wp_send_json_error('Funzioni dashboard non disponibili');
    }
    
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
    
    if (WP_DEBUG) {
        error_log('MWM: Dashboard refresh completed successfully');
    }
    
    wp_send_json_success(array(
        'stats_html' => $stats_html,
        'table_html' => $table_html
    ));
}

/**
 * Utility: Ottiene dati completi tablet per modal - CON DEBUG
 */
function mwm_get_complete_tablet_data($tablet_id) {
    if (WP_DEBUG) {
        error_log('MWM: Getting complete tablet data for ID: ' . $tablet_id);
    }
    
    $tablet = get_post($tablet_id);
    
    if (!$tablet) {
        if (WP_DEBUG) {
            error_log('MWM: Tablet post not found for ID: ' . $tablet_id);
        }
        return array();
    }
    
    // Verifica che ACF sia disponibile
    if (!function_exists('get_field')) {
        if (WP_DEBUG) {
            error_log('MWM: ACF get_field function not available');
        }
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
    
    if (WP_DEBUG) {
        error_log('MWM: Tablet data collected successfully for ID: ' . $tablet_id);
        error_log('MWM: Destinazione data: ' . print_r($tablet_data['dove'], true));
    }
    
    return $tablet_data;
}

/**
 * Hook Frontend Admin: Dopo salvataggio form - CON DEBUG
 */
function mwm_handle_frontend_admin_save($post_id, $form) {
    if (WP_DEBUG) {
        error_log('MWM: Frontend Admin save hook triggered for post: ' . $post_id);
        error_log('MWM: Form data: ' . print_r($form, true));
    }
    
    // Hook chiamato automaticamente da Frontend Admin dopo ogni salvataggio
    if (get_post_type($post_id) === 'tablet') {
        // Log dell'operazione
        error_log('MWM: Tablet aggiornato via modal form - ID: ' . $post_id);
        
        // Aggiorna timestamp ultima modifica se il campo esiste
        if (function_exists('update_field')) {
            update_field('ultima_modifica', current_time('mysql'), $post_id);
        }
        
        // Trigger custom hook per estensioni future
        do_action('mwm_tablet_updated_via_modal', $post_id, $form);
        
        if (WP_DEBUG) {
            error_log('MWM: Post-save operations completed for tablet: ' . $post_id);
        }
    }
}

// Hook salvataggio Frontend Admin
add_action('frontend_admin/forms/edit_post/after_save', 'mwm_handle_frontend_admin_save', 10, 2);
add_action('frontend_admin/forms/new_post/after_save', 'mwm_handle_frontend_admin_save', 10, 2);

/**
 * Customizza output Frontend Admin per modal - CON DEBUG
 */
function mwm_customize_frontend_admin_for_modal($html, $form) {
    if (WP_DEBUG && empty($html)) {
        error_log('MWM: Empty HTML returned from Frontend Admin form');
    }
    
    // Aggiungi classi CSS per styling modal
    $html = str_replace('<form', '<form class="mwm-modal-form"', $html);
    
    // Aggiungi JavaScript per gestire submit e chiusura modal
    $js = "
    <script>
    jQuery(document).ready(function($) {
        console.log('MWM: Frontend Admin form customization loaded');
        
        // Gestisce submit form nelle modal
        $('.mwm-modal-form').on('submit', function(e) {
            console.log('MWM: Form submission detected');
            // Il form viene gestito da Frontend Admin
            // Aggiungiamo solo feedback visivo
            var submitBtn = $(this).find('button[type=\"submit\"], input[type=\"submit\"]');
            submitBtn.prop('disabled', true).text('Salvando...');
        });
        
        // Refresh dashboard dopo salvataggio riuscito
        $(document).on('frontend_admin_form_success', function(e, response) {
            console.log('MWM: Form success event received', response);
            setTimeout(function() {
                if (typeof WarehouseManager !== 'undefined') {
                    WarehouseManager.refreshDashboard();
                    WarehouseManager.closeAllModals();
                    WarehouseManager.showSuccess('Operazione completata con successo');
                } else {
                    console.error('MWM: WarehouseManager object not available');
                }
            }, 1000);
        });
        
        // Gestisce errori form
        $(document).on('frontend_admin_form_error', function(e, response) {
            console.error('MWM: Form error event received', response);
            if (typeof WarehouseManager !== 'undefined') {
                WarehouseManager.showError('Errore nel salvataggio. Riprova.');
            }
        });
    });
    </script>
    ";
    
    return $html . $js;
}

add_filter('frontend_admin_form_output', 'mwm_customize_frontend_admin_for_modal', 10, 2);
