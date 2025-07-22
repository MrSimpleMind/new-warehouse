# My Warehouse Manager

Plugin WordPress per la gestione completa di un magazzino tablet con dashboard interattiva, form di modifica in modal e gestione destinazioni unificate.

## 📋 Panoramica

Il plugin **My Warehouse Manager** è stato progettato specificamente per gestire l'inventario di tablet in un'organizzazione, tracciando assegnazioni, vendite, rientri e manutenzioni tramite una dashboard centralizzata con form modal integrate.

## ✨ Funzionalità Principali

### 🎯 Dashboard Principale
- **Tabella tablet** con visualizzazione completa di tutti i dispositivi
- **Statistiche rapide** (totali, disponibili, assegnati, etc.)
- **Azioni di gruppo** per modifiche massive (modalità kiosk, SIM)
- **Modal dettagli** per visualizzazione completa di ogni tablet
- **Form modal** per modifica e movimenti senza cambiare pagina
- **Ricerca e filtri** per trovare rapidamente i dispositivi

### 📝 Gestione Tablet
- **Form modal modifica tablet** tramite Frontend Admin
- **Tassonomia destinazioni unificata** per tracciare ubicazione
- **Gestione SIM** con logica condizionale avanzata
- **Configurazione modalità kiosk** e accessori

### 📦 Gestione Movimenti
- **Form modal registrazione movimenti** senza reload pagina
- **Aggiornamento automatico** dello stato tablet
- **Gestione destinazioni** tramite tassonomia unificata
- **Upload documenti** per movimenti esterni

## 🛠️ Requisiti Tecnici

### WordPress & Plugin
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+

### Plugin Richiesti
- **Advanced Custom Fields PRO** - Gestione campi personalizzati
- **Frontend Admin by DynamiApps (FREE)** - Form di editing modal

### Plugin Compatibili
- **Divi Theme** - Layout e styling pagine
- **User Role Editor** - Gestione ruoli personalizzati

## 📁 Struttura File

```
my-warehouse-manager/
├── my-warehouse-manager.php           # File principale plugin
├── includes/                          # Logica PHP
│   ├── class-warehouse-manager.php    # Classe principale
│   ├── dashboard-logic.php            # Shortcode dashboard
│   ├── ajax-handlers.php              # Gestione AJAX e modal
│   ├── frontend-forms.php             # Integrazione Frontend Admin
│   └── helpers.php                    # Funzioni utility
├── assets/                            # File statici
│   ├── css/
│   │   └── warehouse-manager.css      # Stili plugin
│   └── js/
│       └── warehouse-manager.js       # JavaScript modal e interazioni
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

### 3. Configurazione ACF Pro

#### Custom Post Type
- **tablet** (slug: `tablet`)

#### Tassonomia Unificata
- **destinazione** (slug: `destinazione`) - Associata al CPT tablet
  - Contiene tutte le destinazioni interne e progetti esterni in una singola tassonomia

#### Gruppi di Campi ACF

**Dettagli Tablet:**
- `stato_dispositivo` (Select: disponibile, assegnato, in_manutenzione, venduto, rientrato)
- `data_di_carico` (Date Picker)
- `modalita_kiosk` (True/False)
- `imei_tablet` (Text)
- `sim_inserita` (True/False)
- `sim_attiva` (True/False)
- `sn_sim`, `pin_sim`, `puk_sim` (Text)
- `cover`, `scatola` (True/False)
- `tipologia` (Select: tablet_android, tablet_ios, tablet_windows)
- `note_generali_tablet` (Textarea)
- `dove` (Taxonomy Select - Destinazione) - **CRUCIALE per ubicazione**

### 4. Configurazione Frontend Admin

Crea i seguenti form in Frontend Admin by DynamiApps:

#### Form ID 204 - Aggiungi Nuovo Tablet
- **Tipo**: New Post Form
- **Post Type**: tablet
- **Campi**: Tutti i custom field del tablet

#### Form ID 110 - Modifica Tablet 
- **Tipo**: Edit Post Form
- **Post Type**: tablet  
- **Campi**: Tutti i custom field modificabili

#### Form ID 125 - Registra Movimento
- **Tipo**: Edit Post Form
- **Post Type**: tablet
- **Funzione**: Aggiorna destinazione del tablet

### 5. Creazione Pagine
Crea una singola pagina principale in WordPress:

```
/magazzino-tablet/          # Dashboard con modal integrate
```

## 📖 Utilizzo

### Dashboard con Modal
Inserisci lo shortcode nella pagina `/magazzino-tablet/`:
```
[tablet_dashboard]
```

### Form Modal Integrate
Le form sono integrate come modal nella dashboard:
- **Aggiungi tablet**: `[frontend_admin form=204]`
- **Modifica tablet**: `[frontend_admin form=110]` 
- **Registra movimento**: `[frontend_admin form=125]`

## ⚙️ Configurazione Avanzata

### Gestione Destinazioni Unificate
La tassonomia `destinazione` contiene:
- Destinazioni interne (uffici, reparti, etc.)
- Progetti esterni (clienti, partner, etc.)
- Posizioni temporanee (magazzino, manutenzione, etc.)

### Personalizzazione Stili
Modifica il file `assets/css/warehouse-manager.css`:

```css
/* Modal personalizzate */
.mwm-modal-content {
    max-width: 900px; /* Più largo per form */
}

