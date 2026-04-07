<?php 
return [
  'title' => 'API-referentie',
  'subtitle' => 'Documentatie voor ontwikkelaars',
  'intro' => 'Welkom bij de volledige API-referentie. Dit systeem maakt een diepgaande integratie met de POS-backend mogelijk, waardoor u ober-apps, klantenkiosken of aangepaste dashboards kunt bouwen.',
  'search_placeholder' => 'Eindpunten zoeken...',
  'base_url' => 'Basis-URL',
  'auth_header' => 'Authenticatie',
  'auth_desc' => 'Verifieer via Bearer-token. Neem `Authorisatie: Bearer <token>` op in de headers.',
  'sections' => [
    'auth' => 'Authenticatie',
    'platform' => 'Platform',
    'resources' => 'Bronnen',
    'customers' => 'Klanten',
    'catalog' => 'Catalogus',
    'sales' => 'Verkoop & Bestellingen',
    'kot' => 'Keukenbestellingen (KOT)',
    'delivery' => 'Leveringsbeheer',
    'operations' => 'Operaties',
    'hardware' => 'Hardware en apparaten',
    'pusher' => 'Realtime en push',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Login',
      'desc' => 'Toegangstoken verkrijgen.',
    ],
    'me' => [
      'title' => 'Gebruikersprofiel',
      'desc' => 'Krijg huidige gebruiker en machtigingen.',
    ],
    'config' => [
      'title' => 'Configuratie en functies',
      'desc' => 'Systeeminstellingen, functievlaggen en actieve modules ophalen.',
    ],
    'permissions' => [
      'title' => 'Machtigingen',
      'desc' => 'Maak een lijst van gebruikersrollen en -mogelijkheden.',
    ],
    'printers' => [
      'title' => 'Printers',
      'desc' => 'Ontvang geconfigureerde bonnen/KOT-printers.',
    ],
    'receipts' => [
      'title' => 'Ontvangstinstellingen',
      'desc' => 'Configuratie voor ontvangststyling verkrijgen.',
    ],
    'switch_branch' => [
      'title' => 'Wissel van filiaal',
      'desc' => 'Wijzig de actieve vertakkingscontext.',
    ],
    'langs' => [
      'title' => 'Talen',
      'desc' => 'Beschikbare talen verkrijgen.',
    ],
    'currencies' => [
      'title' => 'Valuta\'s',
      'desc' => 'Systeemvaluta\'s verkrijgen.',
    ],
    'gateways' => [
      'title' => 'Betalingsgateways',
      'desc' => 'Ontvang referenties voor de openbare gateway.',
    ],
    'staff' => [
      'title' => 'Personeelslijst',
      'desc' => 'Verzamel alle personeelsleden.',
    ],
    'roles' => [
      'title' => 'Rollen',
      'desc' => 'Beschikbare gebruikersrollen ophalen.',
    ],
    'areas' => [
      'title' => 'Gebieden',
      'desc' => 'Verkrijg plattegrondgebieden.',
    ],
    'addr_list' => [
      'title' => 'Lijst adressen',
      'desc' => 'Adressen voor een klant ophalen.',
    ],
    'addr_create' => [
      'title' => 'Adres aanmaken',
      'desc' => 'Voeg een nieuw afleveradres toe.',
    ],
    'addr_update' => [
      'title' => 'Adres bijwerken',
      'desc' => 'Wijzig een bestaand adres.',
    ],
    'addr_delete' => [
      'title' => 'Adres verwijderen',
      'desc' => 'Verwijder een adres.',
    ],
    'menus' => [
      'title' => 'Menu\'s',
      'desc' => 'Krijg actieve menu\'s.',
    ],
    'categories' => [
      'title' => 'Categorieën',
      'desc' => 'Artikelcategorieën ophalen.',
    ],
    'items' => [
      'title' => 'Alle artikelen',
      'desc' => 'Ontvang een volledige artikelcatalogus met prijzen en aanpassingen.',
    ],
    'items_filter' => [
      'title' => 'Artikelen filteren',
      'desc' => 'Ontvang items per categorie of menu.',
    ],
    'variations' => [
      'title' => 'Artikelvariaties',
      'desc' => 'Varianten voor een specifiek artikel ophalen.',
    ],
    'modifiers' => [
      'title' => 'Artikelmodificatoren',
      'desc' => 'Modificatiegroepen ophalen voor een specifiek item.',
    ],
    'orders_create' => [
      'title' => 'Bestelling indienen',
      'desc' => 'Maak een nieuwe bestelling aan (Dine-in/Bezorging).',
    ],
    'orders_list' => [
      'title' => 'Lijstbestellingen',
      'desc' => 'Ontvang bestelgeschiedenis.',
    ],
    'orders_detail' => [
      'title' => 'Besteldetail',
      'desc' => 'Krijg volledige bestelling object.',
    ],
    'orders_status' => [
      'title' => 'Status bijwerken',
      'desc' => 'Orderstatus wijzigen (bijvoorbeeld gereed).',
    ],
    'orders_pay' => [
      'title' => 'Betaalopdracht',
      'desc' => 'Registreer de betaling en sluit de bestelling.',
    ],
    'order_number' => [
      'title' => 'Voorbeeldnummer',
      'desc' => 'Ontvang het volgende bestelnummer.',
    ],
    'order_types' => [
      'title' => 'Besteltypen',
      'desc' => 'Typen ophalen (Dine-in, Takeaway).',
    ],
    'actions' => [
      'title' => 'Toegestane acties',
      'desc' => 'Krijg geldige bestelacties (kot, factuur).',
    ],
    'platforms' => [
      'title' => 'Leveringsplatforms',
      'desc' => 'Koop platforms van derden.',
    ],
    'charges' => [
      'title' => 'Extra kosten',
      'desc' => 'Ontvang servicekosten/kosten.',
    ],
    'taxes' => [
      'title' => 'Belastingen',
      'desc' => 'Ontvang geconfigureerde belastingtarieven.',
    ],
    'tables' => [
      'title' => 'Tafels',
      'desc' => 'Ontvang realtime tafelstatus.',
    ],
    'unlock' => [
      'title' => 'Ontgrendel tafel',
      'desc' => 'Forceer het ontgrendelen van een tafel.',
    ],
    'res_today' => [
      'title' => 'Reserveringen van vandaag',
      'desc' => 'Ontvang reserveringen voor het dashboard.',
    ],
    'res_list' => [
      'title' => 'Alle reserveringen',
      'desc' => 'Ontvang gepagineerde reserveringen.',
    ],
    'res_create' => [
      'title' => 'Reservering maken',
      'desc' => 'Reserveer een tafel.',
    ],
    'res_status' => [
      'title' => 'Reservering bijwerken',
      'desc' => 'Wijzig de reserveringsstatus.',
    ],
    'cust_search' => [
      'title' => 'Zoek klanten',
      'desc' => 'Zoeken op naam/telefoon.',
    ],
    'cust_save' => [
      'title' => 'Klant opslaan',
      'desc' => 'Profiel maken of bijwerken.',
    ],
    'waiters' => [
      'title' => 'Obers',
      'desc' => 'Zorg voor personeel met ober-/chauffeursrollen.',
    ],
    'kot_list' => [
      'title' => 'Lijst KOT\'s',
      'desc' => 'Koop kaartjes voor keukenbestellingen om tentoon te stellen.',
    ],
    'kot_detail' => [
      'title' => 'KOT-detail',
      'desc' => 'Ontvang één KOT met items.',
    ],
    'kot_create' => [
      'title' => 'KOT maken',
      'desc' => 'Maak een nieuwe KOT aan voor een bestaande bestelling.',
    ],
    'kot_status' => [
      'title' => 'Update KOT-status',
      'desc' => 'KOT-status wijzigen (in_keuken, food_ready, geserveerd, geannuleerd).',
    ],
    'kot_item_status' => [
      'title' => 'Artikelstatus bijwerken',
      'desc' => 'Update de individuele itemstatus (bereiden, klaar, geannuleerd).',
    ],
    'kot_places' => [
      'title' => 'Keuken plaatsen',
      'desc' => 'Verkrijg keukenstations/plaatsen.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'Redenen voor annuleren',
      'desc' => 'Ontvang KOT-annuleringsredenen.',
    ],
    'order_kots' => [
      'title' => 'KOT\'s bestellen',
      'desc' => 'Ontvang alle KOT\'s voor een specifieke bestelling.',
    ],
    'delivery_settings' => [
      'title' => 'Leveringsinstellingen',
      'desc' => 'Ontvang configuratie voor filiaalbezorging (radius, kosten, planning).',
    ],
    'delivery_fee_calc' => [
      'title' => 'Bereken de vergoeding',
      'desc' => 'Bereken de bezorgkosten op basis van de locatie van de klant.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Tariefniveaus',
      'desc' => 'Ontvang op afstand gebaseerde tariefniveaus.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Lijstplatforms',
      'desc' => 'Zorg voor actieve bezorgplatforms (Uber Eats, enz.).',
    ],
    'delivery_platform_get' => [
      'title' => 'Platformdetail',
      'desc' => 'Ontvang één bezorgplatform met commissie-informatie.',
    ],
    'delivery_platform_create' => [
      'title' => 'Creëer platform',
      'desc' => 'Voeg een nieuw bezorgplatform toe.',
    ],
    'delivery_platform_update' => [
      'title' => 'Platform bijwerken',
      'desc' => 'Wijzig platforminstellingen/commissie.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Platform verwijderen',
      'desc' => 'Verwijder of deactiveer het bezorgplatform.',
    ],
    'delivery_exec_list' => [
      'title' => 'Lijst leidinggevenden',
      'desc' => 'Ontvang bezorgpersoneel met statusfilter.',
    ],
    'delivery_exec_create' => [
      'title' => 'Uitvoerend creëren',
      'desc' => 'Nieuwe bezorger toevoegen.',
    ],
    'delivery_exec_update' => [
      'title' => 'Update uitvoerend',
      'desc' => 'Wijzig de gegevens van de bezorger.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Executive verwijderen',
      'desc' => 'Bezorgmanager verwijderen of deactiveren.',
    ],
    'delivery_exec_status' => [
      'title' => 'Uitvoerende status',
      'desc' => 'Beschikbaarheid bijwerken (beschikbaar/on_delivery/inactief).',
    ],
    'delivery_assign' => [
      'title' => 'Levering toewijzen',
      'desc' => 'Wijs leidinggevende/platform toe aan bestelling.',
    ],
    'delivery_order_status' => [
      'title' => 'Leveringsstatus',
      'desc' => 'Update de leveringsstatus van de bestelling (voorbereiden, uit_voor_levering, afgeleverd).',
    ],
    'delivery_orders' => [
      'title' => 'Leveringsbestellingen',
      'desc' => 'Ontvang een gefilterde lijst met bezorgorders.',
    ],
    'multipos_reg' => [
      'title' => 'Apparaat registreren',
      'desc' => 'Koppel fysieke hardware.',
    ],
    'multipos_check' => [
      'title' => 'Controleer apparaat',
      'desc' => 'Controleer de registratie.',
    ],
    'notif_token' => [
      'title' => 'FCM registreren',
      'desc' => 'Push-token opslaan.',
    ],
    'notif_list' => [
      'title' => 'Meldingen',
      'desc' => 'Ontvang meldingen in de app.',
    ],
    'notif_read' => [
      'title' => 'Markeer gelezen',
      'desc' => 'Melding afwijzen.',
    ],
    'pusher_settings' => [
      'title' => 'Pusher-instellingen ophalen',
      'desc' => 'Haal de volledige Pusher-configuratie op. Toegankelijk voor alle geauthenticeerde gebruikers (superadmin, admin, personeel).',
    ],
    'pusher_broadcast' => [
      'title' => 'Ontvang uitzendinstellingen',
      'desc' => 'Ontvang Pusher realtime uitzendingsconfiguratie. Systeembrede instellingen voor alle gebruikers.',
    ],
    'pusher_beams' => [
      'title' => 'Beams-instellingen ophalen',
      'desc' => 'Ontvang Pusher Beams configuratie voor pushmeldingen. Toegankelijk voor alle geverifieerde gebruikers.',
    ],
    'pusher_status' => [
      'title' => 'Controleer de pusherstatus',
      'desc' => 'Snelle statuscontrole om te verifiëren of Pusher-services zijn ingeschakeld. Beschikbaar voor alle gebruikers.',
    ],
    'pusher_authorize' => [
      'title' => 'Kanaal autoriseren',
      'desc' => 'Autoriseer gebruikerstoegang tot privé- en aanwezigheidskanalen. Vereist geldige authenticatie.',
    ],
    'pusher_presence' => [
      'title' => 'Ontvang aanwezigheidsleden',
      'desc' => 'Haal een lijst op met gebruikers die momenteel zijn verbonden met een aanwezigheidskanaal. Systeembrede gegevens.',
    ],
  ],
];