<?php
/**
 * Integrazione con Frontend Admin by DynamiApps
 * 
 * Gestisce hook, filtri e logica personalizzata per i form
 * di modifica tablet e registrazione movimenti tramite Frontend Admin.
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
    add_action('frontend_admin/forms/new_post/after_save', 'mwm_handle_movimento_create_save', 10, 3);
    
    // Hook per pre-popolare campi
    add_filter('frontend_admin/forms/load_value', 'mwm_prepopulate_form_fields', 10, 4);
    
    // Hook per custom field rendering
    add_filter('frontend_admin/fields/render_field', 'mwm_render_custom_fields', 10, 3);
    
    // Hook per gestire submit personalizzato
    add_action('wp_ajax_mwm_frontend_form_submit', 'mwm_handle_frontend_form_submit');
    add_action('wp_ajax_nopriv_mwm_frontend_form_submit', 'mwm_handle_frontend_form_submit');
}

// Inizializza integrazione
mwm_init_frontend_admin_integration();

/**
 * Valida i form Frontend Admin personalizzati
 */
function mwm_validate_frontend_forms($errors, $form_data, $form_id) {
    // Validazione per form modifica tablet
    if (strpos($form_id, 'edit_tablet') !== false) {
        $errors = array_merge($errors, mwm_validate_tablet_form($form_data));
    }
    
    // Validazione per form movimento
    if (strpos($form_id, 'new_movimento') !== false) {
        $errors = array_merge($errors, mwm_validate_movimento_form($form_data));
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
    
    // Validazione ubicazione_attuale_tablet -> aggiornato a 'dove'
    if (isset($form_data['dove']) && empty($form_data['dove'])) {
        $errors['dove'] = 'Specificare ubicazione attuale del tablet';
    }
    
    return $errors;
}

/**
 * Valida form di nuovo movimento
 */
function mwm_validate_movimento_form($form_data) {
    $errors = array();
    
    // Validazione tipo movimento
    $valid_types = array_keys(mwm_get_tipo_movimento_options());
    if (isset($form_data['tipo_di_movimento']) && 
        !in_array($form_data['tipo_di_movimento'], $valid_types)) {
        $errors['tipo_di_movimento'] = 'Tipo di movimento non valido';
    }
    
    // Validazione tablet coinvolto
    if (isset($form_data['tablet_coinvolto']) && 
        !mwm_is_valid_tablet_id($form_data['tablet_coinvolto'])) {
        $errors['tablet_coinvolto'] = 'Tablet selezionato non valido';
    }
    
    // Validazione data movimento (non può essere futura)
    if (isset($form_data['data_movimento'])) {
        $movimento_date = strtotime($form_data['data_movimento']);
        $today = strtotime('today');
        
        if ($movimento_date > $today) {
            $errors['data_movimento'] = 'La data movimento non può essere futura';
        }
    }
    
    // Validazione condizionale per referente
    $requires_referente = array('assegnazione', 'vendita', 'assegnazione_affitto');
    if (isset($form_data['tipo_di_movimento']) && 
        in_array($form_data['tipo_di_movimento'], $requires_referente) &&
        empty($form_data['nome_referente_assegnatario'])) {
        $errors['nome_referente_assegnatario'] = 'Nome referente richiesto per questo tipo di movimento';
    }
    
    return $errors;
}

/**
 * Gestisce salvataggio form modifica tablet
 */
function mwm_handle_tablet_edit_save($post_id, $form_data, $form_id) {
    if (get_post_type($post_id) !== 'tablet') {
        return;
    }
    
    // Log della modifica
    mwm_log("Tablet modificato: ID {$post_id}", 'info');
    
    // Aggiorna timestamp ultima modifica
    update_field('ultima_modifica', current_time('mysql'), $post_id);
    
    // Gestione logica speciale per SIM
    mwm_handle_sim_logic($post_id, $form_data);
    
    // Aggiorna ubicazione se necessario -> aggiornato a 'dove'
    if (isset($form_data['dove'])) {
        update_field('dove', $form_data['dove'], $post_id);
    }
    
    // Hook personalizzato per dopo il salvataggio tablet
    do_action('mwm_after_tablet_save', $post_id, $form_data);
}

/**
 * Gestisce salvataggio form nuovo movimento
 */
function mwm_handle_movimento_create_save($post_id, $form_data, $form_id) {
    if (get_post_type($post_id) !== 'movimento_magazzino') {
        return;
    }
    
    // Genera titolo automatico per il movimento
    $tablet_id = $form_data['tablet_coinvolto'] ?? null;
    $data_movimento = $form_data['data_movimento'] ?? current_time('Y-m-d');
    
    if ($tablet_id) {
        $movimento_title = mwm_generate_movimento_id($tablet_id);
        wp_update_post(array(
            'ID' => $post_id,
            'post_title' => $movimento_title
        ));
    }
    
    // Aggiorna stato del tablet se specificato
    if (isset($form_data['nuovo_stato_tablet']) && $tablet_id) {
        update_field('stato_dispositivo', $form_data['nuovo_stato_tablet'], $tablet_id);
        
        // Aggiorna anche ubicazione del tablet se il movimento la cambia
        mwm_update_tablet_location_from_movimento($tablet_id, $form_data);
    }
    
    // Log del movimento
    $tipo_movimento = $form_data['tipo_di_movimento'] ?? 'non specificato';
    mwm_log("Movimento registrato: ID {$post_id}, Tipo: {$tipo_movimento}, Tablet: {$tablet_id}", 'info');
    
    // Hook personalizzato per dopo il salvataggio movimento
    do_action('mwm_after_movimento_save', $post_id, $form_data, $tablet_id);
}

/**
 * Pre-popola campi dei form Frontend Admin
 */
function mwm_prepopulate_form_fields($value, $field_name, $post_id, $form_id) {
    // Pre-popola tablet_coinvolto nei form movimento con ID dall'URL
    if ($field_name === 'tablet_coinvolto' && isset($_GET['post_id'])) {
        $tablet_id = intval($_GET['post_id']);
        if (mwm_is_valid_tablet_id($tablet_id)) {
            return $tablet_id;
        }
    }
    
    // Pre-popola data movimento con data corrente
    if ($field_name === 'data_movimento' && empty($value)) {
        return current_time('Y-m-d');
    }
    
    // Pre-popola ubicazione attuale per modifica tablet -> aggiornato a 'dove'
    if ($field_name === 'dove' && $post_id) {
        $current_location = get_field('dove', $post_id);
        return $current_location ? $current_location->term_id : '';
    }
    
    return $value;
}

/**
 * Renderizza campi personalizzati per Frontend Admin
 */
function mwm_render_custom_fields($html, $field, $form_id) {
    // Renderizza campo select per ubicazione con opzioni dinamiche
    if ($field['name'] === 'ubicazione_attuale_tablet') {
        $options = mwm_get_ubicazione_options_for_frontend();
        $current_value = $field['value'] ?? '';
        
        $select_html = '<select name="' . esc_attr($field['name']) . '" id="' . esc_attr($field['name']) . '" class="mwm-location-select">';
        
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
 * Gestisce logica speciale per i campi SIM
 */
function mwm_handle_sim_logic($tablet_id, $form_data) {
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
 * Aggiorna ubicazione del tablet basata sul movimento
 */
function mwm_update_tablet_location_from_movimento($tablet_id, $movimento_data) {
    $tipo_movimento = $movimento_data['tipo_di_movimento'] ?? '';
    $new_location = '';
    
    switch ($tipo_movimento) {
        case 'assegnazione':
            // Usa destinazione interna
            if (isset($movimento_data['destinazione_interna']) && $movimento_data['destinazione_interna']) {
                $term = get_term($movimento_data['destinazione_interna']);
                $new_location = $term ? $term->name : '';
            }
            break;
            
        case 'vendita':
        case 'assegnazione_affitto':
            // Usa progetto esterno
            if (isset($movimento_data['progetto_esterno']) && $movimento_data['progetto_esterno']) {
                $term = get_term($movimento_data['progetto_esterno']);
                $new_location = $term ? $term->name : '';
            }
            break;
            
        case 'rientro':
            // Il tablet rientra, quindi non è più assegnato
            $new_location = '';
            break;
            
        case 'manutenzione':
            // Il tablet è in manutenzione
            $new_location = 'In Manutenzione';
            break;
    }
    
    if ($new_location !== '') {
        update_field('ubicazione_attuale_tablet', $new_location, $tablet_id);
    }
}

/**
 * Gestisce salvataggio form movimento con logica duplice
 */
function mwm_handle_movimento_form_submission($post_id, $form_data, $form_id) {
    // Controlla se è il form movimento (ID 127)
    if ($form_id !== 'movimento' && $form_id !== '127') {
        return;
    }
    
    // Log dell'azione
    mwm_log("Form movimento sottomesso per tablet ID: {$post_id}", 'info');
    
    // 1. Aggiorna il tablet (già gestito automaticamente da Frontend Admin)
    // Solo aggiungiamo log e validazioni extra
    
    // 2. Crea nuovo movimento_magazzino
    $movimento_data = array(
        'post_type' => 'movimento_magazzino',
        'post_status' => 'publish',
        'post_title' => mwm_generate_movimento_title_from_tablet($post_id)
    );
    
    $movimento_id = wp_insert_post($movimento_data);
    
    if (is_wp_error($movimento_id)) {
        mwm_log("Errore nella creazione movimento: " . $movimento_id->get_error_message(), 'error');
        return;
    }
    
    // 3. Salva tutti i campi ACF nel movimento
    mwm_save_movimento_fields($movimento_id, $post_id, $form_data);
    
    // 4. Aggiorna ubicazione tablet basata sul movimento
    mwm_update_tablet_location_from_new_movimento($post_id, $form_data);
    
    // Hook personalizzato
    do_action('mwm_after_movimento_creation', $movimento_id, $post_id, $form_data);
    
    mwm_log("Movimento creato con successo ID: {$movimento_id} per tablet: {$post_id}", 'info');
}

/**
 * Genera titolo per il movimento basato sul tablet
 */
function mwm_generate_movimento_title_from_tablet($tablet_id) {
    $tablet = get_post($tablet_id);
    $tablet_title = $tablet ? $tablet->post_title : 'Unknown';
    $date = current_time('Y-m-d H:i');
    
    return sprintf('Movimento %s - %s', $tablet_title, $date);
}

/**
 * Salva i campi ACF nel movimento creato
 */
function mwm_save_movimento_fields($movimento_id, $tablet_id, $form_data) {
    // Campi standard del movimento
    $movimento_fields = array(
        'tablet_coinvolto' => $tablet_id, // Link al tablet
        'data_movimento' => current_time('Y-m-d'),
        'tipo_di_movimento' => 'assegnazione' // Default o da form_data
    );
    
    // Mappa i campi del form ai campi ACF
    if (isset($form_data['dove'])) {
        // Destinazione primaria - mapparla a un campo del movimento
        update_field('dove', $form_data['dove'], $movimento_id);
    }
    
    if (isset($form_data['dove2'])) {
        // Destinazione secondaria  
        update_field('dove2', $form_data['dove2'], $movimento_id);
    }
    
    if (isset($form_data['ddt'])) {
        // Documento DDT
        update_field('documento_di_consegna_ise', $form_data['ddt'], $movimento_id);
    }
    
    // Altri campi dal form
    foreach ($movimento_fields as $field => $value) {
        update_field($field, $value, $movimento_id);
    }
}

/**
 * Aggiorna ubicazione tablet basata sul nuovo movimento
 */
function mwm_update_tablet_location_from_new_movimento($tablet_id, $form_data) {
    // Logica per determinare la nuova ubicazione del tablet
    $new_location_term_id = null;
    
    // Priorità: dove (destinazione primaria) > dove2 (destinazione secondaria)
    if (isset($form_data['dove']) && !empty($form_data['dove'])) {
        $new_location_term_id = $form_data['dove'];
    } elseif (isset($form_data['dove2']) && !empty($form_data['dove2'])) {
        $new_location_term_id = $form_data['dove2'];
    }
    
    if ($new_location_term_id) {
        // Aggiorna il campo 'dove' del tablet con il nuovo termine
        $term = get_term($new_location_term_id);
        if ($term && !is_wp_error($term)) {
            update_field('dove', $term, $tablet_id);
            mwm_log("Ubicazione tablet {$tablet_id} aggiornata a: {$term->name}", 'info');
        }
    }
}

// Hook il form movimento alla funzione
add_action('frontend_admin/forms/edit_post/after_save', 'mwm_handle_movimento_form_submission', 10, 3);

/**
 * Handler per submit form personalizzato (se necessario)
 */
function mwm_handle_frontend_form_submit() {
    // Verifica permessi
    if (!wp_verify_nonce($_POST['nonce'], 'warehouse_manager_nonce') || 
        (!current_user_can('manage_options') && !current_user_can('edit_tablet'))) {
        wp_send_json_error('Accesso non autorizzato');
    }
    
    $form_type = sanitize_text_field($_POST['form_type']);
    $form_data = $_POST['form_data'] ?? array();
    
    switch ($form_type) {
        case 'tablet_edit':
            $result = mwm_process_tablet_edit_form($form_data);
            break;
            
        case 'movimento_create':
            $result = mwm_process_movimento_create_form($form_data);
            break;
            
        default:
            wp_send_json_error('Tipo di form non riconosciuto');
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success($result);
    }
}

/**
 * Processa form di modifica tablet
 */
function mwm_process_tablet_edit_form($form_data) {
    $tablet_id = intval($form_data['tablet_id'] ?? 0);
    
    if (!mwm_is_valid_tablet_id($tablet_id)) {
        return new WP_Error('invalid_tablet', 'ID tablet non valido');
    }
    
    // Sanitizza dati
    $clean_data = mwm_sanitize_form_data($form_data, array(
        'stato_dispositivo' => 'select',
        'modalita_kiosk' => 'bool',
        'imei_tablet' => 'text',
        'sim_inserita' => 'bool',
        'sim_attiva' => 'bool',
        'ubicazione_attuale_tablet' => 'text',
        'tipologia' => 'select',
        'note_generali_tablet' => 'textarea'
    ));
    
    // Aggiorna campi ACF
    foreach ($clean_data as $field => $value) {
        update_field($field, $value, $tablet_id);
    }
    
    return array(
        'success' => true,
        'message' => 'Tablet aggiornato con successo',
        'tablet_id' => $tablet_id
    );
}

/**
 * Processa form di creazione movimento
 */
function mwm_process_movimento_create_form($form_data) {
    // Sanitizza dati
    $clean_data = mwm_sanitize_form_data($form_data, array(
        'tablet_coinvolto' => 'int',
        'tipo_di_movimento' => 'select',
        'data_movimento' => 'date',
        'nome_referente_assegnatario' => 'text',
        'note_movimento' => 'textarea'
    ));
    
    // Crea nuovo movimento
    $movimento_id = wp_insert_post(array(
        'post_type' => 'movimento_magazzino',
        'post_status' => 'publish',
        'post_title' => mwm_generate_movimento_id($clean_data['tablet_coinvolto'])
    ));
    
    if (is_wp_error($movimento_id)) {
        return $movimento_id;
    }
    
    // Aggiorna campi ACF
    foreach ($clean_data as $field => $value) {
        update_field($field, $value, $movimento_id);
    }
    
    return array(
        'success' => true,
        'message' => 'Movimento registrato con successo',
        'movimento_id' => $movimento_id
    );
}