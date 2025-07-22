<?php
/**
 * Logica per la dashboard principale dei tablet
 * 
 * Contiene lo shortcode [tablet_dashboard] e tutte le funzioni
 * per visualizzare e gestire la tabella dei tablet con form modal integrate.
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode principale per la dashboard tablet
 */
function mwm_tablet_dashboard_shortcode($atts) {
    // Gestisci azioni di gruppo se inviate
    $message = mwm_handle_dashboard_actions();
    
    // Ottieni tutti i tablet
    $tablets = mwm_get_all_tablets_for_dashboard();
    
    // Genera HTML della dashboard
    ob_start();
    ?>
    <div class="mwm-dashboard-wrapper">
        
        <?php if ($message): ?>
            <div class="mwm-message <?php echo esc_attr($message['type']); ?>">
                <?php echo esc_html($message['text']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Azioni Rapide -->
        <div class="mwm-quick-actions" style="margin-bottom: 20px;">
            <button class="mwm-btn mwm-btn-primary" id="mwm-add-tablet-btn">
                ➕ Aggiungi Nuovo Tablet
            </button>
        </div>
        
        <!-- Azioni di Gruppo -->
        <div class="mwm-bulk-actions">
            <form method="post" id="mwm-bulk-form">
                <?php wp_nonce_field('mwm_bulk_action', 'mwm_bulk_nonce'); ?>
                
                <div class="mwm-bulk-controls">
                    <select name="bulk_action" id="mwm-bulk-action">
                        <option value="">Seleziona azione di gruppo</option>
                        <option value="kiosk_on">Attiva Modalità Kiosk</option>
                        <option value="kiosk_off">Disattiva Modalità Kiosk</option>
                        <option value="sim_active_on">Attiva SIM</option>
                        <option value="sim_active_off">Disattiva SIM</option>
                    </select>
                    
                    <button type="submit" class="mwm-btn mwm-btn-secondary" disabled>
                        Applica a Selezionati
                    </button>
                    
                    <span class="mwm-selected-count">0 tablet selezionati</span>
                </div>
        </div>
        
        <!-- Statistiche Rapide -->
        <div class="mwm-stats">
            <?php echo mwm_generate_tablet_stats($tablets); ?>
        </div>
        
        <!-- Tabella Tablet -->
        <div class="mwm-table-wrapper">
            <table class="mwm-tablets-table" id="mwm-tablets-table">
                <thead>
                    <tr>
                        <th class="mwm-checkbox-col">
                            <input type="checkbox" id="mwm-select-all">
                        </th>
                        <th>ID Tablet</th>
                        <th>IMEI</th>
                        <th>Stato</th>
                        <th>Destinazione</th>
                        <th>Tipologia</th>
                        <th>Modalità Kiosk</th>
                        <th>SIM</th>
                        <th class="mwm-actions-col">Operazioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tablets)): ?>
                        <tr>
                            <td colspan="9" class="mwm-no-data">
                                Nessun tablet trovato. <button class="mwm-btn mwm-btn-primary" onclick="WarehouseManager.openAddTabletModal()">Aggiungi il primo tablet</button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tablets as $tablet): ?>
                            <?php echo mwm_generate_tablet_row($tablet); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        </form> <!-- Chiusura form bulk actions -->
    </div>
    
    <!-- Modal per visualizzazione dettagli -->
    <div id="mwm-tablet-modal" class="mwm-modal" style="display: none;">
        <div class="mwm-modal-content">
            <div class="mwm-modal-header">
                <h3>Dettagli Tablet</h3>
                <span class="mwm-modal-close">&times;</span>
            </div>
            <div class="mwm-modal-body">
                <div class="mwm-loading">Caricamento...</div>
            </div>
        </div>
    </div>
    
    <!-- Modal per modifica tablet -->
    <div id="mwm-edit-tablet-modal" class="mwm-modal" style="display: none;">
        <div class="mwm-modal-content mwm-modal-form">
            <div class="mwm-modal-header">
                <h3>Modifica Tablet</h3>
                <span class="mwm-modal-close">&times;</span>
            </div>
            <div class="mwm-modal-body">
                <!-- Form Frontend Admin viene caricata qui -->
            </div>
        </div>
    </div>
    
    <!-- Modal per registrare movimento -->
    <div id="mwm-movement-modal" class="mwm-modal" style="display: none;">
        <div class="mwm-modal-content mwm-modal-form">
            <div class="mwm-modal-header">
                <h3>Registra Movimento</h3>
                <span class="mwm-modal-close">&times;</span>
            </div>
            <div class="mwm-modal-body">
                <!-- Form Frontend Admin viene caricata qui -->
            </div>
        </div>
    </div>
    
    <!-- Modal per aggiungere nuovo tablet -->
    <div id="mwm-add-tablet-modal" class="mwm-modal" style="display: none;">
        <div class="mwm-modal-content mwm-modal-form">
            <div class="mwm-modal-header">
                <h3>Aggiungi Nuovo Tablet</h3>
                <span class="mwm-modal-close">&times;</span>
            </div>
            <div class="mwm-modal-body">
                <!-- Form Frontend Admin viene caricata qui -->
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * Ottiene tutti i tablet per la dashboard
 */
