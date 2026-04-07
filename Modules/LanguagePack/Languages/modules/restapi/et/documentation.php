<?php 
return [
  'title' => 'API viide',
  'subtitle' => 'Arendaja dokumentatsioon',
  'intro' => 'Tere tulemast täielikku API viidet kasutama. See süsteem võimaldab sügavat integreerimist POS-i taustaprogrammiga, võimaldades teil luua kelnerirakendusi, kliendikioskeid või kohandatud armatuurlaudu.',
  'search_placeholder' => 'Otsi lõpp-punkte...',
  'base_url' => 'Baas-URL',
  'auth_header' => 'Autentimine',
  'auth_desc' => 'Autentimine kandja märgi kaudu. Kaasake päistesse "Authorization: Bearer <token>".',
  'sections' => [
    'auth' => 'Autentimine',
    'platform' => 'Platvorm',
    'resources' => 'Vahendid',
    'customers' => 'Kliendid',
    'catalog' => 'Kataloog',
    'sales' => 'Müük ja tellimused',
    'kot' => 'Köögitellimused (KOT)',
    'delivery' => 'Kohaletoimetamise juhtimine',
    'operations' => 'Operatsioonid',
    'hardware' => 'Riistvara ja seadmed',
    'pusher' => 'Reaalajas & Push',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Logi sisse',
      'desc' => 'Hankige juurdepääsuluba.',
    ],
    'me' => [
      'title' => 'Kasutajaprofiil',
      'desc' => 'Hankige praegune kasutaja ja load.',
    ],
    'config' => [
      'title' => 'Konfiguratsioon ja funktsioonid',
      'desc' => 'Hankige süsteemi sätted, funktsioonide lipud ja aktiivsed moodulid.',
    ],
    'permissions' => [
      'title' => 'load',
      'desc' => 'Loetlege kasutaja rollid ja võimalused.',
    ],
    'printers' => [
      'title' => 'Printerid',
      'desc' => 'Hankige seadistatud kviitungid/KOT-printerid.',
    ],
    'receipts' => [
      'title' => 'Kviitungi seaded',
      'desc' => 'Hankige kviitungi stiili konfiguratsioon.',
    ],
    'switch_branch' => [
      'title' => 'Vaheta haru',
      'desc' => 'Muutke aktiivse haru konteksti.',
    ],
    'langs' => [
      'title' => 'Keeled',
      'desc' => 'Hankige saadaolevad keeled.',
    ],
    'currencies' => [
      'title' => 'Valuutad',
      'desc' => 'Hankige süsteemi valuutad.',
    ],
    'gateways' => [
      'title' => 'Makseväravad',
      'desc' => 'Hankige avaliku lüüsi mandaadid.',
    ],
    'staff' => [
      'title' => 'Töötajate nimekiri',
      'desc' => 'Hankige kõik töötajad.',
    ],
    'roles' => [
      'title' => 'Rollid',
      'desc' => 'Hankige saadaolevad kasutajarollid.',
    ],
    'areas' => [
      'title' => 'Piirkonnad',
      'desc' => 'Hankige põrandaplaani alad.',
    ],
    'addr_list' => [
      'title' => 'Aadresside loend',
      'desc' => 'Hankige kliendi aadressid.',
    ],
    'addr_create' => [
      'title' => 'Loo aadress',
      'desc' => 'Lisage uus tarneaadress.',
    ],
    'addr_update' => [
      'title' => 'Värskenda aadressi',
      'desc' => 'Muutke olemasolevat aadressi.',
    ],
    'addr_delete' => [
      'title' => 'Kustuta aadress',
      'desc' => 'Eemalda aadress.',
    ],
    'menus' => [
      'title' => 'Menüüd',
      'desc' => 'Hankige aktiivsed menüüd.',
    ],
    'categories' => [
      'title' => 'Kategooriad',
      'desc' => 'Hankige kaubakategooriad.',
    ],
    'items' => [
      'title' => 'Kõik esemed',
      'desc' => 'Hankige täielik kaubakataloog koos hindade ja modifikaatoritega.',
    ],
    'items_filter' => [
      'title' => 'Filtreeri üksused',
      'desc' => 'Hankige üksusi kategooria või menüü järgi.',
    ],
    'variations' => [
      'title' => 'Üksuste variatsioonid',
      'desc' => 'Hankige konkreetse üksuse jaoks variatsioone.',
    ],
    'modifiers' => [
      'title' => 'Üksuse muutjad',
      'desc' => 'Hankige konkreetse üksuse modifikaatorirühmad.',
    ],
    'orders_create' => [
      'title' => 'Esitage tellimus',
      'desc' => 'Looge uus tellimus (söögisöök / kohaletoimetamine).',
    ],
    'orders_list' => [
      'title' => 'Tellimuste loend',
      'desc' => 'Hankige tellimuste ajalugu.',
    ],
    'orders_detail' => [
      'title' => 'Tellimuse üksikasjad',
      'desc' => 'Hankige täielik tellimisobjekt.',
    ],
    'orders_status' => [
      'title' => 'Värskenda olekut',
      'desc' => 'Muutke tellimuse olekut (nt valmis).',
    ],
    'orders_pay' => [
      'title' => 'Maksekorraldus',
      'desc' => 'Registreeri makse ja sulge tellimus.',
    ],
    'order_number' => [
      'title' => 'Eelvaate number',
      'desc' => 'Hankige järgmine tellimuse number.',
    ],
    'order_types' => [
      'title' => 'Tellimuste tüübid',
      'desc' => 'Hankige tüüpe (einestada, kaasa võtta).',
    ],
    'actions' => [
      'title' => 'Lubatud toimingud',
      'desc' => 'Hankige kehtivad tellimuse toimingud (kot, arve).',
    ],
    'platforms' => [
      'title' => 'Kohaletoimetamise platvormid',
      'desc' => 'Hankige kolmanda osapoole platvormid.',
    ],
    'charges' => [
      'title' => 'Lisatasud',
      'desc' => 'Hankige teenustasusid/tasusid.',
    ],
    'taxes' => [
      'title' => 'Maksud',
      'desc' => 'Hankige konfigureeritud maksumäärad.',
    ],
    'tables' => [
      'title' => 'Tabelid',
      'desc' => 'Vaadake reaalajas tabeli olekut.',
    ],
    'unlock' => [
      'title' => 'Avage tabel',
      'desc' => 'Tabeli sundavamine.',
    ],
    'res_today' => [
      'title' => 'Tänased broneeringud',
      'desc' => 'Saate armatuurlaua jaoks broneeringuid teha.',
    ],
    'res_list' => [
      'title' => 'Kõik broneeringud',
      'desc' => 'Hankige leheküljenumbritega broneeringuid.',
    ],
    'res_create' => [
      'title' => 'Loo broneering',
      'desc' => 'Broneerige laud.',
    ],
    'res_status' => [
      'title' => 'Värskenda broneeringut',
      'desc' => 'Muutke broneeringu olekut.',
    ],
    'cust_search' => [
      'title' => 'Otsige kliente',
      'desc' => 'Otsi nime/telefoni järgi.',
    ],
    'cust_save' => [
      'title' => 'Salvesta klient',
      'desc' => 'Looge või värskendage profiili.',
    ],
    'waiters' => [
      'title' => 'Kelnerid',
      'desc' => 'Hankige kelneri/juhi rollidega töötajaid.',
    ],
    'kot_list' => [
      'title' => 'Loetlege KOT-id',
      'desc' => 'Hankige väljapanekuks köögitellimuse piletid.',
    ],
    'kot_detail' => [
      'title' => 'KOT Detail',
      'desc' => 'Hankige üks KOT koos esemetega.',
    ],
    'kot_create' => [
      'title' => 'Loo KOT',
      'desc' => 'Looge olemasoleva tellimuse jaoks uus KOT.',
    ],
    'kot_status' => [
      'title' => 'Värskenda KOTi olekut',
      'desc' => 'Muuda KOT-i olekut (köögis_köök, toit_valmis, serveeritud, tühistatud).',
    ],
    'kot_item_status' => [
      'title' => 'Värskenda üksuse olekut',
      'desc' => 'Üksiku üksuse oleku värskendamine (küpsetamine, valmis, tühistatud).',
    ],
    'kot_places' => [
      'title' => 'Köögikohad',
      'desc' => 'Hankige köögipunktid/kohad.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'Tühistamise põhjused',
      'desc' => 'Hankige KOT-i tühistamise põhjused.',
    ],
    'order_kots' => [
      'title' => 'Telli KOT-id',
      'desc' => 'Hankige kõik KOT-id konkreetse tellimuse jaoks.',
    ],
    'delivery_settings' => [
      'title' => 'Kohaletoimetamise seaded',
      'desc' => 'Hankige filiaali kohaletoimetamise konfiguratsioon (raadius, tasud, ajakava).',
    ],
    'delivery_fee_calc' => [
      'title' => 'Arvutage tasu',
      'desc' => 'Arvutage kohaletoimetamise tasu kliendi asukoha alusel.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Tasu tasemed',
      'desc' => 'Hankige vahemaapõhised tasumäärad.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Platvormide loend',
      'desc' => 'Hankige aktiivseid tarneplatvorme (Uber Eats jne).',
    ],
    'delivery_platform_get' => [
      'title' => 'Platvormi üksikasjad',
      'desc' => 'Hankige ühtne tarneplatvorm koos vahendustasu teabega.',
    ],
    'delivery_platform_create' => [
      'title' => 'Loo platvorm',
      'desc' => 'Lisa uus tarneplatvorm.',
    ],
    'delivery_platform_update' => [
      'title' => 'Värskenda platvormi',
      'desc' => 'Muutke platvormi sätteid/komisjoni.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Kustuta platvorm',
      'desc' => 'Eemaldage või desaktiveerige tarneplatvorm.',
    ],
    'delivery_exec_list' => [
      'title' => 'Nimekiri juhid',
      'desc' => 'Hankige kohaletoimetamise töötajad olekufiltriga.',
    ],
    'delivery_exec_create' => [
      'title' => 'Looge juht',
      'desc' => 'Lisa uus tarnejuht.',
    ],
    'delivery_exec_update' => [
      'title' => 'Värskendage Executive',
      'desc' => 'Muutke kohaletoimetamise juhiteavet.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Kustuta Executive',
      'desc' => 'Eemaldage või desaktiveerige kohaletoimetamise juht.',
    ],
    'delivery_exec_status' => [
      'title' => 'Täitev staatus',
      'desc' => 'Värskenda saadavust (available/on_delivery/inactive).',
    ],
    'delivery_assign' => [
      'title' => 'Määra kohaletoimetamine',
      'desc' => 'Määrake juht/platvorm tellimusele.',
    ],
    'delivery_order_status' => [
      'title' => 'Kohaletoimetamise olek',
      'desc' => 'Värskendage tellimuse kohaletoimetamise olekut (valmistamisel, tarnimiseks väljas, tarnitud).',
    ],
    'delivery_orders' => [
      'title' => 'Kohaletoimetamise tellimused',
      'desc' => 'Hankige tarnetellimuste filtreeritud loend.',
    ],
    'multipos_reg' => [
      'title' => 'Registreerige seade',
      'desc' => 'Ühendage füüsiline riistvara.',
    ],
    'multipos_check' => [
      'title' => 'Kontrollige seadet',
      'desc' => 'Kinnitage registreerimine.',
    ],
    'notif_token' => [
      'title' => 'Registreerige FCM',
      'desc' => 'Salvesta tõukemärk.',
    ],
    'notif_list' => [
      'title' => 'Märguanded',
      'desc' => 'Hankige rakendusesiseseid märguandeid.',
    ],
    'notif_read' => [
      'title' => 'Märgi loetuks',
      'desc' => 'Loobu teatisest.',
    ],
    'pusher_settings' => [
      'title' => 'Hankige Pusheri seaded',
      'desc' => 'Tooge tõukuri täielik konfiguratsioon. Juurdepääs kõigile autentitud kasutajatele (superadmin, administraator, personal).',
    ],
    'pusher_broadcast' => [
      'title' => 'Hankige edastusseaded',
      'desc' => 'Hankige Pusheri reaalajas leviedastuse konfiguratsioon. Süsteemiülesed sätted kõigile kasutajatele.',
    ],
    'pusher_beams' => [
      'title' => 'Hankige talade seaded',
      'desc' => 'Hankige Pusher Beamsi tõukemärguannete konfiguratsioon. Juurdepääs kõigile autentitud kasutajatele.',
    ],
    'pusher_status' => [
      'title' => 'Kontrollige tõukuri olekut',
      'desc' => 'Kiire olekukontroll, et kontrollida, kas Pusheri teenused on lubatud. Saadaval kõigile kasutajatele.',
    ],
    'pusher_authorize' => [
      'title' => 'Autoriseeri kanal',
      'desc' => 'Lubage kasutaja juurdepääs privaat- ja kohalolekukanalitele. Nõuab kehtivat autentimist.',
    ],
    'pusher_presence' => [
      'title' => 'Hankige kohaloleku liikmed',
      'desc' => 'Tooge praegu kohalolekukanaliga ühendatud kasutajate loend. Süsteemiülesed andmed.',
    ],
  ],
];