<?php
/**
 * Integrazione con Frontend Admin by DynamiApps
 * 
 * Gestisce hook, filtri e logica personalizzata per i form
 * modal di modifica tablet, registrazione movimenti e aggiunta tablet
 * tramite Frontend Admin.
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inizializza gli hook per Frontend Admin
 */
function mwm_init_frontend_admin_integration() {
    // Hook per personalizzare i form Frontend Admin
    add_filter('frontend_admin/forms/validation', 'mwm_validate_frontend_forms', 10, 3);
    add_action('frontend_admin/forms/edit_post/after_save', 'mwm_handle_tablet_edit_save', 10, 3);
    add_action('frontend_admin/forms/new_post/after_save', 'mwm_handle_tablet_create_save', 10, 3);
    
    // Hook per pre-popolare campi
    add_filter('frontend_admin/forms/load_value', 'mwm_prepopulate_form_fields', 10, 4);
    
    // Hook per custom field rendering
    add_filter('frontend_admin/fields/render_field', 'mwm_render_custom_fields', 10, 3);
    
    // Hook per personalizzare output form in modal
    add_filter('frontend_admin_form_output', 'mwm_customize_form_for_modal', 10, 2);
}

// Inizializza integrazione
mwm_init_frontend_admin_integration();

/**
 * Valida i form Frontend Admin personalizzati
 */
function mwm_validate_frontend_forms($errors, $form_data, $form_id) {
    // Validazione per form modifica tablet (ID 110)
    if ($form_id == '110') {
        $errors = array_merge($errors, mwm_validate_tablet_form($form_data));
    }
    
    // Validazione per form nuovo tablet (ID 204)
    if ($form_id == '204') {
        $errors = array_merge($errors, mwm_validate_new_tablet_form($form_data));
    }
    
    // Validazione per form movimento (ID 125)
    if ($form_id == '125') {
        $errors = array_merge($errors, mwm_validate_movement_form($form_data));
    }
    
    return $errors;
}

/**
 * Valida form di modifica tablet
 */
function mwm_validate_tablet_form($form_data) {
    $errors = array();
    
    // Validazione IMEI (deve essere numerica e lunga 15 cifre)
    if (isset($form_data['imei_tablet']) && !empty($form_data['imei_tablet'])) {
        $imei = preg_replace('/\D/', '', $form_data['imei_tablet']);
        if (strlen($imei) !== 15) {
            $errors['imei_tablet'] = 'IMEI deve contenere esattamente 15 cifre';
        }
    }
    
    // Validazione campi SIM condizionali
    if (isset($form_data['sim_attiva']) && $form_data['sim_attiva'] && 
        (!isset($form_data['sim_inserita']) || !$form_data['sim_inserita'])) {
        $errors['sim_attiva'] = 'Non è possibile attivare la SIM se non è inserita';
    }
    
    return $errors;
}

/**
 * Valida form di nuovo tablet
 */
function mwm_validate_new_tablet_form($form_data) {
    $errors = array();
    
    // Validazioni obbligatorie per nuovo tablet
    if (empty($form_data['post_title'])) {
        $errors['post_title'] = 'ID Tablet è obbligatorio';
    }
    
    // Controllo IMEI duplicato
    if (!empty($form_data['imei_tablet'])) {
        $existing_tablet = get_posts(array(
            'post_type' => 'tablet',
            'meta_query' => array(
                array(
                    'key' => 'imei_tablet',
                    'value' => $form_data['imei_tablet'],
                    'compare' => '='
                )
            )
        ));
        
        if (!empty($existing_tablet)) {
            $errors['imei_tablet'] = 'IMEI già presente nel sistema';
        }
    }
    
    return $errors;
}

/**
 * Valida form di movimento
 */
function mwm_validate_movement_form($form_data) {
    $errors = array();
    
    // Per ora il form movimento è semplicemente una modifica del tablet
    // con focus sulla destinazione
    if (empty($form_data['dove'])) {
        $errors['dove'] = 'Seleziona una destinazione per il movimento';
    }
    
    return $errors;
}

/**
 * Gestisce salvataggio form modifica tablet
 */
function mwm_handle_tablet_edit_save($post_id, $form_data, $form_id) {
    if (get_post_type($post_id) !== 'tablet' || $form_id != '110') {
        return;
    }
    
    // Log della modifica
    mwm_log("Tablet modificato via modal: ID {$post_id}", 'info');
    
    // Aggiorna timestamp ultima modifica
    update_field('ultima_modifica', current_time('mysql'), $post_id);
    
    // Gestione logica speciale per SIM
    mwm_handle_sim_logic_on_save($post_id, $form_data);
    
    // Hook personalizzato per dopo il salvataggio tablet
    do_action('mwm_after_tablet_edit_save', $post_id, $form_data);
}

