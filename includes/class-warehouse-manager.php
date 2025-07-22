<?php
/**
 * Classe principale per la gestione del magazzino
 * 
 * Gestisce la logica centrale del plugin con tassonomia destinazione unificata.
 * Versione senza controlli di sicurezza per sviluppo.
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

class MWM_Warehouse_Manager {
    
    /**
     * Istanza singleton
     */
    private static $instance = null;
    
    /**
     * Custom post types supportati
     */
    private $post_types = array('tablet');
    
    /**
     * Tassonomie supportate (aggiornate)
     */
    private $taxonomies = array('destinazione');
    
    /**
     * Costruttore privato
     */
    private function __construct() {
        add_action('init', array($this, 'init_hooks'));
    }
    
    /**
     * Restituisce istanza singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inizializza tutti gli hook necessari
     */
    public function init_hooks() {
        // Hook per verificare esistenza CPT e taxonomy
        add_action('wp_loaded', array($this, 'verify_dependencies'));
        
        // Hook per azioni di gruppo (bulk actions)
        add_action('wp_ajax_mwm_bulk_action', array($this, 'handle_bulk_action'));
        
        // Hook per gestione Frontend Admin
        add_action('frontend_admin/forms/edit_post/after_save', array($this, 'handle_tablet_save'), 10, 2);
        add_action('frontend_admin/forms/new_post/after_save', array($this, 'handle_tablet_save'), 10, 2);
    }
    
    /**
     * Verifica che CPT e tassonomie esistano
     */
    public function verify_dependencies() {
        $missing_dependencies = array();
        
        // Verifica CPT
        foreach ($this->post_types as $post_type) {
            if (!post_type_exists($post_type)) {
                $missing_dependencies[] = "Custom Post Type: {$post_type}";
            }
        }
        
        // Verifica tassonomie
        foreach ($this->taxonomies as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                $missing_dependencies[] = "Taxonomy: {$taxonomy}";
            }
        }
        
        // Se mancano dipendenze, mostra errore admin
        if (!empty($missing_dependencies) && is_admin()) {
            add_action('admin_notices', function() use ($missing_dependencies) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>My Warehouse Manager:</strong> Dipendenze mancanti: ' . implode(', ', $missing_dependencies);
                echo '<br>Assicurati che i Custom Post Type e le Tassonomie siano creati in ACF Pro.';
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Gestisce azioni di gruppo sui tablet
     */
    public function handle_bulk_action() {
        $action = sanitize_text_field($_POST['bulk_action']);
        $tablet_ids = array_map('intval', $_POST['tablet_ids']);
        
        if (empty($tablet_ids) || empty($action)) {
            wp_send_json_error('Parametri mancanti');
        }
        
        $updated_count = 0;
        
        foreach ($tablet_ids as $tablet_id) {
            // Verifica che sia effettivamente un tablet
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
                
                // Log dell'operazione
                mwm_log("Bulk action '{$action}' applicata a tablet ID: {$tablet_id}", 'info');
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('Aggiornati %d tablet su %d selezionati', $updated_count, count($tablet_ids)),
            'updated_count' => $updated_count
        ));
    }
    
    /**
     * Gestisce salvataggio tablet via Frontend Admin
     */
    public function handle_tablet_save($post_id, $form) {
        if (get_post_type($post_id) !== 'tablet') {
            return;
        }
        
        // Log del salvataggio
        mwm_log("Tablet salvato via Frontend Admin - ID: {$post_id}", 'info');
        
        // Aggiorna timestamp ultima modifica
        update_field('ultima_modifica', current_time('mysql'), $post_id);
        
        // Gestisci logica speciale per SIM
        $this->handle_sim_logic($post_id);
        
        // Hook personalizzato per dopo il salvataggio tablet
        do_action('mwm_after_tablet_save', $post_id, $form);
    }
    
    /**
     * Gestisce logica condizionale per campi SIM
     */
    private function handle_sim_logic($tablet_id) {
        $sim_inserita = get_field('sim_inserita', $tablet_id);
        $sim_attiva = get_field('sim_attiva', $tablet_id);
        
        // Se SIM attiva è impostata a Sì, assicurati che SIM inserita sia Sì
        if ($sim_attiva && !$sim_inserita) {
            update_field('sim_inserita', 1, $tablet_id);
            mwm_log("Auto-impostata SIM inserita per tablet ID: {$tablet_id}", 'info');
        }
        
        // Se SIM inserita è impostata a No, disattiva anche SIM attiva
        if (!$sim_inserita && $sim_attiva) {
            update_field('sim_attiva', 0, $tablet_id);
            
            // Pulisci campi SIM correlati
            update_field('sn_sim', '', $tablet_id);
            update_field('pin_sim', '', $tablet_id);
            update_field('puk_sim', '', $tablet_id);
            
            mwm_log("Auto-disattivata SIM e puliti dati per tablet ID: {$tablet_id}", 'info');
        }
    }
    
    /**
     * Utility: Ottieni tutti i tablet con filtri
     */
    public function get_tablets($args = array()) {
        $default_args = array(
            'post_type' => 'tablet',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array()
        );
        
        $args = wp_parse_args($args, $default_args);
        
        return get_posts($args);
    }
    
    /**
     * Utility: Filtra tablet per destinazione
     */
    public function get_tablets_by_destination($destination_term_id) {
        $tablets = $this->get_tablets();
        $filtered = array();
        
        foreach ($tablets as $tablet) {
            $tablet_destination = get_field('dove', $tablet->ID);
            
            if ($tablet_destination && is_object($tablet_destination) && $tablet_destination->term_id == $destination_term_id) {
                $filtered[] = $tablet;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Utility: Ottieni opzioni per campo destinazione
     */
    public function get_destinazione_options() {
        $options = array(
            '' => '--- Non Assegnato ---'
        );
        
        // Ottieni tutte le destinazioni dalla tassonomia unificata
        $destinazioni = get_terms(array(
            'taxonomy' => 'destinazione',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (!is_wp_error($destinazioni) && !empty($destinazioni)) {
            foreach ($destinazioni as $term) {
                $prefix = $term->parent ? '— ' : '';
                $options[$term->term_id] = $prefix . $term->name;
            }
        }
        
        return $options;
    }
    
    /**
     * Utility: Ottieni statistiche destinazioni
     */
    public function get_destination_stats() {
        $tablets = $this->get_tablets();
        $stats = array();
        
        foreach ($tablets as $tablet) {
            $destinazione = get_field('dove', $tablet->ID);
            
            if ($destinazione && is_object($destinazione)) {
                $dest_name = $destinazione->name;
                
                if (!isset($stats[$dest_name])) {
                    $stats[$dest_name] = array(
                        'totale' => 0,
                        'disponibili' => 0,
                        'assegnati' => 0,
                        'in_manutenzione' => 0
                    );
                }
                
                $stats[$dest_name]['totale']++;
                
                $stato = get_field('stato_dispositivo', $tablet->ID);
                
                switch ($stato) {
                    case 'disponibile':
                        $stats[$dest_name]['disponibili']++;
                        break;
                    case 'assegnato':
                        $stats[$dest_name]['assegnati']++;
                        break;
                    case 'in_manutenzione':
                        $stats[$dest_name]['in_manutenzione']++;
                        break;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Utility: Trova tablet duplicati per IMEI
     */
    public function find_duplicate_imei() {
        $tablets = $this->get_tablets();
        $imei_map = array();
        $duplicates = array();
        
        foreach ($tablets as $tablet) {
            $imei = get_field('imei_tablet', $tablet->ID);
            
            if ($imei && $imei !== '') {
                if (isset($imei_map[$imei])) {
                    // Duplicato trovato
                    if (!isset($duplicates[$imei])) {
                        $duplicates[$imei] = array($imei_map[$imei]);
                    }
                    $duplicates[$imei][] = $tablet;
                } else {
                    $imei_map[$imei] = $tablet;
                }
            }
        }
        
        return $duplicates;
    }
    
    /**
     * Utility: Verifica integrità dati tablet
     */
    public function verify_tablet_integrity($tablet_id = null) {
        $tablets = $tablet_id ? array(get_post($tablet_id)) : $this->get_tablets();
        $issues = array();
        
        foreach ($tablets as $tablet) {
            if (!$tablet) continue;
            
            $tablet_issues = array();
            
            // Verifica IMEI
            $imei = get_field('imei_tablet', $tablet->ID);
            if ($imei && strlen($imei) !== 15) {
                $tablet_issues[] = 'IMEI non valido (deve essere 15 cifre)';
            }
            
            // Verifica logica SIM
            $sim_inserita = get_field('sim_inserita', $tablet->ID);
            $sim_attiva = get_field('sim_attiva', $tablet->ID);
            
            if ($sim_attiva && !$sim_inserita) {
                $tablet_issues[] = 'SIM attiva ma non inserita';
            }
            
            // Verifica stato
            $stato = get_field('stato_dispositivo', $tablet->ID);
            if (!$stato) {
                $tablet_issues[] = 'Stato dispositivo mancante';
            }
            
            if (!empty($tablet_issues)) {
                $issues[$tablet->ID] = array(
                    'title' => $tablet->post_title,
                    'issues' => $tablet_issues
                );
            }
        }
        
        return $issues;
    }
    
    /**
     * Utility: Export dati tablet per backup
     */
    public function export_tablets_data() {
        $tablets = $this->get_tablets();
        $export_data = array();
        
        foreach ($tablets as $tablet) {
            $destinazione = get_field('dove', $tablet->ID);
            
            $export_data[] = array(
                'id' => $tablet->ID,
                'title' => $tablet->post_title,
                'imei_tablet' => get_field('imei_tablet', $tablet->ID),
                'stato_dispositivo' => get_field('stato_dispositivo', $tablet->ID),
                'tipologia' => get_field('tipologia', $tablet->ID),
                'data_di_carico' => get_field('data_di_carico', $tablet->ID),
                'destinazione' => $destinazione ? $destinazione->name : '',
                'modalita_kiosk' => get_field('modalita_kiosk', $tablet->ID) ? 1 : 0,
                'sim_inserita' => get_field('sim_inserita', $tablet->ID) ? 1 : 0,
                'sim_attiva' => get_field('sim_attiva', $tablet->ID) ? 1 : 0,
                'sn_sim' => get_field('sn_sim', $tablet->ID),
                'pin_sim' => get_field('pin_sim', $tablet->ID),
                'puk_sim' => get_field('puk_sim', $tablet->ID),
                'cover' => get_field('cover', $tablet->ID) ? 1 : 0,
                'scatola' => get_field('scatola', $tablet->ID) ? 1 : 0,
                'note_generali_tablet' => get_field('note_generali_tablet', $tablet->ID),
                'created' => $tablet->post_date,
                'modified' => $tablet->post_modified
            );
        }
        
        return $export_data;
    }
}
