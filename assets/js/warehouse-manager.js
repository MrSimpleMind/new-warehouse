/**
 * My Warehouse Manager - JavaScript con Modal Forms
 * 
 * Gestisce le interazioni frontend del plugin, incluse le modal
 * per visualizzazione dettagli, modifica tablet, movimento e aggiunta
 * tramite form Frontend Admin integrate.
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
            this.initModals();
        },

        /**
         * Collega tutti gli eventi
         */
        bindEvents: function() {
            // Gestione modal visualizzazione tablet (mantenuta)
            $(document).on('click', '.mwm-btn-view', this.openTabletModal);
            
            // NUOVI: Gestione modal forms
            $(document).on('click', '.mwm-btn-edit', this.openEditModal);
            $(document).on('click', '.mwm-btn-movement', this.openMovementModal);
            $(document).on('click', '#mwm-add-tablet-btn', this.openAddTabletModal);
            
            // Gestione chiusura modal
            $(document).on('click', '.mwm-modal-close', this.closeModal);
            $(document).on('click', '.mwm-modal', this.closeModalOnBackdrop);

            // Gestione checkbox "Seleziona tutto"
            $(document).on('change', '#mwm-select-all', this.toggleSelectAll);
            $(document).on('change', '.mwm-tablet-checkbox', this.updateSelectAllState);

            // Gestione azioni di gruppo
            $(document).on('change', '.mwm-tablet-checkbox', this.updateBulkActionState);
            $(document).on('change', '#mwm-bulk-action', this.updateBulkActionState);
            $(document).on('submit', '#mwm-bulk-form', this.handleBulkAction);

            // Gestione escape key per chiudere modal
            $(document).on('keydown', this.handleKeyDown);

            // Debug logging
            console.log('Warehouse Manager JavaScript inizializzato con Modal Forms');
        },

        /**
         * Inizializza componenti delle azioni di gruppo
         */
        initBulkActions: function() {
            this.updateBulkActionState();
        },

        /**
         * Inizializza tutte le modal
         */
        initModals: function() {
            // Assicura che tutte le modal siano nascoste all'inizio
            $('.mwm-modal').hide();
        },

        /**
         * Apre modal con dettagli tablet (mantenuta)
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
         * NUOVO: Apre modal modifica tablet
         */
        openEditModal: function(e) {
            e.preventDefault();
            
            const tabletId = $(this).data('tablet-id');
            const modal = $('#mwm-edit-tablet-modal');
            const modalBody = modal.find('.mwm-modal-body');

            if (!tabletId) {
                WarehouseManager.showError('ID tablet non trovato');
                return;
            }

            // Mostra modal con loading
            modalBody.html('<div class="mwm-loading">Caricamento form modifica...</div>');
            modal.fadeIn(300);

            // Chiamata AJAX per caricare form Frontend Admin
            $.ajax({
                url: warehouse_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwm_get_edit_form',
                    tablet_id: tabletId,
                    nonce: warehouse_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        modalBody.html(response.data.html);
                        modal.find('.mwm-modal-header h3').text('Modifica Tablet: ' + response.data.tablet_title);
                        
                        // Inizializza form Frontend Admin
                        WarehouseManager.initModalForm(modal);
                    } else {
                        WarehouseManager.showModalError('Errore nel caricamento form: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Errore AJAX modifica:', error);
                    WarehouseManager.showModalError('Errore di connessione. Riprova più tardi.');
                }
            });
        },

        /**
         * NUOVO: Apre modal movimento tablet
         */
        openMovementModal: function(e) {
            e.preventDefault();
            
            const tabletId = $(this).data('tablet-id');
            const modal = $('#mwm-movement-modal');
            const modalBody = modal.find('.mwm-modal-body');

            if (!tabletId) {
                WarehouseManager.showError('ID tablet non trovato');
                return;
            }

            // Mostra modal con loading
            modalBody.html('<div class="mwm-loading">Caricamento form movimento...</div>');
            modal.fadeIn(300);

            // Chiamata AJAX per caricare form movimento
            $.ajax({
                url: warehouse_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwm_get_movement_form',
                    tablet_id: tabletId,
                    nonce: warehouse_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        modalBody.html(response.data.html);
                        modal.find('.mwm-modal-header h3').text('Movimento Tablet: ' + response.data.tablet_title);
                        
                        // Inizializza form Frontend Admin
                        WarehouseManager.initModalForm(modal);
                    } else {
                        WarehouseManager.showModalError('Errore nel caricamento form movimento: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Errore AJAX movimento:', error);
                    WarehouseManager.showModalError('Errore di connessione. Riprova più tardi.');
                }
            });
        },

        /**
         * NUOVO: Apre modal aggiungi tablet
         */
        openAddTabletModal: function(e) {
            if (e) e.preventDefault();
            
            const modal = $('#mwm-add-tablet-modal');
            const modalBody = modal.find('.mwm-modal-body');

            // Mostra modal con loading
            modalBody.html('<div class="mwm-loading">Caricamento form aggiunta tablet...</div>');
            modal.fadeIn(300);

            // Chiamata AJAX per caricare form aggiunta
            $.ajax({
                url: warehouse_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwm_get_add_form',
                    nonce: warehouse_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        modalBody.html(response.data.html);
                        
                        // Inizializza form Frontend Admin
                        WarehouseManager.initModalForm(modal);
                    } else {
                        WarehouseManager.showModalError('Errore nel caricamento form: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Errore AJAX aggiunta:', error);
                    WarehouseManager.showModalError('Errore di connessione. Riprova più tardi.');
                }
            });
        },

        /**
         * NUOVO: Inizializza form Frontend Admin nella modal
         */
        initModalForm: function(modal) {
            const form = modal.find('form');
            
            if (form.length) {
                // Aggiungi gestore per submit form
                form.on('submit', function(e) {
                    const submitBtn = $(this).find('button[type="submit"], input[type="submit"]');
                    submitBtn.prop('disabled', true);
                    
                    // Cambia testo del pulsante per feedback
                    const originalText = submitBtn.text();
                    submitBtn.text('Salvando...');
                    
                    // Dopo 3 secondi, se il form non ha avuto successo, riabilita
                    setTimeout(function() {
                        if (submitBtn.prop('disabled')) {
                            submitBtn.prop('disabled', false).text(originalText);
                        }
                    }, 5000);
                });

                console.log('Form Frontend Admin inizializzato nella modal');
            }
        },

        /**
         * NUOVO: Chiude tutte le modal
         */
        closeAllModals: function() {
            $('.mwm-modal').fadeOut(300);
        },

        /**
         * Chiude la modal specificata o tutte
         */
        closeModal: function(e) {
            if (e) e.preventDefault();
            
            const modal = $(e.target).closest('.mwm-modal');
            if (modal.length) {
                modal.fadeOut(300);
            } else {
                // Chiudi tutte le modal
                WarehouseManager.closeAllModals();
            }
        },

        /**
         * Chiude modal cliccando sul backdrop
         */
        closeModalOnBackdrop: function(e) {
            if (e.target === this) {
                $(this).fadeOut(300);
            }
        },

        /**
         * Gestisce tasto Escape per chiudere modal
         */
        handleKeyDown: function(e) {
            if (e.keyCode === 27) { // Escape key
                WarehouseManager.closeAllModals();
            }
        },

        /**
         * NUOVO: Refresh dashboard dopo operazioni
         */
        refreshDashboard: function() {
            $.ajax({
                url: warehouse_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwm_refresh_dashboard',
                    nonce: warehouse_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Aggiorna statistiche
                        $('.mwm-stats-grid').html(response.data.stats_html);
                        
                        // Aggiorna tabella
                        $('#mwm-tablets-table tbody').html(response.data.table_html);
                        
                        console.log('Dashboard aggiornata con successo');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Errore refresh dashboard:', error);
                }
            });
        },

        /**
         * Mostra errore nella modal
         */
        showModalError: function(message) {
            const errorHtml = `
                <div class="mwm-error" style="margin: 20px 0; text-align: center;">
                    <strong>Errore:</strong> ${message}
                    <br><br>
                    <button class="mwm-btn mwm-btn-secondary" onclick="WarehouseManager.closeAllModals()">
                        Chiudi
                    </button>
                </div>
            `;
            $('.mwm-modal:visible .mwm-modal-body').html(errorHtml);
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
            
            $('.mwm-message').remove();
            $('.mwm-dashboard-wrapper').prepend(errorHtml);
            
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
         * Aggiorna stato "Seleziona tutto"
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

            // Raccogli ID tablet
            const tabletIds = [];
            checkedTablets.each(function() {
                tabletIds.push($(this).val());
            });

            // Invia via AJAX
            $.ajax({
                url: warehouse_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwm_bulk_action',
                    bulk_action: action,
                    tablet_ids: tabletIds,
                    nonce: warehouse_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WarehouseManager.showSuccess(response.data.message);
                        WarehouseManager.refreshDashboard();
                        
                        // Reset form
                        $('#mwm-bulk-action').val('');
                        $('.mwm-tablet-checkbox, #mwm-select-all').prop('checked', false);
                        WarehouseManager.updateBulkActionState();
                    } else {
                        WarehouseManager.showError('Errore: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Errore bulk action:', error);
                    WarehouseManager.showError('Errore di connessione. Riprova più tardi.');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text(originalText);
                }
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
        }
    };

    /**
     * Inizializza quando il DOM è pronto
     */
    $(document).ready(function() {
        WarehouseManager.init();
        
        // Esponi oggetto globalmente per uso esterno
        window.WarehouseManager = WarehouseManager;
        
        // Listener per successo form Frontend Admin
        $(document).on('frontend_admin_form_success', function(e, form, response) {
            console.log('Frontend Admin form submitted successfully');
            
            // Refresh dashboard dopo 1 secondo
            setTimeout(function() {
                WarehouseManager.refreshDashboard();
                WarehouseManager.closeAllModals();
                WarehouseManager.showSuccess('Operazione completata con successo!');
            }, 1000);
        });
        
        // Listener per errori form Frontend Admin
        $(document).on('frontend_admin_form_error', function(e, form, response) {
            console.error('Frontend Admin form error:', response);
            WarehouseManager.showError('Errore nel salvataggio del form. Riprova.');
        });
    });

})(jQuery);

