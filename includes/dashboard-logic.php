<?php
/**
 * Logica per la dashboard principale dei tablet
 * 
 * Contiene lo shortcode [tablet_dashboard] e tutte le funzioni
 * per visualizzare e gestire la tabella dei tablet.
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode principale per la dashboard tablet
 */
function mwm_tablet_dashboard_shortcode($atts) {
    // Verifica permessi
    if (!current_user_can('administrator')) {
        return '<div class="mwm-error">Accesso negato. È richiesto il login come amministratore.</div>';
    }
    
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
                        <th>Ubicazione Attuale</th>
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
                                Nessun tablet trovato. <a href="/aggiungi-nuovo-tablet/">Aggiungi il primo tablet</a>
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
    
    // Ottieni ubicazione dalla tassonomia unificata 'destinazione'
    $ubicazione_term = get_field('dove', $tablet_id);
    $ubicazione = $ubicazione_term ? $ubicazione_term->name : 'Non assegnato';
    
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
            <?php echo esc_html($ubicazione); ?>
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
                <a href="/modifica-tablet/?post_id=<?php echo esc_attr($tablet_id); ?>" class="mwm-btn mwm-btn-small mwm-btn-edit" title="Modifica tablet">
                    ✏️
                </a>
                <a href="/esegui-movimento/?post_id=<?php echo esc_attr($tablet_id); ?>" class="mwm-btn mwm-btn-small mwm-btn-movement" title="Registra movimento">
                    📦
                </a>
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
            case 'in manutenzione':
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
    
    if (!current_user_can('administrator')) {
        return array('type' => 'error', 'text' => 'Permessi insufficienti');
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

/**
 * Shortcode per la cronologia movimenti [movimenti_history_table]
 */
function mwm_movimenti_history_shortcode($atts) {
    // Verifica permessi
    if (!current_user_can('administrator')) {
        return '<div class="mwm-error">Accesso negato. È richiesto il login come amministratore.</div>';
    }
    
    // Attributi shortcode
    $atts = shortcode_atts(array(
        'per_page' => 20,
        'orderby' => 'date',
        'order' => 'DESC'
    ), $atts);
    
    // Gestisci paginazione
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;
    
    // Gestisci filtri da GET
    $tablet_filter = isset($_GET['tablet_search']) ? sanitize_text_field($_GET['tablet_search']) : '';
    $tipo_filter = isset($_GET['tipo_movimento']) ? sanitize_text_field($_GET['tipo_movimento']) : '';
    
    // Ottieni movimenti
    $movimenti_data = mwm_get_movimenti_for_history($atts, $paged, $tablet_filter, $tipo_filter);
    
    ob_start();
    ?>
    <div class="mwm-history-wrapper">
        
        <!-- Filtri -->
        <div class="mwm-history-filters">
            <form method="GET" class="mwm-filters-form">
                <input 
                    type="text" 
                    name="tablet_search" 
                    placeholder="Cerca per ID Tablet..." 
                    value="<?php echo esc_attr($tablet_filter); ?>"
                    class="mwm-history-filter"
                    data-filter-type="tablet"
                >
                
                <select name="tipo_movimento" class="mwm-history-filter" data-filter-type="tipo">
                    <option value="">Tutti i tipi movimento</option>
                    <?php 
                    $tipo_options = mwm_get_tipo_movimento_options();
                    foreach ($tipo_options as $value => $label) {
                        $selected = selected($tipo_filter, $value, false);
                        echo "<option value='{$value}'{$selected}>{$label}</option>";
                    }
                    ?>
                </select>
                
                <button type="submit" class="mwm-btn mwm-btn-primary">Filtra</button>
                <a href="?" class="mwm-btn mwm-btn-secondary">Reset</a>
            </form>
        </div>
        
        <!-- Statistiche Rapide -->
        <div class="mwm-history-stats">
            <?php echo mwm_generate_movimenti_stats($movimenti_data['total']); ?>
        </div>
        
        <!-- Tabella -->
        <div class="mwm-table-wrapper">
            <table class="mwm-history-table">
                <thead>
                    <tr>
                        <th>Data Movimento</th>
                        <th>Tipo Movimento</th>
                        <th>ID Tablet</th>
                        <th>Referente</th>
                        <th>Destinazione Interna</th>
                        <th>Progetto Esterno</th>
                        <th>Documento</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimenti_data['movimenti'])): ?>
                        <tr>
                            <td colspan="8" class="mwm-no-data">
                                <?php if ($tablet_filter || $tipo_filter): ?>
                                    Nessun movimento trovato con i filtri applicati.
                                    <br><a href="?">Visualizza tutti i movimenti</a>
                                <?php else: ?>
                                    Nessun movimento registrato.
                                    <br><a href="/magazzino-tablet/">Torna alla dashboard</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimenti_data['movimenti'] as $movimento): ?>
                            <?php echo mwm_generate_movimento_row($movimento); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginazione -->
        <?php if ($movimenti_data['total_pages'] > 1): ?>
            <div class="mwm-pagination">
                <?php echo mwm_generate_pagination($movimenti_data['current_page'], $movimenti_data['total_pages']); ?>
            </div>
        <?php endif; ?>
        
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Ottiene movimenti per la cronologia con filtri e paginazione
 */