/* Form modal responsive */
.frontend-admin-form {
    padding: 20px;
}
```

### JavaScript Personalizzato
Il file `warehouse-manager.js` gestisce:
- Apertura modal per dettagli, modifica e movimento
- Validazione form lato client
- Aggiornamento dinamico della dashboard
- Gestione azioni di gruppo

## 🔧 Architettura Modal

### Modal Manager
Il plugin usa un sistema unificato di modal:
1. **Modal dettagli** - Visualizzazione read-only
2. **Modal modifica** - Form Frontend Admin per editing
3. **Modal movimento** - Form Frontend Admin per movimenti

### Flow di Lavoro
1. **Dashboard** → Click bottone → **Modal aperta**
2. **Form compilazione** → **Submit AJAX** → **Dashboard aggiornata**
3. **Nessun reload pagina** → **Esperienza fluida**

## 🏗️ Sviluppo

### Hook Personalizzati
```php
// Dopo salvataggio tablet
add_action('mwm_after_tablet_save', 'my_custom_tablet_action', 10, 2);

// Dopo apertura modal
add_action('mwm_before_modal_open', 'my_modal_setup', 10, 2);
```

### Filtri Disponibili
```php
// Personalizza contenuto modal
add_filter('mwm_modal_content', 'my_modal_content', 10, 3);

// Modifica validazione form
add_filter('mwm_form_validation', 'my_custom_validation', 10, 2);
```

## 🐛 Debugging

### Console Browser
Verifica errori JavaScript:
```javascript
// Console log attivi in modalità sviluppo
console.log('MWM: Modal opened for tablet ID:', tabletId);
```

### Log PHP
```php
// Log personalizzati
error_log('MWM: Tablet aggiornato ID ' . $tablet_id);
```

## 🔄 Roadmap

### Versione Corrente (1.0)
- ✅ Dashboard unificata con modal
- ✅ Tassonomia destinazioni unificata  
- ✅ Form Frontend Admin integrate
- ✅ Rimozione dipendenze obsolete

### Prossime Versioni
- **Export/Import** dati tablet
- **Notifiche in-app** per cambiamenti
- **Filtri avanzati** per grandi inventari
- **API REST** per integrazioni esterne

## 📊 Performance

### Ottimizzazioni
- **Modal lazy loading** - Caricamento contenuto solo quando necessario
- **AJAX calls minimizzate** - Una chiamata per operazione
- **Cache browser** - Asset statici cached
- **Database ottimizzato** - Query efficienti per grandi dataset

## 👥 Supporto

### Documentazione Tecnica
Consulta i commenti nel codice per dettagli implementativi specifici, specialmente:
- `dashboard-logic.php` - Logica shortcode e modal
- `ajax-handlers.php` - Gestione chiamate AJAX
- `warehouse-manager.js` - Interazioni client-side

---

## 📄 Licenza

Questo plugin è rilasciato sotto licenza GPL v2 o successiva, in linea con WordPress.

## 🙏 Ringraziamenti

- **WordPress Community** per l'ecosistema
- **Advanced Custom Fields** per la gestione field
- **Frontend Admin by DynamiApps** per le form modal
- **AirDroid Business** per l'ispirazione UI/UX

---

**Versione:** 1.0.0  
**Architettura:** Unificata con Modal  
**Ultima Modifica:** 2025-01-22
