/**
 * My Warehouse Manager - JavaScript
 * 
 * Gestisce le interazioni frontend del plugin, inclusa la modal
 * di visualizzazione dettagli tablet e le azioni di gruppo.
 */

(function($) {
    'use strict';

    /**
     * Oggetto principale per la gestione del warehouse
     */
    const WarehouseManager = {
        
        /**
         * Inizializza il plugin
         */
        init: function() {
            this.bindEvents();
            this.initBulkActions();
            this.initModal();
        },

        /**
         * Collega tutti gli eventi
         */
        bindEvents: function() {
            // Gestione modal visualizzazione tablet
            $(document).on('click', '.mwm-btn-view', this.openTabletModal);
            $(document).on('click', '.mwm-modal-close', this.closeModal);
            $(document).on('click', '.mwm-modal', this.closeModalOnBackdrop);

            // Gestione checkbox "Seleziona tutto"
            $(document).on('change', '#mwm-select-all', this.toggleSelectAll);
            $(document).on('change', '.mwm-tablet-checkbox', this.updateSelectAllState);

            // Gestione azioni di gruppo
            $(document).on('change', '.mwm-tablet-checkbox', this.updateBulkActionState);
            $(document).on('change', '#mwm-bulk-action', this.updateBulkActionState);

            // Gestione form azioni di gruppo
            $(document).on('submit', '#mwm-bulk-form', this.handleBulkAction);

            // Gestione escape key per chiudere modal
            $(document).on('keydown', this.handleKeyDown);

            // Gestione filtri cronologia (se presente)
            $(document).on('change', '.mwm-history-filter', this.filterHistory);

            // Debug logging
            if (window.console && console.log) {
                console.log('Warehouse Manager JavaScript inizializzato');
            }
        },

        /**
         * Inizializza componenti delle azioni di gruppo
         */
        initBulkActions: function() {
            this.updateBulkActionState();
        },

        /**
         * Inizializza modal
         */
        initModal: function() {
            // Assicura che la modal sia nascosta all'inizio
            $('#mwm-tablet-modal').hide();
        },

        /**
         * Apre modal con dettagli tablet
         */
        openTabletModal: function(e) {
            e.preventDefault();
            
            const tabletId = $(this).data('tablet-id');
            const modal = $('#mwm-tablet-modal');
            const modalBody = modal.find('.mwm-modal-body');

            if (!tabletId) {
                WarehouseManager.showError('ID tablet non trovato');
                return;
            }

            // Mostra modal con loading
            modalBody.html('<div class="mwm-loading">Caricamento dettagli tablet...</div>');
            modal.fadeIn(300);

            // Chiamata AJAX per recuperare dettagli
            $.ajax({
                url: warehouse_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwm_get_tablet_details',
                    tablet_id: tabletId,
                    nonce: warehouse_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        modalBody.html(response.data.html);
                        // Aggiorna titolo modal se necessario
                        modal.find('.mwm-modal-header h3').text('Dettagli Tablet: ' + response.data.tablet_title);
                    } else {
                        WarehouseManager.showModalError('Errore nel caricamento: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Errore AJAX:', error);
                    WarehouseManager.showModalError('Errore di connessione. Riprova più tardi.');
                }
            });
        },

        /**
         * Chiude la modal
         */
        closeModal: function(e) {
            if (e) e.preventDefault();
            $('#mwm-tablet-modal').fadeOut(300);
        },

        /**
         * Chiude modal cliccando sul backdrop
         */
        closeModalOnBackdrop: function(e) {
            if (e.target === this) {
                WarehouseManager.closeModal();
            }
        },

        /**
         * Gestisce tasto Escape per chiudere modal
         */
        handleKeyDown: function(e) {
            if (e.keyCode === 27) { // Escape key
                WarehouseManager.closeModal();
            }
        },

        /**
         * Mostra errore nella modal
         */
        showModalError: function(message) {
            const errorHtml = `
                <div class="mwm-error" style="margin: 20px 0; text-align: center;">
                    <strong>Errore:</strong> ${message}
                    <br><br>
                    <button class="mwm-btn mwm-btn-secondary" onclick="WarehouseManager.closeModal()">
                        Chiudi
                    </button>
                </div>
            `;
            $('#mwm-tablet-modal .mwm-modal-body').html(errorHtml);
        },

        /**
         * Mostra messaggio di errore generico
         */
        showError: function(message) {
            const errorHtml = `
                <div class="mwm-message error" style="margin: 10px 0;">
                    ${message}
                </div>
            `;
            
            // Rimuovi messaggi esistenti
            $('.mwm-message').remove();
            
            // Aggiungi nuovo messaggio in cima alla dashboard
            $('.mwm-dashboard-wrapper').prepend(errorHtml);
            
            // Auto-rimuovi dopo 5 secondi
            setTimeout(function() {
                $('.mwm-message.error').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Mostra messaggio di successo
         */
        showSuccess: function(message) {
            const successHtml = `
                <div class="mwm-message success" style="margin: 10px 0;">
                    ${message}
                </div>
            `;
            
            $('.mwm-message').remove();
            $('.mwm-dashboard-wrapper').prepend(successHtml);
            
            setTimeout(function() {
                $('.mwm-message.success').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Gestisce "Seleziona tutto"
         */
        toggleSelectAll: function() {
            const isChecked = $(this).prop('checked');
            $('.mwm-tablet-checkbox').prop('checked', isChecked);
            WarehouseManager.updateBulkActionState();
        },

        /**
         * Aggiorna stato "Seleziona tutto" basato sui checkbox individuali
         */
        updateSelectAllState: function() {
            const totalCheckboxes = $('.mwm-tablet-checkbox').length;
            const checkedCheckboxes = $('.mwm-tablet-checkbox:checked').length;
            
            const selectAllCheckbox = $('#mwm-select-all');
            
            if (checkedCheckboxes === 0) {
                selectAllCheckbox.prop('indeterminate', false);
                selectAllCheckbox.prop('checked', false);
            } else if (checkedCheckboxes === totalCheckboxes) {
                selectAllCheckbox.prop('indeterminate', false);
                selectAllCheckbox.prop('checked', true);
            } else {
                selectAllCheckbox.prop('indeterminate', true);
                selectAllCheckbox.prop('checked', false);
            }
            
            WarehouseManager.updateBulkActionState();
        },

        /**
         * Aggiorna stato dei controlli azioni di gruppo
         */
        updateBulkActionState: function() {
            const checkedCount = $('.mwm-tablet-checkbox:checked').length;
            const bulkActionSelect = $('#mwm-bulk-action');
            const submitButton = $('#mwm-bulk-form button[type="submit"]');
            const countSpan = $('.mwm-selected-count');

            // Aggiorna contatore
            const countText = checkedCount === 0 ? '0 tablet selezionati' : 
                             checkedCount === 1 ? '1 tablet selezionato' :
                             `${checkedCount} tablet selezionati`;
            countSpan.text(countText);

            // Abilita/disabilita bottone
            const hasAction = bulkActionSelect.val() !== '';
            const canSubmit = checkedCount > 0 && hasAction;
            
            submitButton.prop('disabled', !canSubmit);
            
            if (canSubmit) {
                submitButton.removeClass('mwm-btn-secondary').addClass('mwm-btn-primary');
            } else {
                submitButton.removeClass('mwm-btn-primary').addClass('mwm-btn-secondary');
            }
        },

        /**
         * Gestisce submit del form azioni di gruppo
         */
        handleBulkAction: function(e) {
            e.preventDefault();

            const form = $(this);
            const action = $('#mwm-bulk-action').val();
            const checkedTablets = $('.mwm-tablet-checkbox:checked');
            const submitButton = form.find('button[type="submit"]');

            if (!action || checkedTablets.length === 0) {
                WarehouseManager.showError('Seleziona un\'azione e almeno un tablet');
                return;
            }

            // Conferma azione
            const actionLabels = {
                'kiosk_on': 'attivare la modalità kiosk',
                'kiosk_off': 'disattivare la modalità kiosk',
                'sim_active_on': 'attivare la SIM',
                'sim_active_off': 'disattivare la SIM'
            };

            const actionLabel = actionLabels[action] || 'eseguire questa azione';
            const confirmMessage = `Sei sicuro di voler ${actionLabel} per ${checkedTablets.length} tablet selezionati?`;

            if (!confirm(confirmMessage)) {
                return;
            }

            // Disabilita bottone e mostra loading
            const originalText = submitButton.text();
            submitButton.prop('disabled', true).text('Elaborazione...');

            // Invia form normale (non AJAX per semplicità)
            // Il form verrà processato dal PHP con redirect
            form.off('submit').submit();

            // Nota: dopo il submit, la pagina si ricaricherà quindi non serve ripristinare lo stato del bottone
        },

        /**
         * Filtra cronologia movimenti (se presente)
         */
        filterHistory: function() {
            // Implementazione semplice per filtri
            const filterValue = $(this).val().toLowerCase();
            const filterType = $(this).data('filter-type');
            
            $('.mwm-history-table tbody tr').each(function() {
                const row = $(this);
                let shouldShow = true;
                
                if (filterType === 'tablet' && filterValue) {
                    const tabletCell = row.find('td').eq(2).text().toLowerCase(); // Colonna tablet
                    shouldShow = tabletCell.includes(filterValue);
                } else if (filterType === 'tipo' && filterValue) {
                    const tipoCell = row.find('td').eq(1).text().toLowerCase(); // Colonna tipo
                    shouldShow = tipoCell.includes(filterValue);
                }
                
                row.toggle(shouldShow);
            });
        },

        /**
         * Utility per evidenziare riga della tabella
         */
        highlightRow: function(tabletId, duration = 2000) {
            const row = $(`tr[data-tablet-id="${tabletId}"]`);
            if (row.length) {
                row.addClass('mwm-highlight');
                setTimeout(() => {
                    row.removeClass('mwm-highlight');
                }, duration);
            }
        },

        /**
         * Aggiorna riga della tabella dopo modifica
         */
        refreshTableRow: function(tabletId) {
            // Questa funzione potrebbe essere utile per aggiornare
            // una singola riga senza ricaricare l'intera pagina
            // Per ora è un placeholder per futuri sviluppi
        },

        /**
         * Gestisce click sui link sensibili (PIN, PUK, etc)
         */
        handleSensitiveData: function() {
            $(document).on('click', '.mwm-sensitive', function() {
                $(this).select();
            });
        }
    };

    /**
     * Inizializza quando il DOM è pronto
     */
    $(document).ready(function() {
        WarehouseManager.init();
        WarehouseManager.handleSensitiveData();
    });

    /**
     * Espone oggetto globalmente per uso esterno
     */
    window.WarehouseManager = WarehouseManager;

})(jQuery);

/**
 * CSS aggiuntivo per animazioni e stati speciali
 * (Questo verrà gestito meglio nel file CSS, ma per ora lo includiamo qui)
 */
if (typeof window !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = `
        .mwm-highlight {
            background-color: #fff3cd !important;
            transition: background-color 0.3s ease;
        }
        
        .mwm-loading {
            position: relative;
        }
        
        .mwm-loading::after {
            content: '';
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #ccc;
            border-top: 2px solid #007cba;
            border-radius: 50%;
            animation: mwm-spin 1s linear infinite;
        }
        
        @keyframes mwm-spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }
        
        .mwm-modal {
            backdrop-filter: blur(2px);
        }
        
        .mwm-btn:focus {
            outline: 2px solid #007cba;
            outline-offset: 2px;
        }
        
        .mwm-tablet-checkbox:focus,
        #mwm-select-all:focus {
            outline: 2px solid #007cba;
            outline-offset: 2px;
        }
    `;
    document.head.appendChild(style);
}