function mwm_get_movimenti_for_history($atts, $paged, $tablet_filter = '', $tipo_filter = '') {
    $per_page = intval($atts['per_page']);
    
    $meta_query = array();
    
    // Filtro per tablet
    if ($tablet_filter) {
        // Trova tablet che corrispondono al filtro
        $tablets = get_posts(array(
            'post_type' => 'tablet',
            's' => $tablet_filter,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        if (!empty($tablets)) {
            $meta_query[] = array(
                'key' => 'tablet_coinvolto',
                'value' => $tablets,
                'compare' => 'IN'
            );
        } else {
            // Nessun tablet trovato, restituisci risultati vuoti
            return array(
                'movimenti' => array(),
                'total' => 0,
                'total_pages' => 0,
                'current_page' => 1
            );
        }
    }
    
    // Filtro per tipo movimento
    if ($tipo_filter) {
        $meta_query[] = array(
            'key' => 'tipo_di_movimento',
            'value' => $tipo_filter,
            'compare' => '='
        );
    }
    
    $args = array(
        'post_type' => 'movimento_magazzino',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
        'meta_query' => $meta_query
    );
    
    $query = new WP_Query($args);
    
    return array(
        'movimenti' => $query->posts,
        'total' => $query->found_posts,
        'total_pages' => $query->max_num_pages,
        'current_page' => $paged
    );
}

/**
 * Genera riga singola movimento per la cronologia
 */
function mwm_generate_movimento_row($movimento) {
    $movimento_id = $movimento->ID;
    
    // Ottieni campi ACF
    $tipo_movimento = get_field('tipo_di_movimento', $movimento_id) ?: '-';
    $data_movimento = get_field('data_movimento', $movimento_id);
    $tablet_coinvolto = get_field('tablet_coinvolto', $movimento_id);
    $referente = get_field('nome_referente_assegnatario', $movimento_id) ?: '-';
    $destinazione_interna = get_field('destinazione_interna', $movimento_id);
    $progetto_esterno = get_field('progetto_esterno', $movimento_id);
    $documento = get_field('documento_di_consegna_ise', $movimento_id);
    $note = get_field('note_movimento', $movimento_id) ?: '-';
    
    // Formatta dati
    $data_formattata = $data_movimento ? date('d/m/Y', strtotime($data_movimento)) : '-';
    $tablet_title = $tablet_coinvolto ? $tablet_coinvolto->post_title : '-';
    $tablet_id = $tablet_coinvolto ? $tablet_coinvolto->ID : null;
    
    $destinazione_nome = $destinazione_interna ? $destinazione_interna->name : '-';
    $progetto_nome = $progetto_esterno ? $progetto_esterno->name : '-';
    
    $tipo_class = 'status-' . sanitize_html_class(strtolower($tipo_movimento));
    
    ob_start();
    ?>
    <tr data-movimento-id="<?php echo esc_attr($movimento_id); ?>">
        <td><?php echo esc_html($data_formattata); ?></td>
        <td>
            <span class="mwm-status <?php echo esc_attr($tipo_class); ?>">
                <?php echo esc_html(ucfirst(str_replace('_', ' ', $tipo_movimento))); ?>
            </span>
        </td>
        <td>
            <?php if ($tablet_id): ?>
                <a href="/visualizza-tablet/?post_id=<?php echo esc_attr($tablet_id); ?>" class="mwm-tablet-link">
                    <strong><?php echo esc_html($tablet_title); ?></strong>
                </a>
            <?php else: ?>
                <?php echo esc_html($tablet_title); ?>
            <?php endif; ?>
        </td>
        <td><?php echo esc_html($referente); ?></td>
        <td><?php echo esc_html($destinazione_nome); ?></td>
        <td><?php echo esc_html($progetto_nome); ?></td>
        <td>
            <?php if ($documento): ?>
                <a href="<?php echo esc_url($documento['url']); ?>" 
                   class="mwm-document-link" 
                   target="_blank" 
                   title="Visualizza documento">
                    📄 <?php echo esc_html($documento['filename']); ?>
                </a>
            <?php else: ?>
                -
            <?php endif; ?>
        </td>
        <td>
            <?php if (strlen($note) > 50): ?>
                <span title="<?php echo esc_attr($note); ?>">
                    <?php echo esc_html(substr($note, 0, 50) . '...'); ?>
                </span>
            <?php else: ?>
                <?php echo esc_html($note); ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

/**
 * Genera statistiche per la cronologia movimenti
 */
function mwm_generate_movimenti_stats($total_movimenti) {
    // Ottieni statistiche degli ultimi 30 giorni
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    
    $recent_movimenti = get_posts(array(
        'post_type' => 'movimento_magazzino',
        'meta_query' => array(
            array(
                'key' => 'data_movimento',
                'value' => $thirty_days_ago,
                'compare' => '>='
            )
        ),
        'posts_per_page' => -1
    ));
    
    $stats_by_type = array();
    foreach ($recent_movimenti as $movimento) {
        $tipo = get_field('tipo_di_movimento', $movimento->ID);
        $stats_by_type[$tipo] = ($stats_by_type[$tipo] ?? 0) + 1;
    }
    
    ob_start();
    ?>
    <div class="mwm-stats-grid" style="margin-bottom: 20px;">
        <div class="mwm-stat-card">
            <div class="mwm-stat-number"><?php echo $total_movimenti; ?></div>
            <div class="mwm-stat-label">Movimenti Totali</div>
        </div>
        <div class="mwm-stat-card">
            <div class="mwm-stat-number"><?php echo count($recent_movimenti); ?></div>
            <div class="mwm-stat-label">Ultimi 30 Giorni</div>
        </div>
        <?php foreach (array_slice($stats_by_type, 0, 4, true) as $tipo => $count): ?>
            <div class="mwm-stat-card">
                <div class="mwm-stat-number"><?php echo $count; ?></div>
                <div class="mwm-stat-label"><?php echo ucfirst(str_replace('_', ' ', $tipo)); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Genera paginazione per cronologia
 */
function mwm_generate_pagination($current_page, $total_pages) {
    $pagination_links = array();
    
    // Bottone precedente
    if ($current_page > 1) {
        $prev_link = get_pagenum_link($current_page - 1);
        $pagination_links[] = '<a href="' . esc_url($prev_link) . '">&laquo; Precedente</a>';
    }
    
    // Numeri pagina
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $pagination_links[] = '<a href="' . esc_url(get_pagenum_link(1)) . '">1</a>';
        if ($start > 2) {
            $pagination_links[] = '<span>...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $pagination_links[] = '<span class="current">' . $i . '</span>';
        } else {
            $pagination_links[] = '<a href="' . esc_url(get_pagenum_link($i)) . '">' . $i . '</a>';
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $pagination_links[] = '<span>...</span>';
        }
        $pagination_links[] = '<a href="' . esc_url(get_pagenum_link($total_pages)) . '">' . $total_pages . '</a>';
    }
    
    // Bottone successivo
    if ($current_page < $total_pages) {
        $next_link = get_pagenum_link($current_page + 1);
        $pagination_links[] = '<a href="' . esc_url($next_link) . '">Successivo &raquo;</a>';
    }
    
    return implode('', $pagination_links);
}