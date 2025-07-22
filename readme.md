# My Warehouse Manager

Plugin WordPress per la gestione completa di un magazzino tablet con dashboard interattiva, form di modifica e cronologia movimenti.

## 📋 Panoramica

Il plugin **My Warehouse Manager** è stato progettato specificamente per gestire l'inventario di tablet in un'organizzazione, tracciando assegnazioni, vendite, rientri e manutenzioni. Offre un'interfaccia moderna ispirata ad AirDroid Business con funzionalità complete per la gestione quotidiana.

## ✨ Funzionalità Principali

### 🎯 Dashboard Principale
- **Tabella tablet** con visualizzazione completa di tutti i dispositivi
- **Statistiche rapide** (totali, disponibili, assegnati, etc.)
- **Azioni di gruppo** per modifiche massive (modalità kiosk, SIM)
- **Modal dettagli** per visualizzazione completa di ogni tablet
- **Ricerca e filtri** per trovare rapidamente i dispositivi

### 📝 Gestione Tablet
- **Form modifica tablet** tramite Frontend Admin
- **Campo ubicazione unificata** per tracciare posizione attuale
- **Gestione SIM** con logica condizionale avanzata
- **Configurazione modalità kiosk** e accessori

### 📦 Movimenti e Cronologia
- **Registrazione movimenti** (assegnazioni, vendite, rientri, etc.)
- **Cronologia completa** di tutti i movimenti
- **Aggiornamento automatico** dello stato tablet
- **Upload documenti** per movimenti esterni

### 🔒 Sicurezza
- **Controllo permessi** amministratore
- **Validazione CSRF** su tutte le operazioni
- **Sanitizzazione dati** e prevenzione XSS
- **Audit trail** per tracciamento attività

## 🛠️ Requisiti Tecnici

### WordPress & Plugin
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+

### Plugin Richiesti
- **Advanced Custom Fields PRO** - Gestione campi personalizzati
- **Frontend Admin by DynamiApps (FREE)** - Form di editing frontend
- **Forminator PRO** (opzionale) - Form aggiunta tablet

### Plugin Compatibili
- **Divi Theme** - Layout e styling pagine
- **User Role Editor** - Gestione ruoli personalizzati

## 📁 Struttura File

```
my-warehouse-manager/
├── my-warehouse-manager.php           # File principale plugin
├── includes/                          # Logica PHP
│   ├── class-warehouse-manager.php    # Classe principale
│   ├── dashboard-logic.php            # Shortcode dashboard e cronologia
│   ├── ajax-handlers.php              # Gestione AJAX
│   ├── frontend-forms.php             # Integrazione Frontend Admin
│   ├── security.php                   # Controlli sicurezza
│   └── helpers.php                    # Funzioni utility
├── assets/                            # File statici
│   ├── css/
│   │   └── warehouse-manager.css      # Stili plugin
│   └── js/
│       └── warehouse-manager.js       # JavaScript interazioni
├── templates/                         # Template HTML
│   └── modal-tablet-view.php          # Template modal dettagli
└── README.md                          # Documentazione
```

## 🚀 Installazione

### 1. Upload Plugin
```bash
# Carica la cartella del plugin in
wp-content/plugins/my-warehouse-manager/
```

### 2. Attivazione
1. Accedi all'admin WordPress
2. Vai su **Plugin > Plugin Installati**
3. Attiva **My Warehouse Manager**

### 3. Configurazione ACF
Assicurati di avere configurato in ACF Pro:

#### Custom Post Types
- **tablet** (slug: `tablet`)
- **movimento_magazzino** (slug: `movimento_magazzino`)

#### Tassonomie
- **destinazioni_interne** (slug: `destinazioni_interne`)
- **progetti_esterni** (slug: `progetti_esterni`)

#### Gruppi di Campi ACF
**Dettagli Tablet:**
- `stato_dispositivo` (Select)
- `data_di_carico` (Date Picker)
- `modalita_kiosk` (True/False)
- `imei_tablet` (Text)
- `sim_inserita` (True/False)
- `sim_attiva` (True/False)
- `sn_sim`, `pin_sim`, `puk_sim` (Text)
- `cover`, `scatola` (True/False)
- `tipologia` (Select)
- `note_generali_tablet` (Textarea)
- `ubicazione_attuale_tablet` (Text) - **CRUCIALE**

**Dettagli Movimento:**
- `tipo_di_movimento` (Select)
- `data_movimento` (Date Picker)
- `tablet_coinvolto` (Post Object)
- `nome_referente_assegnatario` (Text)
- `destinazione_interna` (Taxonomy Select)
- `progetto_esterno` (Taxonomy Select)
- `documento_di_consegna_ise` (File)
- `note_movimento` (Textarea)

