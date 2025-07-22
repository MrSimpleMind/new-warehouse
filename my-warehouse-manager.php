<?php
/**
 * Plugin Name: My Warehouse Manager
 * Description: Sistema di gestione magazzino tablet con dashboard, form modal e destinazioni unificate
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
        
        // Hook per attivazione/disattivazione plugin
        register_activation_hook(__FILE__, array($this, 'on_activation'));
        register_deactivation_hook(__FILE__, array($this, 'on_deactivation'));
    }
    
    /**
     * Carica CSS e JS nel frontend
     */
    public function enqueue_assets() {
        // Solo sulla pagina magazzino-tablet
        if (is_page() && is_page('magazzino-tablet')) {
            
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
                'error_text' => __('Errore nel caricamento dati', 'my-warehouse-manager'),
                'success_text' => __('Operazione completata con successo', 'my-warehouse-manager')
            ));
        }
    }
    
    /**
     * Carica assets nell'admin (se necessario)
     */
    public function enqueue_admin_assets() {
        // Per ora vuoto, ma pronto per futuri sviluppi
        // Potremmo aggiungere stili per preview form ACF
    }
    
    /**
     * Esegue operazioni all'attivazione del plugin
     */
    public function on_activation() {
        // Flush rewrite rules se necessario
        flush_rewrite_rules();
        
        // Crea pagina magazzino-tablet se non esiste
        $this->create_required_pages();
        
        // Log attivazione
        if (WP_DEBUG) {
            error_log('My Warehouse Manager plugin attivato');
        }
    }
    
    /**
     * Esegue operazioni alla disattivazione del plugin
     */
    public function on_deactivation() {
        // Cleanup se necessario
        flush_rewrite_rules();
        
        // Log disattivazione  
        if (WP_DEBUG) {
            error_log('My Warehouse Manager plugin disattivato');
        }
    }
    
    /**
     * Crea pagine richieste dal plugin
     */
    private function create_required_pages() {
        $required_pages = array(
            array(
                'slug' => 'magazzino-tablet',
                'title' => 'Dashboard Magazzino Tablet',
                'content' => '[tablet_dashboard]',
                'status' => 'private' // Pagina privata per sicurezza base
            )
        );
        
        foreach ($required_pages as $page_data) {
            // Controlla se la pagina esiste già
            $existing_page = get_page_by_path($page_data['slug']);
            
            if (!$existing_page) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_content' => $page_data['content'],
                    'post_status' => $page_data['status'],
                    'post_type' => 'page',
                    'post_author' => 1
                ));
                
                if ($page_id && !is_wp_error($page_id)) {
                    mwm_log("Pagina creata: {$page_data['title']} (ID: {$page_id})", 'info');
                }
            }
        }
    }
}

// Inizializza il plugin
MyWarehouseManager::get_instance();

// Funzioni di utility globali per compatibilità
if (!function_exists('mwm_get_plugin_url')) {
    /**
     * Restituisce URL del plugin
     */
    function mwm_get_plugin_url($path = '') {
        return MWM_PLUGIN_URL . $path;
    }
}

if (!function_exists('mwm_get_plugin_version')) {
    /**
     * Restituisce versione del plugin
     */
    function mwm_get_plugin_version() {
        return MWM_PLUGIN_VERSION;
    }
}

/**
 * Hook per personalizzazioni Frontend Admin
 */
add_filter('frontend_admin_form_content', function($content, $form) {
    // Aggiungi stili personalizzati per form nelle modal
    if (strpos($content, 'class="frontend-admin-form"') !== false) {
        $content = str_replace(
            'class="frontend-admin-form"',
            'class="frontend-admin-form mwm-frontend-form"',
            $content
        );
    }
    
    return $content;
}, 10, 2);

/**
 * Personalizza messaggi Frontend Admin per modal
 */
add_filter('frontend_admin_success_message', function($message, $form) {
    // Per le modal, usiamo JavaScript per gestire il successo
    return '<div class="mwm-form-success" style="display:none;">' . $message . '</div>';
}, 10, 2);

/**
 * Hook per debugging in sviluppo
 */
if (WP_DEBUG) {
    add_action('wp_footer', function() {
        if (is_page('magazzino-tablet')) {
            echo '<!-- My Warehouse Manager Debug: Plugin caricato correttamente -->';
        }
    });
}
