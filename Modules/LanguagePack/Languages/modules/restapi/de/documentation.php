<?php 
return [
  'title' => 'API-Referenz',
  'subtitle' => 'Entwicklerdokumentation',
  'intro' => 'Willkommen zur vollständigen API-Referenz. Dieses System ermöglicht eine tiefe Integration mit dem POS-Backend, sodass Sie Kellner-Apps, Kundenkioske oder benutzerdefinierte Dashboards erstellen können.',
  'search_placeholder' => 'Endpunkte suchen...',
  'base_url' => 'Basis-URL',
  'auth_header' => 'Authentifizierung',
  'auth_desc' => 'Authentifizieren Sie sich über Bearer Token. Fügen Sie „Authorization: Bearer <token>“ in die Header ein.',
  'sections' => [
    'auth' => 'Authentifizierung',
    'platform' => 'Plattform',
    'resources' => 'Ressourcen',
    'customers' => 'Kunden',
    'catalog' => 'Katalog',
    'sales' => 'Verkäufe und Bestellungen',
    'kot' => 'Küchenbestellungen (KOT)',
    'delivery' => 'Liefermanagement',
    'operations' => 'Operationen',
    'hardware' => 'Hardware und Geräte',
    'pusher' => 'Echtzeit und Push',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Login',
      'desc' => 'Zugriffstoken erhalten.',
    ],
    'me' => [
      'title' => 'Benutzerprofil',
      'desc' => 'Aktuellen Benutzer und Berechtigungen abrufen.',
    ],
    'config' => [
      'title' => 'Konfiguration und Funktionen',
      'desc' => 'Rufen Sie Systemeinstellungen, Feature-Flags und aktive Module ab.',
    ],
    'permissions' => [
      'title' => 'Berechtigungen',
      'desc' => 'Listen Sie Benutzerrollen und -funktionen auf.',
    ],
    'printers' => [
      'title' => 'Drucker',
      'desc' => 'Erhalten Sie konfigurierte Beleg-/KOT-Drucker.',
    ],
    'receipts' => [
      'title' => 'Empfangseinstellungen',
      'desc' => 'Rufen Sie die Konfiguration des Quittungsstils ab.',
    ],
    'switch_branch' => [
      'title' => 'Zweig wechseln',
      'desc' => 'Aktiven Zweigkontext ändern.',
    ],
    'langs' => [
      'title' => 'Sprachen',
      'desc' => 'Erhalten Sie verfügbare Sprachen.',
    ],
    'currencies' => [
      'title' => 'Währungen',
      'desc' => 'Holen Sie sich Systemwährungen.',
    ],
    'gateways' => [
      'title' => 'Zahlungsgateways',
      'desc' => 'Erhalten Sie Anmeldeinformationen für das öffentliche Gateway.',
    ],
    'staff' => [
      'title' => 'Personalliste',
      'desc' => 'Holen Sie sich alle Mitarbeiter.',
    ],
    'roles' => [
      'title' => 'Rollen',
      'desc' => 'Erhalten Sie verfügbare Benutzerrollen.',
    ],
    'areas' => [
      'title' => 'Bereiche',
      'desc' => 'Holen Sie sich Grundrissflächen.',
    ],
    'addr_list' => [
      'title' => 'Adressen auflisten',
      'desc' => 'Adressen für einen Kunden abrufen.',
    ],
    'addr_create' => [
      'title' => 'Adresse erstellen',
      'desc' => 'Fügen Sie eine neue Lieferadresse hinzu.',
    ],
    'addr_update' => [
      'title' => 'Adresse aktualisieren',
      'desc' => 'Ändern Sie eine vorhandene Adresse.',
    ],
    'addr_delete' => [
      'title' => 'Adresse löschen',
      'desc' => 'Entfernen Sie eine Adresse.',
    ],
    'menus' => [
      'title' => 'Menüs',
      'desc' => 'Erhalten Sie aktive Menüs.',
    ],
    'categories' => [
      'title' => 'Kategorien',
      'desc' => 'Artikelkategorien abrufen.',
    ],
    'items' => [
      'title' => 'Alle Artikel',
      'desc' => 'Erhalten Sie den vollständigen Artikelkatalog mit Preisen und Modifikatoren.',
    ],
    'items_filter' => [
      'title' => 'Elemente filtern',
      'desc' => 'Erhalten Sie Artikel nach Kategorie oder Menü.',
    ],
    'variations' => [
      'title' => 'Artikelvariationen',
      'desc' => 'Erhalten Sie Variationen für einen bestimmten Artikel.',
    ],
    'modifiers' => [
      'title' => 'Gegenstandsmodifikatoren',
      'desc' => 'Modifikatorgruppen für ein bestimmtes Element abrufen.',
    ],
    'orders_create' => [
      'title' => 'Bestellung absenden',
      'desc' => 'Erstellen Sie eine neue Bestellung (Dine-in/Delivery).',
    ],
    'orders_list' => [
      'title' => 'Bestellungen auflisten',
      'desc' => 'Bestellhistorie abrufen.',
    ],
    'orders_detail' => [
      'title' => 'Bestelldetails',
      'desc' => 'Holen Sie sich das vollständige Bestellobjekt.',
    ],
    'orders_status' => [
      'title' => 'Aktualisierungsstatus',
      'desc' => 'Auftragsstatus ändern (z. B. vorbereitet).',
    ],
    'orders_pay' => [
      'title' => 'Zahlungsauftrag',
      'desc' => 'Zahlung verbuchen und Bestellung abschließen.',
    ],
    'order_number' => [
      'title' => 'Vorschaunummer',
      'desc' => 'Holen Sie sich die nächste Bestellnummer.',
    ],
    'order_types' => [
      'title' => 'Auftragsarten',
      'desc' => 'Holen Sie sich Sorten (Dine-in, Takeaway).',
    ],
    'actions' => [
      'title' => 'Zulässige Aktionen',
      'desc' => 'Erhalten Sie gültige Bestellaktionen (Kot, Rechnung).',
    ],
    'platforms' => [
      'title' => 'Lieferplattformen',
      'desc' => 'Holen Sie sich Plattformen von Drittanbietern.',
    ],
    'charges' => [
      'title' => 'Zusätzliche Gebühren',
      'desc' => 'Erhalten Sie Servicegebühren/Gebühren.',
    ],
    'taxes' => [
      'title' => 'Steuern',
      'desc' => 'Erhalten Sie konfigurierte Steuersätze.',
    ],
    'tables' => [
      'title' => 'Tische',
      'desc' => 'Erhalten Sie den Tischstatus in Echtzeit.',
    ],
    'unlock' => [
      'title' => 'Tisch entsperren',
      'desc' => 'Erzwingen Sie die Entsperrung eines Tisches.',
    ],
    'res_today' => [
      'title' => 'Heutige Reservierungen',
      'desc' => 'Erhalten Sie Reservierungen für das Dashboard.',
    ],
    'res_list' => [
      'title' => 'Alle Reservierungen',
      'desc' => 'Erhalten Sie paginierte Reservierungen.',
    ],
    'res_create' => [
      'title' => 'Reservierung erstellen',
      'desc' => 'Reservieren Sie einen Tisch.',
    ],
    'res_status' => [
      'title' => 'Reservierung aktualisieren',
      'desc' => 'Reservierungsstatus ändern.',
    ],
    'cust_search' => [
      'title' => 'Kunden suchen',
      'desc' => 'Nach Name/Telefon suchen.',
    ],
    'cust_save' => [
      'title' => 'Kunde speichern',
      'desc' => 'Profil erstellen oder aktualisieren.',
    ],
    'waiters' => [
      'title' => 'Kellner',
      'desc' => 'Stellen Sie Mitarbeiter mit der Rolle Kellner/Fahrer ein.',
    ],
    'kot_list' => [
      'title' => 'KOTs auflisten',
      'desc' => 'Holen Sie sich Küchenbestellscheine zur Ausstellung.',
    ],
    'kot_detail' => [
      'title' => 'KOT-Detail',
      'desc' => 'Erhalten Sie einzelne KOT mit Gegenständen.',
    ],
    'kot_create' => [
      'title' => 'KOT erstellen',
      'desc' => 'Erstellen Sie ein neues KOT für die bestehende Bestellung.',
    ],
    'kot_status' => [
      'title' => 'KOT-Status aktualisieren',
      'desc' => 'KOT-Status ändern (in_kitchen, food_ready, serviert, storniert).',
    ],
    'kot_item_status' => [
      'title' => 'Artikelstatus aktualisieren',
      'desc' => 'Aktualisieren Sie den Status einzelner Artikel (Kochen, Fertig, Abgebrochen).',
    ],
    'kot_places' => [
      'title' => 'Küchenplätze',
      'desc' => 'Holen Sie sich Küchenstationen/-plätze.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'Gründe für die Stornierung',
      'desc' => 'Erhalten Sie KOT-Stornierungsgründe.',
    ],
    'order_kots' => [
      'title' => 'Bestellen Sie KOTs',
      'desc' => 'Holen Sie sich alle KOTs für eine bestimmte Bestellung.',
    ],
    'delivery_settings' => [
      'title' => 'Liefereinstellungen',
      'desc' => 'Rufen Sie die Konfiguration der Filialzustellung ab (Radius, Gebühren, Zeitplan).',
    ],
    'delivery_fee_calc' => [
      'title' => 'Gebühr berechnen',
      'desc' => 'Berechnen Sie die Liefergebühr basierend auf dem Standort des Kunden.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Gebührenstufen',
      'desc' => 'Erhalten Sie distanzbasierte Gebührenstufen.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Plattformen auflisten',
      'desc' => 'Erhalten Sie aktive Lieferplattformen (Uber Eats usw.).',
    ],
    'delivery_platform_get' => [
      'title' => 'Plattformdetail',
      'desc' => 'Erhalten Sie eine einzige Lieferplattform mit Provisionsinformationen.',
    ],
    'delivery_platform_create' => [
      'title' => 'Plattform erstellen',
      'desc' => 'Neue Lieferplattform hinzufügen.',
    ],
    'delivery_platform_update' => [
      'title' => 'Plattform aktualisieren',
      'desc' => 'Plattformeinstellungen/Provision ändern.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Plattform löschen',
      'desc' => 'Lieferplattform entfernen oder deaktivieren.',
    ],
    'delivery_exec_list' => [
      'title' => 'Führungskräfte auflisten',
      'desc' => 'Erhalten Sie Zusteller mit Statusfilter.',
    ],
    'delivery_exec_create' => [
      'title' => 'Führungskraft erstellen',
      'desc' => 'Fügen Sie einen neuen Liefermanager hinzu.',
    ],
    'delivery_exec_update' => [
      'title' => 'Update Executive',
      'desc' => 'Ändern Sie die Informationen des Zustellers.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Führungskraft löschen',
      'desc' => 'Liefermanager entfernen oder deaktivieren.',
    ],
    'delivery_exec_status' => [
      'title' => 'Führungsstatus',
      'desc' => 'Update-Verfügbarkeit (verfügbar/bei Lieferung/inaktiv).',
    ],
    'delivery_assign' => [
      'title' => 'Lieferung zuordnen',
      'desc' => 'Ordnen Sie der Bestellung Führungskraft/Plattform zu.',
    ],
    'delivery_order_status' => [
      'title' => 'Lieferstatus',
      'desc' => 'Aktualisieren Sie den Lieferstatus der Bestellung (wird vorbereitet, out_for_delivery, geliefert).',
    ],
    'delivery_orders' => [
      'title' => 'Lieferaufträge',
      'desc' => 'Erhalten Sie eine gefilterte Liste der Lieferaufträge.',
    ],
    'multipos_reg' => [
      'title' => 'Gerät registrieren',
      'desc' => 'Verknüpfen Sie physische Hardware.',
    ],
    'multipos_check' => [
      'title' => 'Überprüfen Sie das Gerät',
      'desc' => 'Überprüfen Sie die Registrierung.',
    ],
    'notif_token' => [
      'title' => 'Registrieren Sie FCM',
      'desc' => 'Push-Token speichern.',
    ],
    'notif_list' => [
      'title' => 'Benachrichtigungen',
      'desc' => 'Erhalten Sie In-App-Benachrichtigungen.',
    ],
    'notif_read' => [
      'title' => 'Mark Read',
      'desc' => 'Benachrichtigung verwerfen.',
    ],
    'pusher_settings' => [
      'title' => 'Holen Sie sich Pusher-Einstellungen',
      'desc' => 'Rufen Sie die vollständige Pusher-Konfiguration ab. Zugriff für alle authentifizierten Benutzer (Superadmin, Admin, Mitarbeiter).',
    ],
    'pusher_broadcast' => [
      'title' => 'Rufen Sie die Broadcast-Einstellungen ab',
      'desc' => 'Holen Sie sich die Pusher-Echtzeit-Broadcast-Konfiguration. Systemweite Einstellungen für alle Benutzer.',
    ],
    'pusher_beams' => [
      'title' => 'Balkeneinstellungen abrufen',
      'desc' => 'Holen Sie sich die Push-Benachrichtigungskonfiguration für Pusher Beams. Für alle authentifizierten Benutzer zugänglich.',
    ],
    'pusher_status' => [
      'title' => 'Überprüfen Sie den Pusher-Status',
      'desc' => 'Schnelle Statusprüfung, um zu überprüfen, ob Pusher-Dienste aktiviert sind. Für alle Benutzer verfügbar.',
    ],
    'pusher_authorize' => [
      'title' => 'Kanal autorisieren',
      'desc' => 'Autorisieren Sie den Benutzerzugriff auf private Kanäle und Präsenzkanäle. Erfordert eine gültige Authentifizierung.',
    ],
    'pusher_presence' => [
      'title' => 'Erhalten Sie Präsenzmitglieder',
      'desc' => 'Rufen Sie eine Liste der Benutzer ab, die derzeit mit einem Präsenzkanal verbunden sind. Systemweite Daten.',
    ],
  ],
];