/**
 * CSS aggiuntivo per modal forms
 */
if (typeof window !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = `
        /* Modal form styling */
        .mwm-modal-form {
            max-width: 900px !important;
        }
        
        .mwm-modal-form .mwm-modal-body {
            max-height: 70vh;
            overflow-y: auto;
            padding: 20px;
        }
        
        /* Frontend Admin form styling in modal */
        .mwm-modal .frontend-admin-form {
            background: none;
            box-shadow: none;
            border: none;
        }
        
        .mwm-modal .frontend-admin-form input,
        .mwm-modal .frontend-admin-form select,
        .mwm-modal .frontend-admin-form textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .mwm-modal .frontend-admin-form button[type="submit"] {
            background: #007cba;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .mwm-modal .frontend-admin-form button[type="submit"]:hover {
            background: #005a87;
        }
        
        .mwm-modal .frontend-admin-form button[type="submit"]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .mwm-highlight {
            background-color: #fff3cd !important;
            transition: background-color 0.3s ease;
        }
        
        .mwm-loading {
            position: relative;
            padding: 40px;
            text-align: center;
            color: #666;
        }
        
        .mwm-loading::after {
            content: '';
            position: absolute;
            right: 50%;
            top: 60%;
            transform: translateX(50%);
            width: 20px;
            height: 20px;
            border: 2px solid #ccc;
            border-top: 2px solid #007cba;
            border-radius: 50%;
            animation: mwm-spin 1s linear infinite;
        }
        
        @keyframes mwm-spin {
            0% { transform: translateX(50%) rotate(0deg); }
            100% { transform: translateX(50%) rotate(360deg); }
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
        
        /* Responsive modal forms */
        @media (max-width: 768px) {
            .mwm-modal-form {
                max-width: 95vw !important;
                margin: 20px !important;
            }
            
            .mwm-modal-form .mwm-modal-body {
                max-height: 80vh;
                padding: 15px;
            }
        }
    `;
    document.head.appendChild(style);
}