### 4. Creazione Pagine
Crea le seguenti pagine private in WordPress:

```
/magazzino-tablet/          # Dashboard principale
/modifica-tablet/           # Form modifica tablet
/esegui-movimento/          # Form registrazione movimento
/cronologia-movimenti/      # Cronologia completa
/aggiungi-nuovo-tablet/     # Form aggiunta (Forminator)
```

## 📖 Utilizzo

### Dashboard Principale
Inserisci lo shortcode nella pagina `/magazzino-tablet/`:
```
[tablet_dashboard]
```

### Cronologia Movimenti
Inserisci lo shortcode nella pagina `/cronologia-movimenti/`:
```
[movimenti_history_table per_page="20"]
```

### Form Frontend Admin
Configura i form nelle rispettive pagine seguendo le istruzioni nella documentazione tecnica.

## ⚙️ Configurazione Avanzata

### Personalizzazione Stili
Modifica il file `assets/css/warehouse-manager.css` per personalizzare l'aspetto:

```css
/* Cambia colori principali */
.mwm-btn-primary {
    background: #your-color;
}

/* Modifica card statistiche */
.mwm-stat-card {
    background: #your-background;
}
```

### Hook Personalizzati
Il plugin espone diversi hook per estensioni:

```php
// Dopo salvataggio tablet
add_action('mwm_after_tablet_save', 'my_custom_tablet_action', 10, 2);

// Dopo salvataggio movimento
add_action('mwm_after_movimento_save', 'my_custom_movimento_action', 10, 3);
```

### Filtri Disponibili
```php
// Personalizza opzioni ubicazione
add_filter('mwm_ubicazione_options', 'my_custom_locations');

// Modifica validazione form
add_filter('mwm_form_validation', 'my_custom_validation', 10, 2);
```

## 🐛 Debugging

### Attiva Debug Mode
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Log Location
I log del plugin si trovano in:
```
/wp-content/debug.log
```

### Messaggi Debug Comuni
- `MWM INFO: Tablet modificato: ID XXX`
- `MWM INFO: Movimento registrato: ID XXX`
- `MWM ERROR: ID tablet non valido`

## 🔧 Risoluzione Problemi

### Modal Non Si Apre
1. Verifica che jQuery sia caricato
2. Controlla console browser per errori JavaScript
3. Assicurati che AJAX URL sia corretto

### Azioni di Gruppo Non Funzionano
1. Verifica permessi utente (deve essere administrator)
2. Controlla nonce di sicurezza
3. Verifica che i campi ACF esistano

### Frontend Admin Form Errori
1. Verifica configurazione campi in Frontend Admin
2. Assicurati che tutti i custom post type esistano
3. Controlla che le tassonomie siano configurate

### Performance Lente
1. Implementa caching se hai molti tablet (500+)
2. Considera l'indicizzazione database per ricerche
3. Ottimizza query ACF con `get_field()` bulk

## 📊 Best Practices

### Gestione Dati
- **Backup regolari** prima di modifiche massive
- **Validazione input** sempre attiva
- **Sanitizzazione** di tutti i dati utente

### Performance
- **Paginazione** per tabelle con molti record
- **Lazy loading** per modal con molti dettagli
- **Caching** per query ripetitive

### Sicurezza
- **Nonce verification** su tutte le operazioni
- **Capability checks** su ogni azione
- **Input sanitization** costante

## 🔄 Aggiornamenti Futuri

### V1.1 (Pianificato)
- **Export Excel/CSV** della dashboard
- **Notifiche email** per movimenti importanti
- **Widget dashboard** WordPress admin

### V1.2 (Pianificato)
- **API REST** per integrazione esterna
- **App mobile companion** 
- **Scadenze e alert** per manutenzioni

## 👥 Supporto

### Documentazione Tecnica
Consulta i commenti nel codice per dettagli implementativi specifici.

### Community
Per domande e supporto, utilizza i canali della community WordPress.

### Contributi
I contributi sono benvenuti! Segui le best practices WordPress per pull request.

---

## 📄 Licenza

Questo plugin è rilasciato sotto licenza GPL v2 o successiva, in linea con WordPress.

## 🙏 Ringraziamenti

- **WordPress Community** per l'ecosistema fantastico
- **Advanced Custom Fields** per la potenza dei custom field
- **AirDroid Business** per l'ispirazione UI/UX

---

**Versione:** 1.0.0  
**Autore:** Il Tuo Nome  
**Ultima Modifica:** 2025-01-21