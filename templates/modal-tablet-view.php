<?php
/**
 * Template per la modal di visualizzazione dettagli tablet
 * 
 * Questo template può essere utilizzato sia nell'AJAX handler
 * sia come template standalone per rendering server-side.
 * 
 * Variabili disponibili:
 * $tablet_data - Array con tutti i dati del tablet
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Assicurati che i dati del tablet siano disponibili
if (empty($tablet_data)) {
    ?>
    <div class="mwm-error">
        Nessun dato tablet disponibile per la visualizzazione.
    </div>
    <?php
    return;
}

// Estrai dati per facilità d'uso
$tablet_id = $tablet_data['tablet_title'] ?? 'Non disponibile';
$stato = $tablet_data['stato_dispositivo'] ?? '';
$tipologia = $tablet_data['tipologia'] ?? '';
$imei = $tablet_data['imei_tablet'] ?? '';
$data_carico = $tablet_data['data_di_carico'] ?? '';
$ubicazione = $tablet_data['ubicazione_attuale_tablet'] ?? '';
$modalita_kiosk = $tablet_data['modalita_kiosk'] ?? false;
$sim_inserita = $tablet_data['sim_inserita'] ?? false;
$sim_attiva = $tablet_data['sim_attiva'] ?? false;
$sn_sim = $tablet_data['sn_sim'] ?? '';
$pin_sim = $tablet_data['pin_sim'] ?? '';
$puk_sim = $tablet_data['puk_sim'] ?? '';
$cover = $tablet_data['cover'] ?? false;
$scatola = $tablet_data['scatola'] ?? false;
$note = $tablet_data['note_generali_tablet'] ?? '';
$post_date = $tablet_data['post_date'] ?? '';
$post_modified = $tablet_data['post_modified'] ?? '';
?>

<div class="mwm-tablet-details">
    
    <!-- Header con ID tablet -->
    <div class="mwm-detail-header">
        <h4><?php echo esc_html($tablet_id); ?></h4>
        <div class="mwm-detail-meta">
            <?php if ($post_date): ?>
                <span><strong>Creato:</strong> <?php echo esc_html(date('d/m/Y H:i', strtotime($post_date))); ?></span>
            <?php endif; ?>
            <?php if ($post_modified && $post_modified !== $post_date): ?>
                <span><strong>Modificato:</strong> <?php echo esc_html(date('d/m/Y H:i', strtotime($post_modified))); ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Informazioni Principali -->
    <div class="mwm-detail-section">
        <h5>📋 Informazioni Principali</h5>
        <div class="mwm-detail-grid">
            <div class="mwm-detail-item">
                <label>Stato Dispositivo:</label>
                <span class="mwm-status status-<?php echo sanitize_html_class(strtolower($stato ?: 'non-definito')); ?>">
                    <?php echo esc_html($stato ?: 'Non definito'); ?>
                </span>
            </div>
            
            <div class="mwm-detail-item">
                <label>Tipologia:</label>
                <span><?php echo esc_html($tipologia ?: '-'); ?></span>
            </div>
            
            <div class="mwm-detail-item">
                <label>IMEI:</label>
                <span class="mwm-mono"><?php echo esc_html($imei ?: '-'); ?></span>
            </div>
            
            <div class="mwm-detail-item">
                <label>Data di Carico:</label>
                <span><?php echo $data_carico ? esc_html(date('d/m/Y', strtotime($data_carico))) : '-'; ?></span>
            </div>
        </div>
    </div>
    
    <!-- Ubicazione Attuale -->
    <div class="mwm-detail-section">
        <h5>📍 Ubicazione</h5>
        <div class="mwm-detail-item">
            <label>Ubicazione Attuale:</label>
            <span class="mwm-location-current">
                <?php 
                if ($ubicazione && $ubicazione !== '') {
                    echo esc_html($ubicazione);
                } else {
                    echo '<em style="color: #666;">Non assegnato</em>';
                }
                ?>
            </span>
        </div>
    </div>
    
    <!-- Configurazione -->
    <div class="mwm-detail-section">
        <h5>⚙️ Configurazione</h5>
        <div class="mwm-detail-grid">
            <div class="mwm-detail-item">
                <label>Modalità Kiosk:</label>
                <span class="mwm-status <?php echo $modalita_kiosk ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $modalita_kiosk ? '✅ Attiva' : '❌ Disattiva'; ?>
                </span>
            </div>
            
            <div class="mwm-detail-item">
                <label>Cover:</label>
                <span class="mwm-status <?php echo $cover ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $cover ? '✅ Presente' : '❌ Assente'; ?>
                </span>
            </div>
            
            <div class="mwm-detail-item">
                <label>Scatola:</label>
                <span class="mwm-status <?php echo $scatola ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $scatola ? '✅ Presente' : '❌ Assente'; ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Informazioni SIM -->
    <div class="mwm-detail-section">
        <h5>📱 Informazioni SIM</h5>
        <div class="mwm-detail-grid">
            <div class="mwm-detail-item">
                <label>SIM Inserita:</label>
                <span class="mwm-status <?php echo $sim_inserita ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $sim_inserita ? '✅ Sì' : '❌ No'; ?>
                </span>
            </div>
            
            <?php if ($sim_inserita): ?>
                <div class="mwm-detail-item">
                    <label>SIM Attiva:</label>
                    <span class="mwm-status <?php echo $sim_attiva ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $sim_attiva ? '✅ Sì' : '⚠️ No'; ?>
                    </span>
                </div>
                
                <?php if ($sn_sim): ?>
                    <div class="mwm-detail-item">
                        <label>SN SIM:</label>
                        <span class="mwm-mono mwm-sensitive" title="Clicca per selezionare"><?php echo esc_html($sn_sim); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($pin_sim): ?>
                    <div class="mwm-detail-item">
                        <label>PIN SIM:</label>
                        <span class="mwm-mono mwm-sensitive" title="Clicca per selezionare"><?php echo esc_html($pin_sim); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($puk_sim): ?>
                    <div class="mwm-detail-item">
                        <label>PUK SIM:</label>
                        <span class="mwm-mono mwm-sensitive" title="Clicca per selezionare"><?php echo esc_html($puk_sim); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!$sn_sim && !$pin_sim && !$puk_sim): ?>
                    <div class="mwm-detail-item">
                        <label></label>
                        <span style="font-style: italic; color: #666;">Nessun dettaglio SIM disponibile</span>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="mwm-detail-item">
                    <label></label>
                    <span style="font-style: italic; color: #666;">Nessuna SIM inserita</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Note -->
    <?php if ($note): ?>
        <div class="mwm-detail-section">
            <h5>📝 Note Generali</h5>
            <div class="mwm-detail-notes">
                <?php echo nl2br(esc_html($note)); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Statistiche Rapide -->
    <div class="mwm-detail-section">
        <h5>📊 Riepilogo Rapido</h5>
        <div class="mwm-detail-grid">
            <?php
            // Calcola "punteggio completezza"
            $completeness_score = 0;
            $max_score = 10;
            
            if ($stato) $completeness_score++;
            if ($tipologia) $completeness_score++;
            if ($imei) $completeness_score++;
            if ($data_carico) $completeness_score++;
            if ($ubicazione) $completeness_score += 2; // Più importante
            if ($modalita_kiosk !== null) $completeness_score++;
            if ($sim_inserita !== null) $completeness_score++;
            if ($cover !== null) $completeness_score++;
            if ($scatola !== null) $completeness_score++;
            
            $completeness_percentage = round(($completeness_score / $max_score) * 100);
            
            // Stato configurazione
            $config_status = 'Parziale';
            $config_class = 'status-partial';
            
            if ($completeness_percentage >= 90) {
                $config_status = 'Completa';
                $config_class = 'status-active';
            } elseif ($completeness_percentage < 50) {
                $config_status = 'Incompleta';
                $config_class = 'status-inactive';
            }
            ?>
            
            <div class="mwm-detail-item">
                <label>Completezza Dati:</label>
                <span class="mwm-status <?php echo $config_class; ?>">
                    <?php echo $config_status; ?> (<?php echo $completeness_percentage; ?>%)
                </span>
            </div>
            
            <div class="mwm-detail-item">
                <label>Pronto per Uso:</label>
                <span class="mwm-status <?php echo ($stato === 'Disponibile' && $modalita_kiosk) ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo ($stato === 'Disponibile' && $modalita_kiosk) ? '✅ Sì' : '⚠️ No'; ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Azioni Rapide -->
    <div class="mwm-detail-actions">
        <a href="/modifica-tablet/?post_id=<?php echo esc_attr($tablet_data['tablet_id'] ?? ''); ?>" 
           class="mwm-btn mwm-btn-primary">
            ✏️ Modifica Tablet
        </a>
        <a href="/esegui-movimento/?post_id=<?php echo esc_attr($tablet_data['tablet_id'] ?? ''); ?>" 
           class="mwm-btn mwm-btn-secondary">
            📦 Registra Movimento
        </a>
        
        <?php if ($ubicazione): ?>
            <button class="mwm-btn mwm-btn-secondary" onclick="WarehouseManager.highlightRow(<?php echo esc_attr($tablet_data['tablet_id'] ?? '0'); ?>)">
                📍 Evidenzia in Tabella
            </button>
        <?php endif; ?>
    </div>
    
    <!-- Footer con suggerimenti -->
    <?php if ($completeness_percentage < 80): ?>
        <div style="margin-top: 20px; padding: 12px; background: #fff3cd; border-radius: 4px; border-left: 4px solid #ffc107;">
            <strong>💡 Suggerimento:</strong> 
            Completa le informazioni mancanti per migliorare la gestione del tablet:
            <ul style="margin: 8px 0 0 20px; font-size: 13px;">
                <?php if (!$stato): ?><li>Imposta stato dispositivo</li><?php endif; ?>
                <?php if (!$tipologia): ?><li>Specifica tipologia tablet</li><?php endif; ?>
                <?php if (!$imei): ?><li>Inserisci IMEI</li><?php endif; ?>
                <?php if (!$ubicazione): ?><li>Assegna ubicazione attuale</li><?php endif; ?>
                <?php if (!$data_carico): ?><li>Inserisci data di carico</li><?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<script>
// JavaScript specifico per la modal
jQuery(document).ready(function($) {
    // Seleziona automaticamente testo sensibile al click
    $('.mwm-sensitive').on('click', function() {
        this.select();
        document.execCommand('copy');
        
        // Feedback visivo
        const originalBg = $(this).css('background-color');
        $(this).css('background-color', '#d4edda');
        setTimeout(() => {
            $(this).css('background-color', originalBg);
        }, 300);
    });
    
    // Tooltip per dati sensibili
    $('.mwm-sensitive').attr('title', 'Clicca per selezionare e copiare');
});
</script>