/**
 * Gestisce salvataggio form nuovo tablet
 */
function mwm_handle_tablet_create_save($post_id, $form_data, $form_id) {
    if (get_post_type($post_id) !== 'tablet' || $form_id != '204') {
        return;
    }
    
    // Log della creazione
    mwm_log("Nuovo tablet creato: ID {$post_id}", 'info');
    
    // Imposta data di carico se non specificata
    if (!get_field('data_di_carico', $post_id)) {
        update_field('data_di_carico', current_time('Y-m-d'), $post_id);
    }
    
    // Imposta stato predefinito se non specificato
    if (!get_field('stato_dispositivo', $post_id)) {
        update_field('stato_dispositivo', 'disponibile', $post_id);
    }
    
    // Gestione logica SIM
    mwm_handle_sim_logic_on_save($post_id, $form_data);
    
    // Hook personalizzato per dopo la creazione tablet
    do_action('mwm_after_tablet_create_save', $post_id, $form_data);
}

/**
 * Pre-popola campi dei form Frontend Admin
 */
function mwm_prepopulate_form_fields($value, $field_name, $post_id, $form_id) {
    // Pre-popola data movimento con data corrente per form movimento
    if ($field_name === 'data_movimento' && $form_id == '125' && empty($value)) {
        return current_time('Y-m-d');
    }
    
    // Pre-popola data di carico per nuovo tablet
    if ($field_name === 'data_di_carico' && $form_id == '204' && empty($value)) {
        return current_time('Y-m-d');
    }
    
    return $value;
}

/**
 * Renderizza campi personalizzati per Frontend Admin
 */
function mwm_render_custom_fields($html, $field, $form_id) {
    // Personalizza campo destinazione per avere opzioni più chiare
    if ($field['name'] === 'dove' && $field['type'] === 'taxonomy') {
        $current_value = $field['value'] ?? '';
        $options = mwm_get_destinazione_options_for_frontend();
        
        $select_html = '<select name="' . esc_attr($field['name']) . '" id="' . esc_attr($field['name']) . '" class="mwm-destination-select">';
        
        foreach ($options as $value => $label) {
            $selected = selected($current_value, $value, false);
            $select_html .= sprintf('<option value="%s"%s>%s</option>', 
                esc_attr($value), $selected, esc_html($label));
        }
        
        $select_html .= '</select>';
        
        return $select_html;
    }
    
    return $html;
}

/**
 * Gestisce logica speciale per i campi SIM su salvataggio
 */
function mwm_handle_sim_logic_on_save($tablet_id, $form_data) {
    // Se SIM attiva è impostata a Sì, assicurati che SIM inserita sia Sì
    if (isset($form_data['sim_attiva']) && $form_data['sim_attiva']) {
        update_field('sim_inserita', 1, $tablet_id);
    }
    
    // Se SIM inserita è impostata a No, disattiva anche SIM attiva
    if (isset($form_data['sim_inserita']) && !$form_data['sim_inserita']) {
        update_field('sim_attiva', 0, $tablet_id);
        
        // Pulisci campi SIM correlati
        update_field('sn_sim', '', $tablet_id);
        update_field('pin_sim', '', $tablet_id);
        update_field('puk_sim', '', $tablet_id);
    }
}

/**
 * Ottiene opzioni destinazione per Frontend Admin
 */
function mwm_get_destinazione_options_for_frontend() {
    $options = array(
        '' => '--- Seleziona Destinazione ---'
    );
    
    $terms = get_terms(array(
        'taxonomy' => 'destinazione',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            $prefix = $term->parent ? '— ' : '';
            $options[$term->term_id] = $prefix . $term->name;
        }
    }
    
    return $options;
}

/**
 * Personalizza output form per modal
 */
