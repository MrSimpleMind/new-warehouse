/**
 * My Warehouse Manager - JavaScript CORRETTO
 * 
 * Versione corretta con debug e gestione errori migliorata
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
            console.log('WarehouseManager: Inizializzazione...');
            this.bindEvents();
            this.initBulkActions();
            this.initModals();
            console.log('WarehouseManager: Inizializzazione completata');
        },

        /**
         * Collega tutti gli eventi
         */
        bindEvents: function() {
            console.log('WarehouseManager: Collegando eventi...');
            
            // Gestione modal visualizzazione tablet (mantenuta)
            $(document).on('click', '.mwm-btn-view', this.openTabletModal);
            
            // MODAL FORMS - Con debug
            $(document).on('click', '.mwm-btn-edit', this.openEditModal);
            $(document).on('click', '.mwm-btn-movement', this.openMovementModal);
            $(document).on('click', '#mwm-add-tablet-btn', this.openAddTabletModal);
            
            // Debug: verifica che i bottoni esistano
            console.log('Add tablet button found:', $('#mwm-add-tablet-btn').length);
            console.log('Edit buttons found:', $('.mwm-btn-edit').length);
            console.log('Movement buttons found:', $('.mwm-btn-movement').length);
            
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

            console.log('WarehouseManager: Eventi collegati');
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
            console.log('Opening tablet details modal');
            
            const tabletId = $(this).data('tablet-id');
            const modal = $('#mwm-tablet-modal');
            const modalBody = modal.find('.mwm-modal-body');

            if (!tabletId) {
                console.error('Tablet ID not found');
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
                    console.log('Tablet details response:', response);
                    if (response.success) {
                        modalBody.html(response.data.html);
                        modal.find('.mwm-modal-header h3').text('Dettagli Tablet: ' + response.data.tablet_title);
                    } else {
                        console.error('Error in tablet details:', response);
                        WarehouseManager.showModalError('Errore nel caricamento: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error in tablet details:', xhr, status, error);
                    WarehouseManager.showModalError('Errore di connessione. Riprova più tardi.');
                }
            });
        },

        /**
         * NUOVO: Apre modal modifica tablet - CORRETTO
         */
        openEditModal: function(e) {
            e.preventDefault();
            console.log('Opening edit modal');
            
            const tabletId = $(this).data('tablet-id');
            const modal = $('#mwm-edit-tablet-modal');
            const modalBody = modal.find('.mwm-modal-body');

            console.log('Edit modal - Tablet ID:', tabletId);

            if (!tabletId) {
                console.error('Tablet ID not found for edit modal');
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
                    console.log('Edit form response:', response);
                    if (response.success) {
                        modalBody.html(response.data.html);
                        modal.find('.mwm-modal-header h3').text('Modifica Tablet: ' + response.data.tablet_title);
                        
                        // Inizializza form Frontend Admin
                        WarehouseManager.initModalForm(modal);
                    } else {
                        console.error('Error in edit form:', response);
                        WarehouseManager.showModalError('Errore nel caricamento form: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error in edit form:', xhr, status, error);
                    WarehouseManager.showModalError('Errore di connessione. Riprova più tardi.');
                }
            });
        },

        /**
         * NUOVO: Apre modal movimento tablet - CORRETTO
         */
        openMovementModal: function(e) {
            e.preventDefault();
            console.log('Opening movement modal');
            
            const tabletId = $(this).data('tablet-id');
            const modal = $('#mwm-movement-modal');
            const modalBody = modal.find('.mwm-modal-body');

            console.log('Movement modal - Tablet ID:', tabletId);

            if (!tabletId) {
                console.error('Tablet ID not found for movement modal');
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
                    console.log('Movement form response:', response);
                    if (response.success) {
                        modalBody.html(response.data.html);
                        modal.find('.mwm-modal-header h3').text('Movimento Tablet: ' + response.data.tablet_title);
                        
                        // Inizializza form Frontend Admin
                        WarehouseManager.initModalForm(modal);
                    } else {
                        console.error('Error in movement form:', response);
                        WarehouseManager.showModalError('Errore nel caricamento form movimento: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error in movement form:', xhr, status, error);
                    WarehouseManager.showModalError('Errore di connessione. Riprova più tardi.');
                }
            });
        },

        /**
         * NUOVO: Apre modal aggiungi tablet - CORRETTO
         */
        openAddTabletModal: function(e) {
            if (e) e.preventDefault();
            console.log('Opening add tablet modal');
            
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
                    console.log('Add form response:', response);
                    if (response.success) {
                        modalBody.html(response.data.html);
                        modal.find('.mwm-modal-header h3').text('Aggiungi Nuovo Tablet');
                        
                        // Inizializza form Frontend Admin
                        WarehouseManager.initModalForm(modal);
                    } else {
                        console.error('Error in add form:', response);
                        WarehouseManager.showModalError('Errore nel caricamento form: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error in add form:', xhr, status, error);
                    WarehouseManager.showModalError('Errore di connessione. Riprova più tardi.');
                }
            });
        },

        /**
         * NUOVO: Inizializza form Frontend Admin nella modal
         */
        initModalForm: function(modal) {
            const form = modal.find('form');
            console.log('Initializing modal form, forms found:', form.length);
            
            if (form.length) {
                // Aggiungi gestore per submit form
                form.on('submit', function(e) {
                    console.log('Form submitting...');
                    const submitBtn = $(this).find('button[type="submit"], input[type="submit"]');
                    submitBtn.prop('disabled', true);
                    
                    // Cambia testo del pulsante per feedback
                    const originalText = submitBtn.text() || submitBtn.val();
                    if (submitBtn.is('input')) {
                        submitBtn.val('Salvando...');
                    } else {
                        submitBtn.text('Salvando...');
                    }
                    
                    // Timeout di sicurezza per riabilitare
                    setTimeout(function() {
                        if (submitBtn.prop('disabled')) {
                            submitBtn.prop('disabled', false);
                            if (submitBtn.is('input')) {
                                submitBtn.val(originalText);
                            } else {
                                submitBtn.text(originalText);
                            }
                        }
                    }, 10000);
                });

                console.log('Modal form initialized successfully');
            } else {
                console.warn('No form found in modal to initialize');
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
            console.log('Closing modal');
            
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
                console.log('Closing modal via backdrop');
                $(this).fadeOut(300);
            }
        },

        /**
         * Gestisce tasto Escape per chiudere modal
         */
        handleKeyDown: function(e) {
            if (e.keyCode === 27) { // Escape key
                console.log('Closing modals via ESC key');
                WarehouseManager.closeAllModals();
            }
        },

        /**
         * NUOVO: Refresh dashboard dopo operazioni
         */
        refreshDashboard: function() {
            console.log('Refreshing dashboard...');
            $.ajax({
                url: warehouse_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mwm_refresh_dashboard',
                    nonce: warehouse_ajax.nonce
                },
                success: function(response) {
                    console.log('Dashboard refresh response:', response);
                    if (response.success) {
                        // Aggiorna statistiche
                        $('.mwm-stats-grid').html(response.data.stats_html);
                        
                        // Aggiorna tabella
                        $('#mwm-tablets-table tbody').html(response.data.table_html);
                        
                        console.log('Dashboard refreshed successfully');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error refreshing dashboard:', xhr, status, error);
                }
            });
        },

        /**
         * Mostra errore nella modal
         */
        showModalError: function(message) {
            const errorHtml = `
                <div class="mwm-error" style="margin: 20px 0; text-align: center; padding: 20px; background: #ffe6e6; border-left: 4px solid #dc3232; border-radius: 4px;">
                    <strong style="color: #dc3232;">Errore:</strong> ${message}
                    <br><br>
                    <button class="mwm-btn mwm-btn-secondary" onclick="WarehouseManager.closeAllModals()" style="background: #666; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">
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
                <div class="mwm-message error" style="margin: 10px 0; padding: 12px; background: #ffe6e6; border-left: 4px solid #dc3232; color: #dc3232; border-radius: 4px;">
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
                <div class="mwm-message success" style="margin: 10px 0; padding: 12px; background: #d4edda; border-left: 4px solid #28a745; color: #155724; border-radius: 4px;">
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
                    console.error('Bulk action error:', xhr, status, error);
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
        // Verifica che warehouse_ajax sia disponibile
        if (typeof warehouse_ajax === 'undefined') {
            console.error('warehouse_ajax not defined! Plugin assets not loaded correctly.');
            return;
        }
        
        console.log('warehouse_ajax available:', warehouse_ajax);
        
        WarehouseManager.init();
        
        // Esponi oggetto globalmente per uso esterno
        window.WarehouseManager = WarehouseManager;
        
        // Listener per successo form Frontend Admin
        $(document).on('frontend_admin_form_success', function(e, form, response) {
            console.log('Frontend Admin form submitted successfully:', response);
            
            // Refresh dashboard dopo 1 secondo
            setTimeout(function() {
                WarehouseManager.refreshDashboard();
                WarehouseManager.closeAllModals();
                WarehouseManager.showSuccess('Operazione completata con successo!');
            }, 1000);
        });
        
        // Listener per errori form Frontend Admin
        $(document).on('frontend_admin_form_error', function(e, form, response) {
            console.error('Frontend Admin form error:', form, response);
            WarehouseManager.showError('Errore nel salvataggio del form. Riprova.');
        });
    });

})(jQuery);
