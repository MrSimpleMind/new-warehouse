<?php
/**
 * Classe principale per la gestione del magazzino
 * 
 * Gestisce la logica centrale del plugin, inclusi i custom post type,
 * le tassonomie e le operazioni principali sui tablet.
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
    private $post_types = array('tablet', 'movimento_magazzino');
    
    /**
     * Tassonomie supportate
     */
    private $taxonomies = array('destinazioni_interne', 'progetti_esterni');
    
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
        
        // Hook per validazione form Frontend Admin
        add_action('frontend_admin/form/after_save', array($this, 'handle_frontend_form_save'), 10, 2);
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
        // Verifica nonce e permessi
        if (!wp_verify_nonce($_POST['nonce'], 'warehouse_manager_nonce') || !current_user_can('administrator')) {
            wp_die('Accesso non autorizzato');
        }
        
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
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('Aggiornati %d tablet su %d selezionati', $updated_count, count($tablet_ids)),
            'updated_count' => $updated_count
        ));
    }
    
    /**
     * Gestisce salvataggio form Frontend Admin
     */
    public function handle_frontend_form_save($post_id, $form_id) {
        // Logica per gestire salvataggi specifici
        // Questo hook sarà chiamato dopo ogni salvataggio di Frontend Admin
        
        // Se è un form di movimento, crea anche il log
        if (get_post_type($post_id) === 'movimento_magazzino') {
            $this->create_movimento_log($post_id);
        }
        
        // Se è un tablet modificato, aggiorna timestamp ultima modifica
        if (get_post_type($post_id) === 'tablet') {
            update_field('ultima_modifica', current_time('mysql'), $post_id);
        }
    }
    
    /**
     * Crea log dettagliato per movimento
     */
    private function create_movimento_log($movimento_id) {
        // Per ora solo log, in futuro potremmo aggiungere notifiche
        $movimento = get_post($movimento_id);
        $tablet_coinvolto = get_field('tablet_coinvolto', $movimento_id);
        
        if ($tablet_coinvolto) {
            $tablet_title = $tablet_coinvolto->post_title;
            $tipo_movimento = get_field('tipo_di_movimento', $movimento_id);
            
            error_log(sprintf(
                'Movimento registrato: %s per tablet %s (ID: %d)',
                $tipo_movimento,
                $tablet_title,
                $tablet_coinvolto->ID
            ));
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
     * Utility: Ottieni movimenti con filtri
     */
    public function get_movimenti($args = array()) {
        $default_args = array(
            'post_type' => 'movimento_magazzino',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $default_args);
        
        return get_posts($args);
    }
    
    /**
     * Utility: Genera titolo automatico per movimento
     */
    public function generate_movimento_title($tablet_id, $data_movimento = null) {
        if (!$data_movimento) {
            $data_movimento = current_time('Y-m-d');
        }
        
        $tablet = get_post($tablet_id);
        $tablet_title = $tablet ? $tablet->post_title : 'Unknown';
        
        return sprintf('Movimento %s - %s', $tablet_title, $data_movimento);
    }
    
    /**
     * Utility: Ottieni opzioni per campo ubicazione_attuale_tablet
     */
    public function get_ubicazione_options() {
        $options = array(
            '' => '--- Nessuna Assegnazione ---'
        );
        
        // Aggiungi destinazioni interne
        $destinazioni = get_terms(array(
            'taxonomy' => 'destinazioni_interne',
            'hide_empty' => false
        ));
        
        if (!is_wp_error($destinazioni) && !empty($destinazioni)) {
            $options['--- DESTINAZIONI INTERNE ---'] = '--- DESTINAZIONI INTERNE ---';
            foreach ($destinazioni as $term) {
                $prefix = $term->parent ? '— ' : '';
                $options[$term->name] = $prefix . $term->name;
            }
        }
        
        // Aggiungi progetti esterni
        $progetti = get_terms(array(
            'taxonomy' => 'progetti_esterni',
            'hide_empty' => false
        ));
        
        if (!is_wp_error($progetti) && !empty($progetti)) {
            $options['--- PROGETTI ESTERNI ---'] = '--- PROGETTI ESTERNI ---';
            foreach ($progetti as $term) {
                $options[$term->name] = $term->name;
            }
        }
        
        return $options;
    }
}