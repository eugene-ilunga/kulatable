<?php 
return [
  'title' => 'Referință API',
  'subtitle' => 'Documentația pentru dezvoltatori',
  'intro' => 'Bun venit la referința completă API. Acest sistem permite integrarea profundă cu backend-ul POS, permițându-vă să creați aplicații pentru chelneri, chioșcuri pentru clienți sau tablouri de bord personalizate.',
  'search_placeholder' => 'Căutați puncte finale...',
  'base_url' => 'Adresa URL de bază',
  'auth_header' => 'Autentificare',
  'auth_desc' => 'Autentificare prin Bearer Token. Includeți `Authorization: Bearer <token>` în anteturi.',
  'sections' => [
    'auth' => 'Autentificare',
    'platform' => 'Platformă',
    'resources' => 'Resurse',
    'customers' => 'Clienții',
    'catalog' => 'Catalog',
    'sales' => 'Vânzări și comenzi',
    'kot' => 'Comenzi de bucătărie (KOT)',
    'delivery' => 'Managementul livrărilor',
    'operations' => 'Operațiuni',
    'hardware' => 'Hardware și dispozitive',
    'pusher' => 'În timp real și Push',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Log in',
      'desc' => 'Obțineți un token de acces.',
    ],
    'me' => [
      'title' => 'Profil de utilizator',
      'desc' => 'Obțineți utilizatorul actual și permisiunile.',
    ],
    'config' => [
      'title' => 'Configurare și caracteristici',
      'desc' => 'Obțineți setări de sistem, steaguri de caracteristici și module active.',
    ],
    'permissions' => [
      'title' => 'Permisiuni',
      'desc' => 'Listați rolurile și capabilitățile utilizatorilor.',
    ],
    'printers' => [
      'title' => 'Imprimante',
      'desc' => 'Obțineți chitanțe/imprimante KOT configurate.',
    ],
    'receipts' => [
      'title' => 'Setări de chitanță',
      'desc' => 'Obțineți configurația stilului chitanței.',
    ],
    'switch_branch' => [
      'title' => 'Schimbați filiala',
      'desc' => 'Schimbați contextul de ramură activă.',
    ],
    'langs' => [
      'title' => 'Limbi',
      'desc' => 'Obțineți limbile disponibile.',
    ],
    'currencies' => [
      'title' => 'Monede',
      'desc' => 'Obțineți monede de sistem.',
    ],
    'gateways' => [
      'title' => 'Gateway-uri de plată',
      'desc' => 'Obțineți acreditări de gateway public.',
    ],
    'staff' => [
      'title' => 'Lista personalului',
      'desc' => 'Obțineți toți membrii personalului.',
    ],
    'roles' => [
      'title' => 'Roluri',
      'desc' => 'Obțineți roluri de utilizator disponibile.',
    ],
    'areas' => [
      'title' => 'Zone',
      'desc' => 'Obțineți zone cu plan de etaj.',
    ],
    'addr_list' => [
      'title' => 'Listează adrese',
      'desc' => 'Obțineți adrese pentru un client.',
    ],
    'addr_create' => [
      'title' => 'Creați adresă',
      'desc' => 'Adăugați o nouă adresă de livrare.',
    ],
    'addr_update' => [
      'title' => 'Actualizați adresa',
      'desc' => 'Modificați o adresă existentă.',
    ],
    'addr_delete' => [
      'title' => 'Ștergeți adresa',
      'desc' => 'Eliminați o adresă.',
    ],
    'menus' => [
      'title' => 'Meniuri',
      'desc' => 'Obțineți meniuri active.',
    ],
    'categories' => [
      'title' => 'Categorii',
      'desc' => 'Obțineți categorii de articole.',
    ],
    'items' => [
      'title' => 'Toate articolele',
      'desc' => 'Obțineți catalogul complet de articole cu prețuri și modificatori.',
    ],
    'items_filter' => [
      'title' => 'Filtrați articole',
      'desc' => 'Obțineți articole după categorie sau meniu.',
    ],
    'variations' => [
      'title' => 'Variațiile articolului',
      'desc' => 'Obțineți variante pentru un anumit articol.',
    ],
    'modifiers' => [
      'title' => 'Modificatori de articole',
      'desc' => 'Obțineți grupuri de modificatori pentru un anumit articol.',
    ],
    'orders_create' => [
      'title' => 'Trimite comanda',
      'desc' => 'Creați o nouă comandă (Dine-in/Delivery).',
    ],
    'orders_list' => [
      'title' => 'Lista comenzi',
      'desc' => 'Obțineți istoricul comenzilor.',
    ],
    'orders_detail' => [
      'title' => 'Detaliu comanda',
      'desc' => 'Obțineți obiectul de comandă complet.',
    ],
    'orders_status' => [
      'title' => 'Actualizare stare',
      'desc' => 'Modificați starea comenzii (de exemplu, pregătită).',
    ],
    'orders_pay' => [
      'title' => 'Ordin de plată',
      'desc' => 'Înregistrați plata și închideți comanda.',
    ],
    'order_number' => [
      'title' => 'Numărul de previzualizare',
      'desc' => 'Obțineți următorul număr de comandă.',
    ],
    'order_types' => [
      'title' => 'Tipuri de comenzi',
      'desc' => 'Obțineți tipuri (Dine-in, Takeaway).',
    ],
    'actions' => [
      'title' => 'Acțiuni permise',
      'desc' => 'Obțineți acțiuni valide de comandă (kot, factura).',
    ],
    'platforms' => [
      'title' => 'Platforme de livrare',
      'desc' => 'Obțineți platforme terțe.',
    ],
    'charges' => [
      'title' => 'Taxe suplimentare',
      'desc' => 'Obțineți taxe/taxe pentru servicii.',
    ],
    'taxes' => [
      'title' => 'Impozite',
      'desc' => 'Obțineți cote de impozitare configurate.',
    ],
    'tables' => [
      'title' => 'Mesele',
      'desc' => 'Obțineți starea tabelului în timp real.',
    ],
    'unlock' => [
      'title' => 'Deblocați tabelul',
      'desc' => 'Deblocarea forțată a unei mese.',
    ],
    'res_today' => [
      'title' => 'Rezervările de azi',
      'desc' => 'Obțineți rezervări pentru tabloul de bord.',
    ],
    'res_list' => [
      'title' => 'Toate Rezervările',
      'desc' => 'Obțineți rezervări paginate.',
    ],
    'res_create' => [
      'title' => 'Creați rezervare',
      'desc' => 'Rezervă o masă.',
    ],
    'res_status' => [
      'title' => 'Actualizați rezervarea',
      'desc' => 'Schimbați starea rezervării.',
    ],
    'cust_search' => [
      'title' => 'Căutați clienți',
      'desc' => 'Găsiți după nume/telefon.',
    ],
    'cust_save' => [
      'title' => 'Salvați clientul',
      'desc' => 'Creați sau actualizați profilul.',
    ],
    'waiters' => [
      'title' => 'Ospatari',
      'desc' => 'Obțineți personal cu roluri de chelner/șofer.',
    ],
    'kot_list' => [
      'title' => 'Listați KOT-urile',
      'desc' => 'Obțineți bilete de comandă pentru bucătărie pentru afișare.',
    ],
    'kot_detail' => [
      'title' => 'Detaliu KOT',
      'desc' => 'Obțineți un singur KOT cu articole.',
    ],
    'kot_create' => [
      'title' => 'Creați KOT',
      'desc' => 'Creați un nou KOT pentru comanda existentă.',
    ],
    'kot_status' => [
      'title' => 'Actualizați starea KOT',
      'desc' => 'Schimbați starea KOT (in_kitchen, food_ready, servit, anulat).',
    ],
    'kot_item_status' => [
      'title' => 'Actualizați starea articolului',
      'desc' => 'Actualizați starea articolului individual (gătit, gata, anulat).',
    ],
    'kot_places' => [
      'title' => 'Locuri de Bucătărie',
      'desc' => 'Obțineți posturi/locuri de bucătărie.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'Motive pentru anulare',
      'desc' => 'Obțineți motivele anulării KOT.',
    ],
    'order_kots' => [
      'title' => 'Comandați KOT-uri',
      'desc' => 'Obțineți toate KOT-urile pentru o anumită comandă.',
    ],
    'delivery_settings' => [
      'title' => 'Setări de livrare',
      'desc' => 'Obțineți configurația de livrare a sucursalei (rază, taxe, program).',
    ],
    'delivery_fee_calc' => [
      'title' => 'Calculați taxa',
      'desc' => 'Calculați taxa de livrare în funcție de locația clientului.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Niveluri de taxe',
      'desc' => 'Obțineți niveluri de taxe bazate pe distanță.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Listează platformele',
      'desc' => 'Obțineți platforme de livrare active (Uber Eats etc.).',
    ],
    'delivery_platform_get' => [
      'title' => 'Detaliu platformă',
      'desc' => 'Obțineți o singură platformă de livrare cu informații despre comisioane.',
    ],
    'delivery_platform_create' => [
      'title' => 'Creați platformă',
      'desc' => 'Adăugați o nouă platformă de livrare.',
    ],
    'delivery_platform_update' => [
      'title' => 'Actualizați platforma',
      'desc' => 'Modificați setările/comisionul platformei.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Ștergeți platforma',
      'desc' => 'Eliminați sau dezactivați platforma de livrare.',
    ],
    'delivery_exec_list' => [
      'title' => 'Lista directori',
      'desc' => 'Obțineți personal de livrare cu filtru de stare.',
    ],
    'delivery_exec_create' => [
      'title' => 'Creați executiv',
      'desc' => 'Adăugați un nou director de livrare.',
    ],
    'delivery_exec_update' => [
      'title' => 'Update Executive',
      'desc' => 'Modificați informațiile executive de livrare.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Șterge Executive',
      'desc' => 'Eliminați sau dezactivați executivul de livrare.',
    ],
    'delivery_exec_status' => [
      'title' => 'Statutul executiv',
      'desc' => 'Actualizați disponibilitatea (disponibil/la_livrare/inactiv).',
    ],
    'delivery_assign' => [
      'title' => 'Atribuiți livrarea',
      'desc' => 'Atribuiți executiv/platformă la comandă.',
    ],
    'delivery_order_status' => [
      'title' => 'Starea livrării',
      'desc' => 'Actualizați starea de livrare a comenzii (pregătire, out_for_delivery, livrate).',
    ],
    'delivery_orders' => [
      'title' => 'Comenzi de livrare',
      'desc' => 'Obțineți o listă filtrată de comenzi de livrare.',
    ],
    'multipos_reg' => [
      'title' => 'Înregistrați dispozitivul',
      'desc' => 'Conectați hardware-ul fizic.',
    ],
    'multipos_check' => [
      'title' => 'Verificați dispozitivul',
      'desc' => 'Verificați înregistrarea.',
    ],
    'notif_token' => [
      'title' => 'Înregistrați FCM',
      'desc' => 'Salvați jetonul push.',
    ],
    'notif_list' => [
      'title' => 'Notificări',
      'desc' => 'Primiți alerte în aplicație.',
    ],
    'notif_read' => [
      'title' => 'Mark Citit',
      'desc' => 'Respinge notificarea.',
    ],
    'pusher_settings' => [
      'title' => 'Obțineți setări Pusher',
      'desc' => 'Preluați configurația Pusher completă. Accesibil tuturor utilizatorilor autentificați (superadmin, admin, personal).',
    ],
    'pusher_broadcast' => [
      'title' => 'Obțineți setări de difuzare',
      'desc' => 'Obțineți configurația de difuzare în timp real Pusher. Setări la nivel de sistem pentru toți utilizatorii.',
    ],
    'pusher_beams' => [
      'title' => 'Obțineți setări Beams',
      'desc' => 'Obțineți configurația notificărilor push Pusher Beams. Accesibil tuturor utilizatorilor autentificați.',
    ],
    'pusher_status' => [
      'title' => 'Verificați starea împingătorului',
      'desc' => 'Verificare rapidă a stării pentru a verifica dacă serviciile Pusher sunt activate. Disponibil pentru toți utilizatorii.',
    ],
    'pusher_authorize' => [
      'title' => 'Autorizați canalul',
      'desc' => 'Autorizați accesul utilizatorilor la canalele private și de prezență. Necesită autentificare validă.',
    ],
    'pusher_presence' => [
      'title' => 'Obțineți membri prezență',
      'desc' => 'Preluați lista utilizatorilor conectați în prezent la un canal de prezență. Date la nivelul întregului sistem.',
    ],
  ],
];