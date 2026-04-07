<?php 
return [
  'title' => 'Riferimento API',
  'subtitle' => 'Documentazione per gli sviluppatori',
  'intro' => 'Benvenuti nel riferimento API completo. Questo sistema consente una profonda integrazione con il backend POS, consentendoti di creare app per camerieri, chioschi per i clienti o dashboard personalizzati.',
  'search_placeholder' => 'Cerca endpoint...',
  'base_url' => 'URL di base',
  'auth_header' => 'Autenticazione',
  'auth_desc' => 'Autenticazione tramite token al portatore. Includi "Autorizzazione: Bearer <token>" nelle intestazioni.',
  'sections' => [
    'auth' => 'Autenticazione',
    'platform' => 'Piattaforma',
    'resources' => 'Risorse',
    'customers' => 'Clienti',
    'catalog' => 'Catalogare',
    'sales' => 'Vendite e ordini',
    'kot' => 'Ordini di cucina (KOT)',
    'delivery' => 'Gestione della consegna',
    'operations' => 'Operazioni',
    'hardware' => 'Hardware e dispositivi',
    'pusher' => 'In tempo reale e push',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Login',
      'desc' => 'Ottieni il token di accesso.',
    ],
    'me' => [
      'title' => 'Profilo utente',
      'desc' => 'Ottieni l\'utente e le autorizzazioni attuali.',
    ],
    'config' => [
      'title' => 'Configurazione e funzionalità',
      'desc' => 'Ottieni impostazioni di sistema, flag di funzionalità e moduli attivi.',
    ],
    'permissions' => [
      'title' => 'Autorizzazioni',
      'desc' => 'Elenca i ruoli e le funzionalità dell\'utente.',
    ],
    'printers' => [
      'title' => 'Stampanti',
      'desc' => 'Ottieni ricevute/stampanti KOT configurate.',
    ],
    'receipts' => [
      'title' => 'Impostazioni della ricevuta',
      'desc' => 'Ottieni la configurazione dello stile della ricevuta.',
    ],
    'switch_branch' => [
      'title' => 'Cambia ramo',
      'desc' => 'Modifica il contesto del ramo attivo.',
    ],
    'langs' => [
      'title' => 'Lingue',
      'desc' => 'Ottieni le lingue disponibili.',
    ],
    'currencies' => [
      'title' => 'Valute',
      'desc' => 'Ottieni le valute di sistema.',
    ],
    'gateways' => [
      'title' => 'Gateway di pagamento',
      'desc' => 'Ottieni le credenziali del gateway pubblico.',
    ],
    'staff' => [
      'title' => 'Elenco del personale',
      'desc' => 'Ottieni tutti i membri dello staff.',
    ],
    'roles' => [
      'title' => 'Ruoli',
      'desc' => 'Ottieni i ruoli utente disponibili.',
    ],
    'areas' => [
      'title' => 'Aree',
      'desc' => 'Ottieni le aree della planimetria.',
    ],
    'addr_list' => [
      'title' => 'Elenca indirizzi',
      'desc' => 'Ottieni indirizzi per un cliente.',
    ],
    'addr_create' => [
      'title' => 'Crea indirizzo',
      'desc' => 'Aggiungi un nuovo indirizzo di consegna.',
    ],
    'addr_update' => [
      'title' => 'Aggiorna indirizzo',
      'desc' => 'Modifica un indirizzo esistente.',
    ],
    'addr_delete' => [
      'title' => 'Elimina indirizzo',
      'desc' => 'Rimuovere un indirizzo.',
    ],
    'menus' => [
      'title' => 'Menù',
      'desc' => 'Ottieni menu attivi.',
    ],
    'categories' => [
      'title' => 'Categorie',
      'desc' => 'Ottieni categorie di articoli.',
    ],
    'items' => [
      'title' => 'Tutti gli articoli',
      'desc' => 'Ottieni il catalogo completo degli articoli con prezzi e modificatori.',
    ],
    'items_filter' => [
      'title' => 'Filtra elementi',
      'desc' => 'Ottieni articoli per categoria o menu.',
    ],
    'variations' => [
      'title' => 'Variazioni degli articoli',
      'desc' => 'Ottieni varianti per un articolo specifico.',
    ],
    'modifiers' => [
      'title' => 'Modificatori di articoli',
      'desc' => 'Ottieni gruppi di modificatori per un articolo specifico.',
    ],
    'orders_create' => [
      'title' => 'Invia ordine',
      'desc' => 'Crea un nuovo ordine (Cena sul posto/Consegna).',
    ],
    'orders_list' => [
      'title' => 'Elenco ordini',
      'desc' => 'Ottieni la cronologia degli ordini.',
    ],
    'orders_detail' => [
      'title' => 'Dettagli dell\'ordine',
      'desc' => 'Ottieni l\'oggetto dell\'ordine completo.',
    ],
    'orders_status' => [
      'title' => 'Aggiorna stato',
      'desc' => 'Modificare lo stato dell\'ordine (es. preparato).',
    ],
    'orders_pay' => [
      'title' => 'Ordine di pagamento',
      'desc' => 'Registra il pagamento e chiudi l\'ordine.',
    ],
    'order_number' => [
      'title' => 'Numero di anteprima',
      'desc' => 'Ottieni il numero dell\'ordine successivo.',
    ],
    'order_types' => [
      'title' => 'Tipi di ordine',
      'desc' => 'Ottieni tipi (cenare sul posto, da asporto).',
    ],
    'actions' => [
      'title' => 'Azioni consentite',
      'desc' => 'Ottieni azioni di ordine valide (kot, fattura).',
    ],
    'platforms' => [
      'title' => 'Piattaforme di consegna',
      'desc' => 'Ottieni piattaforme di terze parti.',
    ],
    'charges' => [
      'title' => 'Costi aggiuntivi',
      'desc' => 'Ottieni costi/commissioni di servizio.',
    ],
    'taxes' => [
      'title' => 'Tasse',
      'desc' => 'Ottieni aliquote fiscali configurate.',
    ],
    'tables' => [
      'title' => 'Tabelle',
      'desc' => 'Ottieni lo stato della tabella in tempo reale.',
    ],
    'unlock' => [
      'title' => 'Sblocca tabella',
      'desc' => 'Forza lo sblocco di un tavolo.',
    ],
    'res_today' => [
      'title' => 'Le prenotazioni di oggi',
      'desc' => 'Ottieni prenotazioni per la dashboard.',
    ],
    'res_list' => [
      'title' => 'Tutte le prenotazioni',
      'desc' => 'Ottieni prenotazioni impaginate.',
    ],
    'res_create' => [
      'title' => 'Crea prenotazione',
      'desc' => 'Prenota un tavolo.',
    ],
    'res_status' => [
      'title' => 'Aggiorna prenotazione',
      'desc' => 'Modifica lo stato della prenotazione.',
    ],
    'cust_search' => [
      'title' => 'Cerca clienti',
      'desc' => 'Trova per nome/telefono.',
    ],
    'cust_save' => [
      'title' => 'Salva cliente',
      'desc' => 'Crea o aggiorna il profilo.',
    ],
    'waiters' => [
      'title' => 'Camerieri',
      'desc' => 'Ottieni personale con ruoli di cameriere/autista.',
    ],
    'kot_list' => [
      'title' => 'Elenca i KOT',
      'desc' => 'Ottieni i biglietti per l\'ordine della cucina da esporre.',
    ],
    'kot_detail' => [
      'title' => 'Dettaglio KOT',
      'desc' => 'Ottieni un singolo KOT con gli oggetti.',
    ],
    'kot_create' => [
      'title' => 'Crea KOT',
      'desc' => 'Crea un nuovo KOT per l\'ordine esistente.',
    ],
    'kot_status' => [
      'title' => 'Aggiorna stato KOT',
      'desc' => 'Modifica lo stato KOT (in_kitchen, food_ready, servito, annullato).',
    ],
    'kot_item_status' => [
      'title' => 'Aggiorna lo stato dell\'articolo',
      'desc' => 'Aggiorna lo stato del singolo articolo (in cottura, pronto, annullato).',
    ],
    'kot_places' => [
      'title' => 'Luoghi della cucina',
      'desc' => 'Ottieni postazioni/posti cucina.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'Annulla motivi',
      'desc' => 'Ottieni i motivi dell\'annullamento del KOT.',
    ],
    'order_kots' => [
      'title' => 'Ordina KOT',
      'desc' => 'Ottieni tutti i KOT per un ordine specifico.',
    ],
    'delivery_settings' => [
      'title' => 'Impostazioni di consegna',
      'desc' => 'Ottieni la configurazione della consegna in filiale (raggio, tariffe, pianificazione).',
    ],
    'delivery_fee_calc' => [
      'title' => 'Calcola tariffa',
      'desc' => 'Calcola le spese di consegna in base alla posizione del cliente.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Livelli tariffari',
      'desc' => 'Ottieni livelli tariffari basati sulla distanza.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Elenco piattaforme',
      'desc' => 'Ottieni piattaforme di consegna attive (Uber Eats, ecc.).',
    ],
    'delivery_platform_get' => [
      'title' => 'Dettaglio della piattaforma',
      'desc' => 'Ottieni una piattaforma di consegna unica con informazioni sulle commissioni.',
    ],
    'delivery_platform_create' => [
      'title' => 'Crea piattaforma',
      'desc' => 'Aggiungi una nuova piattaforma di consegna.',
    ],
    'delivery_platform_update' => [
      'title' => 'Aggiorna piattaforma',
      'desc' => 'Modificare le impostazioni/commissioni della piattaforma.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Elimina piattaforma',
      'desc' => 'Rimuovi o disattiva la piattaforma di consegna.',
    ],
    'delivery_exec_list' => [
      'title' => 'Elenco dirigenti',
      'desc' => 'Ottieni personale di consegna con il filtro di stato.',
    ],
    'delivery_exec_create' => [
      'title' => 'Crea esecutivo',
      'desc' => 'Aggiungi un nuovo responsabile delle consegne.',
    ],
    'delivery_exec_update' => [
      'title' => 'Aggiornamento esecutivo',
      'desc' => 'Modifica le informazioni del responsabile della consegna.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Elimina esecutivo',
      'desc' => 'Rimuovere o disattivare il responsabile della consegna.',
    ],
    'delivery_exec_status' => [
      'title' => 'Stato esecutivo',
      'desc' => 'Aggiorna disponibilità (disponibile/in_consegna/inattivo).',
    ],
    'delivery_assign' => [
      'title' => 'Assegna consegna',
      'desc' => 'Assegnare esecutivo/piattaforma all\'ordine.',
    ],
    'delivery_order_status' => [
      'title' => 'Stato di consegna',
      'desc' => 'Aggiorna lo stato di consegna dell\'ordine (in preparazione, in uscita, consegnato).',
    ],
    'delivery_orders' => [
      'title' => 'Ordini di consegna',
      'desc' => 'Ottieni un elenco filtrato degli ordini di consegna.',
    ],
    'multipos_reg' => [
      'title' => 'Registra dispositivo',
      'desc' => 'Collegare l\'hardware fisico.',
    ],
    'multipos_check' => [
      'title' => 'Controlla dispositivo',
      'desc' => 'Verifica la registrazione.',
    ],
    'notif_token' => [
      'title' => 'Registra FCM',
      'desc' => 'Salva token push.',
    ],
    'notif_list' => [
      'title' => 'Notifiche',
      'desc' => 'Ricevi avvisi in-app.',
    ],
    'notif_read' => [
      'title' => 'Segna letto',
      'desc' => 'Ignora notifica.',
    ],
    'pusher_settings' => [
      'title' => 'Ottieni le impostazioni dello spintore',
      'desc' => 'Recupera la configurazione completa del Pusher. Accessibile a tutti gli utenti autenticati (superadmin, admin, staff).',
    ],
    'pusher_broadcast' => [
      'title' => 'Ottieni le impostazioni di trasmissione',
      'desc' => 'Ottieni la configurazione della trasmissione in tempo reale di Pusher. Impostazioni a livello di sistema per tutti gli utenti.',
    ],
    'pusher_beams' => [
      'title' => 'Ottieni le impostazioni delle travi',
      'desc' => 'Ottieni la configurazione delle notifiche push di Pusher Beams. Accessibile a tutti gli utenti autenticati.',
    ],
    'pusher_status' => [
      'title' => 'Controlla lo stato dello spintore',
      'desc' => 'Controllo rapido dello stato per verificare se i servizi Pusher sono abilitati. Disponibile per tutti gli utenti.',
    ],
    'pusher_authorize' => [
      'title' => 'Autorizza canale',
      'desc' => 'Autorizzare l\'accesso degli utenti ai canali privati ​​e di presenza. Richiede un\'autenticazione valida.',
    ],
    'pusher_presence' => [
      'title' => 'Ottieni membri presenti',
      'desc' => 'Recupera l\'elenco degli utenti attualmente connessi a un canale di presenza. Dati a livello di sistema.',
    ],
  ],
];