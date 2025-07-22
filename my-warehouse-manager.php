<?php
/**
 * Plugin Name: My Warehouse Manager
 * Description: Sistema di gestione magazzino tablet con dashboard, form di modifica e storico movimenti
 * Version: 1.0.0
 * Author: Il Tuo Nome
 * Text Domain: my-warehouse-manager
 * Domain Path: /languages
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definisci costanti del plugin
define('MWM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MWM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MWM_PLUGIN_VERSION', '1.0.0');

/**
 * Classe principale del plugin
 */
class MyWarehouseManager {
    
    /**
     * Istanza singleton del plugin
     */
    private static $instance = null;
    
    /**
     * Costruttore privato per pattern singleton
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Restituisce l'istanza singleton del plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inizializza il plugin
     */
    public function init() {
        // Carica file di supporto
        $this->load_dependencies();
        
        // Registra hooks
        $this->register_hooks();
        
        // Carica assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Carica tutti i file PHP necessari
     */
    private function load_dependencies() {
        require_once MWM_PLUGIN_PATH . 'includes/class-warehouse-manager.php';
        require_once MWM_PLUGIN_PATH . 'includes/security.php';
        require_once MWM_PLUGIN_PATH . 'includes/helpers.php';
        require_once MWM_PLUGIN_PATH . 'includes/dashboard-logic.php';
        require_once MWM_PLUGIN_PATH . 'includes/ajax-handlers.php';
        require_once MWM_PLUGIN_PATH . 'includes/frontend-forms.php';
    }
    
    /**
     * Registra hooks e shortcode
     */
    private function register_hooks() {
        // Inizializza classe principale
        MWM_Warehouse_Manager::get_instance();
        
        // Registra shortcode dashboard
        add_shortcode('tablet_dashboard', 'mwm_tablet_dashboard_shortcode');
        add_shortcode('movimenti_history_table', 'mwm_movimenti_history_shortcode');
        
        // Hook per attivazione/disattivazione plugin
        register_activation_hook(__FILE__, array($this, 'on_activation'));
        register_deactivation_hook(__FILE__, array($this, 'on_deactivation'));
    }
    
    /**
     * Carica CSS e JS nel frontend
     */
    public function enqueue_assets() {
        // Solo sulle pagine che ne hanno bisogno
        if (is_page() && (is_page('magazzino-tablet') || is_page('modifica-tablet') || is_page('esegui-movimento') || is_page('cronologia-movimenti'))) {
            
            wp_enqueue_style(
                'warehouse-manager-css',
                MWM_PLUGIN_URL . 'assets/css/warehouse-manager.css',
                array(),
                MWM_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                'warehouse-manager-js',
                MWM_PLUGIN_URL . 'assets/js/warehouse-manager.js',
                array('jquery'),
                MWM_PLUGIN_VERSION,
                true
            );
            
            // Passa variabili JavaScript per AJAX
            wp_localize_script('warehouse-manager-js', 'warehouse_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('warehouse_manager_nonce'),
                'loading_text' => __('Caricamento...', 'my-warehouse-manager'),
                'error_text' => __('Errore nel caricamento dati', 'my-warehouse-manager')
            ));
        }
    }
    
    /**
     * Carica assets nell'admin (se necessario)
     */
    public function enqueue_admin_assets() {
        // Per ora vuoto, ma pronto per futuri sviluppi
    }
    
    /**
     * Esegue operazioni all'attivazione del plugin
     */
    public function on_activation() {
        // Flush rewrite rules se necessario
        flush_rewrite_rules();
        
        // Log attivazione
        error_log('My Warehouse Manager plugin attivato');
    }
    
    /**
     * Esegue operazioni alla disattivazione del plugin
     */
    public function on_deactivation() {
        // Cleanup se necessario
        flush_rewrite_rules();
        
        // Log disattivazione  
        error_log('My Warehouse Manager plugin disattivato');
    }
}

// Inizializza il plugin
MyWarehouseManager::get_instance();

// Funzioni di utility globali per compatibilità
if (!function_exists('mwm_is_admin_user')) {
    /**
     * Verifica se l'utente corrente è amministratore
     */
    function mwm_is_admin_user() {
        return current_user_can('administrator');
    }
}

if (!function_exists('mwm_get_plugin_url')) {
    /**
     * Restituisce URL del plugin
     */
    function mwm_get_plugin_url($path = '') {
        return MWM_PLUGIN_URL . $path;
    }
}