function mwm_get_all_tablets_for_dashboard() {
    $args = array(
        'post_type' => 'tablet',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    return get_posts($args);
}

/**
 * Genera riga singola tablet per la tabella
 */
function mwm_generate_tablet_row($tablet) {
    $tablet_id = $tablet->ID;
    
    // Ottieni dati ACF
    $imei = get_field('imei_tablet', $tablet_id) ?: '-';
    $stato = get_field('stato_dispositivo', $tablet_id) ?: 'Non definito';
    
    // Ottieni destinazione dalla tassonomia unificata
    $destinazione_term = get_field('dove', $tablet_id);
    $destinazione = $destinazione_term ? $destinazione_term->name : 'Non assegnato';
    
    $tipologia = get_field('tipologia', $tablet_id) ?: '-';
    $modalita_kiosk = get_field('modalita_kiosk', $tablet_id);
    $sim_inserita = get_field('sim_inserita', $tablet_id);
    $sim_attiva = get_field('sim_attiva', $tablet_id);
    
    // Formatta dati per visualizzazione
    $kiosk_display = $modalita_kiosk ? 'Attiva' : 'Disattiva';
    $kiosk_class = $modalita_kiosk ? 'status-active' : 'status-inactive';
    
    $sim_display = mwm_format_sim_status($sim_inserita, $sim_attiva);
    $sim_class = mwm_get_sim_status_class($sim_inserita, $sim_attiva);
    
    $stato_class = 'status-' . sanitize_html_class(strtolower($stato));
    
    ob_start();
    ?>
    <tr data-tablet-id="<?php echo esc_attr($tablet_id); ?>">
        <td class="mwm-checkbox-col">
            <input type="checkbox" name="tablet_ids[]" value="<?php echo esc_attr($tablet_id); ?>" class="mwm-tablet-checkbox">
        </td>
        <td class="mwm-tablet-id">
            <strong><?php echo esc_html($tablet->post_title); ?></strong>
        </td>
        <td><?php echo esc_html($imei); ?></td>
        <td>
            <span class="mwm-status <?php echo esc_attr($stato_class); ?>">
                <?php echo esc_html($stato); ?>
            </span>
        </td>
        <td class="mwm-location">
            <?php echo esc_html($destinazione); ?>
        </td>
        <td><?php echo esc_html($tipologia); ?></td>
        <td>
            <span class="mwm-status <?php echo esc_attr($kiosk_class); ?>">
                <?php echo esc_html($kiosk_display); ?>
            </span>
        </td>
        <td>
            <span class="mwm-status <?php echo esc_attr($sim_class); ?>">
                <?php echo esc_html($sim_display); ?>
            </span>
        </td>
        <td class="mwm-actions-col">
            <div class="mwm-actions">
                <button class="mwm-btn mwm-btn-small mwm-btn-view" data-tablet-id="<?php echo esc_attr($tablet_id); ?>" title="Visualizza dettagli">
                    👁️
                </button>
                <button class="mwm-btn mwm-btn-small mwm-btn-edit" data-tablet-id="<?php echo esc_attr($tablet_id); ?>" title="Modifica tablet">
                    ✏️
                </button>
                <button class="mwm-btn mwm-btn-small mwm-btn-movement" data-tablet-id="<?php echo esc_attr($tablet_id); ?>" title="Registra movimento">
                    📦
                </button>
            </div>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

/**
 * Genera statistiche rapide per la dashboard
 */
function mwm_generate_tablet_stats($tablets) {
    $stats = array(
        'totale' => count($tablets),
        'disponibili' => 0,
        'assegnati' => 0,
        'in_manutenzione' => 0,
        'kiosk_attivo' => 0,
        'sim_attive' => 0
    );
    
    foreach ($tablets as $tablet) {
        $stato = get_field('stato_dispositivo', $tablet->ID);
        $modalita_kiosk = get_field('modalita_kiosk', $tablet->ID);
        $sim_attiva = get_field('sim_attiva', $tablet->ID);
        
        // Conta per stato
        switch (strtolower($stato)) {
            case 'disponibile':
                $stats['disponibili']++;
                break;
            case 'assegnato':
                $stats['assegnati']++;
                break;
            case 'in_manutenzione':
                $stats['in_manutenzione']++;
                break;
        }
        
        if ($modalita_kiosk) {
            $stats['kiosk_attivo']++;
        }
        
        if ($sim_attiva) {
            $stats['sim_attive']++;
        }
    }
    
    ob_start();
    ?>
    <div class="mwm-stats-grid">
        <div class="mwm-stat-card">
            <div class="mwm-stat-number"><?php echo $stats['totale']; ?></div>
            <div class="mwm-stat-label">Tablet Totali</div>
        </div>
        <div class="mwm-stat-card mwm-stat-available">
            <div class="mwm-stat-number"><?php echo $stats['disponibili']; ?></div>
            <div class="mwm-stat-label">Disponibili</div>
        </div>
        <div class="mwm-stat-card mwm-stat-assigned">
            <div class="mwm-stat-number"><?php echo $stats['assegnati']; ?></div>
            <div class="mwm-stat-label">Assegnati</div>
        </div>
        <div class="mwm-stat-card mwm-stat-maintenance">
            <div class="mwm-stat-number"><?php echo $stats['in_manutenzione']; ?></div>
            <div class="mwm-stat-label">In Manutenzione</div>
        </div>
        <div class="mwm-stat-card mwm-stat-kiosk">
            <div class="mwm-stat-number"><?php echo $stats['kiosk_attivo']; ?></div>
            <div class="mwm-stat-label">Kiosk Attivo</div>
        </div>
        <div class="mwm-stat-card mwm-stat-sim">
            <div class="mwm-stat-number"><?php echo $stats['sim_attive']; ?></div>
            <div class="mwm-stat-label">SIM Attive</div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Gestisce le azioni della dashboard (azioni di gruppo)
 */
function mwm_handle_dashboard_actions() {
    if (!isset($_POST['mwm_bulk_nonce']) || !wp_verify_nonce($_POST['mwm_bulk_nonce'], 'mwm_bulk_action')) {
        return null;
    }
    
    $action = sanitize_text_field($_POST['bulk_action']);
    $tablet_ids = isset($_POST['tablet_ids']) ? array_map('intval', $_POST['tablet_ids']) : array();
    
    if (empty($action) || empty($tablet_ids)) {
        return array('type' => 'error', 'text' => 'Seleziona un\'azione e almeno un tablet');
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
                // Se attiviamo SIM, deve essere anche inserita
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
    
    $action_labels = array(
        'kiosk_on' => 'Modalità Kiosk attivata',
        'kiosk_off' => 'Modalità Kiosk disattivata', 
        'sim_active_on' => 'SIM attivata',
        'sim_active_off' => 'SIM disattivata'
    );
    
    $action_label = isset($action_labels[$action]) ? $action_labels[$action] : 'Azione eseguita';
    
    return array(
        'type' => 'success',
        'text' => sprintf('%s per %d tablet su %d selezionati', $action_label, $updated_count, count($tablet_ids))
    );
}

/**
 * Formatta stato SIM per visualizzazione
 */
function mwm_format_sim_status($sim_inserita, $sim_attiva) {
    if (!$sim_inserita) {
        return 'Non inserita';
    }
    
    if ($sim_attiva) {
        return 'Inserita e Attiva';
    }
    
    return 'Inserita ma Non Attiva';
}

/**
 * Ottiene classe CSS per stato SIM
 */
function mwm_get_sim_status_class($sim_inserita, $sim_attiva) {
    if (!$sim_inserita) {
        return 'status-inactive';
    }
    
    if ($sim_attiva) {
        return 'status-active';
    }
    
    return 'status-partial';
}