function mwm_customize_form_for_modal($html, $form) {
    // Aggiungi wrapper per styling modal
    $html = '<div class="mwm-frontend-form-wrapper">' . $html . '</div>';
    
    // Aggiungi JavaScript per gestione modal
    $js = "
    <script>
    jQuery(document).ready(function($) {
        // Stile form in modal
        $('.mwm-frontend-form-wrapper form').addClass('mwm-modal-form-inner');
        
        // Gestisce submit form
        $('.mwm-frontend-form-wrapper form').on('submit', function(e) {
            var form = $(this);
            var submitBtn = form.find('button[type=\"submit\"], input[type=\"submit\"]');
            
            // Disabilita bottone e cambia testo
            submitBtn.prop('disabled', true);
            var originalText = submitBtn.val() || submitBtn.text();
            submitBtn.val('Salvando...').text('Salvando...');
            
            // Timeout per riabilitare se qualcosa va storto
            setTimeout(function() {
                if (submitBtn.prop('disabled')) {
                    submitBtn.prop('disabled', false).val(originalText).text(originalText);
                }
            }, 10000);
        });
        
        // Gestisce successo form
        $(document).on('frontend_admin_form_success', function(e, response) {
            console.log('Form salvato con successo:', response);
            
            // Chiudi modal dopo breve delay
            setTimeout(function() {
                if (typeof WarehouseManager !== 'undefined') {
                    WarehouseManager.closeAllModals();
                    WarehouseManager.refreshDashboard();
                    WarehouseManager.showSuccess('Tablet salvato con successo!');
                }
            }, 1500);
        });
        
        // Gestisce errori form
        $(document).on('frontend_admin_form_error', function(e, errors) {
            console.error('Errori form:', errors);
            
            if (typeof WarehouseManager !== 'undefined') {
                WarehouseManager.showError('Errore nel salvataggio. Controlla i dati inseriti.');
            }
        });
    });
    </script>
    
    <style>
    .mwm-frontend-form-wrapper {
        padding: 20px 0;
    }
    
    .mwm-frontend-form-wrapper .frontend-admin-form {
        background: none !important;
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
    }
    
    .mwm-frontend-form-wrapper input,
    .mwm-frontend-form-wrapper select,
    .mwm-frontend-form-wrapper textarea {
        width: 100% !important;
        padding: 10px 12px !important;
        border: 1px solid #ddd !important;
        border-radius: 4px !important;
        margin-bottom: 15px !important;
        font-size: 14px !important;
    }
    
    .mwm-frontend-form-wrapper label {
        display: block !important;
        margin-bottom: 5px !important;
        font-weight: 500 !important;
        color: #333 !important;
    }
    
    .mwm-frontend-form-wrapper button[type=\"submit\"],
    .mwm-frontend-form-wrapper input[type=\"submit\"] {
        background: #007cba !important;
        color: white !important;
        padding: 12px 24px !important;
        border: none !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        font-weight: 500 !important;
        font-size: 14px !important;
        width: auto !important;
        margin-top: 10px !important;
    }
    
    .mwm-frontend-form-wrapper button[type=\"submit\"]:hover,
    .mwm-frontend-form-wrapper input[type=\"submit\"]:hover {
        background: #005a87 !important;
    }
    
    .mwm-frontend-form-wrapper button[type=\"submit\"]:disabled,
    .mwm-frontend-form-wrapper input[type=\"submit\"]:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }
    
    .mwm-frontend-form-wrapper .error {
        color: #dc3232 !important;
        font-size: 13px !important;
        margin-top: 5px !important;
    }
    
    .mwm-frontend-form-wrapper .success {
        color: #46b450 !important;
        font-size: 13px !important;
        margin-top: 5px !important;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .mwm-frontend-form-wrapper {
            padding: 15px 0;
        }
        
        .mwm-frontend-form-wrapper input,
        .mwm-frontend-form-wrapper select,
        .mwm-frontend-form-wrapper textarea {
            padding: 8px 10px !important;
            font-size: 16px !important; /* Evita zoom su iOS */
        }
    }
    </style>
    ";
    
    return $html . $js;
}

/**
 * Hook per personalizzazioni aggiuntive
 */
add_action('wp_ajax_mwm_frontend_form_custom_action', 'mwm_handle_custom_form_action');

function mwm_handle_custom_form_action() {
    // Handler per azioni personalizzate dei form se necessario in futuro
    // Per ora è un placeholder
    
    wp_send_json_success('Custom action handled');
}

/**
 * Filtro per personalizzare messaggi di successo
 */
add_filter('frontend_admin_success_message', function($message, $form_id) {
    $custom_messages = array(
        '110' => 'Tablet modificato con successo!',
        '204' => 'Nuovo tablet aggiunto con successo!',
        '125' => 'Movimento registrato con successo!'
    );
    
    return isset($custom_messages[$form_id]) ? $custom_messages[$form_id] : $message;
}, 10, 2);

/**
 * Filtro per personalizzare messaggi di errore
 */
add_filter('frontend_admin_error_message', function($message, $errors, $form_id) {
    if (!empty($errors)) {
        $error_list = '<ul>';
        foreach ($errors as $field => $error) {
            $error_list .= '<li>' . esc_html($error) . '</li>';
        }
        $error_list .= '</ul>';
        
        return 'Errori di validazione:' . $error_list;
    }
    
    return $message;
}, 10, 3);
