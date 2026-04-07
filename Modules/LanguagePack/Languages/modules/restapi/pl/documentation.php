<?php 
return [
  'title' => 'Dokumentacja API',
  'subtitle' => 'Dokumentacja programisty',
  'intro' => 'Witamy w pełnej dokumentacji API. System ten umożliwia głęboką integrację z backendem POS, umożliwiając tworzenie aplikacji kelnerskich, kiosków dla klientów lub niestandardowych dashboardów.',
  'search_placeholder' => 'Wyszukaj punkty końcowe...',
  'base_url' => 'Bazowy adres URL',
  'auth_header' => 'Uwierzytelnianie',
  'auth_desc' => 'Uwierzytelnij się za pomocą tokena okaziciela. Uwzględnij w nagłówkach „Autoryzacja: okaziciel <token>”.',
  'sections' => [
    'auth' => 'Uwierzytelnianie',
    'platform' => 'Platforma',
    'resources' => 'Zasoby',
    'customers' => 'Klienci',
    'catalog' => 'Katalog',
    'sales' => 'Sprzedaż i zamówienia',
    'kot' => 'Zamówienia kuchenne (KOT)',
    'delivery' => 'Zarządzanie dostawami',
    'operations' => 'Operacje',
    'hardware' => 'Sprzęt i urządzenia',
    'pusher' => 'W czasie rzeczywistym i Push',
  ],
  'endpoints' => [
    'login' => [
      'title' => 'Login',
      'desc' => 'Uzyskaj token dostępu.',
    ],
    'me' => [
      'title' => 'Profil użytkownika',
      'desc' => 'Uzyskaj bieżącego użytkownika i uprawnienia.',
    ],
    'config' => [
      'title' => 'Konfiguracja i funkcje',
      'desc' => 'Uzyskaj ustawienia systemu, flagi funkcji i aktywne moduły.',
    ],
    'permissions' => [
      'title' => 'Uprawnienia',
      'desc' => 'Lista ról i możliwości użytkowników.',
    ],
    'printers' => [
      'title' => 'Drukarki',
      'desc' => 'Uzyskaj skonfigurowane drukarki paragonów/KOT.',
    ],
    'receipts' => [
      'title' => 'Ustawienia odbioru',
      'desc' => 'Uzyskaj konfigurację stylizacji paragonów.',
    ],
    'switch_branch' => [
      'title' => 'Oddział przełączający',
      'desc' => 'Zmień kontekst aktywnego oddziału.',
    ],
    'langs' => [
      'title' => 'Języki',
      'desc' => 'Uzyskaj dostępne języki.',
    ],
    'currencies' => [
      'title' => 'Waluty',
      'desc' => 'Pobierz waluty systemowe.',
    ],
    'gateways' => [
      'title' => 'Bramki płatnicze',
      'desc' => 'Uzyskaj poświadczenia bramy publicznej.',
    ],
    'staff' => [
      'title' => 'Lista personelu',
      'desc' => 'Zdobądź wszystkich pracowników.',
    ],
    'roles' => [
      'title' => 'Role',
      'desc' => 'Uzyskaj dostępne role użytkowników.',
    ],
    'areas' => [
      'title' => 'Obszary',
      'desc' => 'Uzyskaj obszary planu piętra.',
    ],
    'addr_list' => [
      'title' => 'Lista adresów',
      'desc' => 'Uzyskaj adresy dla klienta.',
    ],
    'addr_create' => [
      'title' => 'Utwórz adres',
      'desc' => 'Dodaj nowy adres dostawy.',
    ],
    'addr_update' => [
      'title' => 'Aktualizuj adres',
      'desc' => 'Zmodyfikuj istniejący adres.',
    ],
    'addr_delete' => [
      'title' => 'Usuń adres',
      'desc' => 'Usuń adres.',
    ],
    'menus' => [
      'title' => 'Menu',
      'desc' => 'Uzyskaj aktywne menu.',
    ],
    'categories' => [
      'title' => 'Kategorie',
      'desc' => 'Pobierz kategorie przedmiotów.',
    ],
    'items' => [
      'title' => 'Wszystkie przedmioty',
      'desc' => 'Uzyskaj pełny katalog przedmiotów z cenami i modyfikatorami.',
    ],
    'items_filter' => [
      'title' => 'Filtruj elementy',
      'desc' => 'Pobierz elementy według kategorii lub menu.',
    ],
    'variations' => [
      'title' => 'Odmiany pozycji',
      'desc' => 'Uzyskaj odmiany dla konkretnego przedmiotu.',
    ],
    'modifiers' => [
      'title' => 'Modyfikatory przedmiotów',
      'desc' => 'Pobierz grupy modyfikatorów dla określonego przedmiotu.',
    ],
    'orders_create' => [
      'title' => 'Prześlij zamówienie',
      'desc' => 'Utwórz nowe zamówienie (Dane na miejscu/Dostawa).',
    ],
    'orders_list' => [
      'title' => 'Lista zamówień',
      'desc' => 'Uzyskaj historię zamówień.',
    ],
    'orders_detail' => [
      'title' => 'Szczegóły zamówienia',
      'desc' => 'Uzyskaj pełny obiekt zamówienia.',
    ],
    'orders_status' => [
      'title' => 'Aktualizuj stan',
      'desc' => 'Zmień status zamówienia (np. przygotowane).',
    ],
    'orders_pay' => [
      'title' => 'Zapłać zamówienie',
      'desc' => 'Zapisz płatność i zamknij zamówienie.',
    ],
    'order_number' => [
      'title' => 'Numer podglądu',
      'desc' => 'Uzyskaj następny numer zamówienia.',
    ],
    'order_types' => [
      'title' => 'Typy zamówień',
      'desc' => 'Uzyskaj typy (posiłek na miejscu, na wynos).',
    ],
    'actions' => [
      'title' => 'Dozwolone działania',
      'desc' => 'Uzyskaj prawidłowe działania związane z zamówieniem (kot, rachunek).',
    ],
    'platforms' => [
      'title' => 'Platformy dostaw',
      'desc' => 'Zdobądź platformy innych firm.',
    ],
    'charges' => [
      'title' => 'Dodatkowe opłaty',
      'desc' => 'Uzyskaj opłaty za usługi/opłaty.',
    ],
    'taxes' => [
      'title' => 'Podatki',
      'desc' => 'Uzyskaj skonfigurowane stawki podatkowe.',
    ],
    'tables' => [
      'title' => 'Stoły',
      'desc' => 'Uzyskaj status tabeli w czasie rzeczywistym.',
    ],
    'unlock' => [
      'title' => 'Odblokuj stół',
      'desc' => 'Wymuś odblokowanie stołu.',
    ],
    'res_today' => [
      'title' => 'Dzisiejsze rezerwacje',
      'desc' => 'Zdobądź rezerwacje na dashboard.',
    ],
    'res_list' => [
      'title' => 'Wszystkie rezerwacje',
      'desc' => 'Uzyskaj rezerwacje podzielone na strony.',
    ],
    'res_create' => [
      'title' => 'Utwórz rezerwację',
      'desc' => 'Zarezerwuj stolik.',
    ],
    'res_status' => [
      'title' => 'Aktualizuj rezerwację',
      'desc' => 'Zmień status rezerwacji.',
    ],
    'cust_search' => [
      'title' => 'Szukaj klientów',
      'desc' => 'Znajdź według nazwiska/telefonu.',
    ],
    'cust_save' => [
      'title' => 'Zapisz Klienta',
      'desc' => 'Utwórz lub zaktualizuj profil.',
    ],
    'waiters' => [
      'title' => 'Kelnerzy',
      'desc' => 'Zatrudnij personel na stanowiskach kelnera/kierowcy.',
    ],
    'kot_list' => [
      'title' => 'Lista KOTów',
      'desc' => 'Zdobądź bilety do zamówienia w kuchni na wystawę.',
    ],
    'kot_detail' => [
      'title' => 'KOT Szczegóły',
      'desc' => 'Zdobądź pojedynczy KOT z przedmiotami.',
    ],
    'kot_create' => [
      'title' => 'Utwórz KOTA',
      'desc' => 'Utwórz nowy KOT dla istniejącego zamówienia.',
    ],
    'kot_status' => [
      'title' => 'Zaktualizuj status KOT',
      'desc' => 'Zmień status KOT (w_kuchni, jedzenie_gotowe, podane, anulowane).',
    ],
    'kot_item_status' => [
      'title' => 'Zaktualizuj status przedmiotu',
      'desc' => 'Aktualizuj status poszczególnych produktów (gotowanie, gotowe, anulowane).',
    ],
    'kot_places' => [
      'title' => 'Miejsca kuchenne',
      'desc' => 'Zdobądź stanowiska/miejsca kuchenne.',
    ],
    'kot_cancel_reasons' => [
      'title' => 'Anuluj powody',
      'desc' => 'Uzyskaj powody anulowania KOT.',
    ],
    'order_kots' => [
      'title' => 'Zamów KOT',
      'desc' => 'Zdobądź wszystkie KOTy dla konkretnego zamówienia.',
    ],
    'delivery_settings' => [
      'title' => 'Ustawienia dostawy',
      'desc' => 'Uzyskaj konfigurację dostawy do oddziału (promień, opłaty, harmonogram).',
    ],
    'delivery_fee_calc' => [
      'title' => 'Oblicz opłatę',
      'desc' => 'Oblicz opłatę za dostawę na podstawie lokalizacji klienta.',
    ],
    'delivery_fee_tiers' => [
      'title' => 'Poziomy opłat',
      'desc' => 'Uzyskaj poziomy opłat zależne od odległości.',
    ],
    'delivery_platforms_list' => [
      'title' => 'Lista platform',
      'desc' => 'Uzyskaj aktywne platformy dostaw (Uber Eats itp.).',
    ],
    'delivery_platform_get' => [
      'title' => 'Szczegóły platformy',
      'desc' => 'Uzyskaj platformę z pojedynczą dostawą i informacjami o prowizjach.',
    ],
    'delivery_platform_create' => [
      'title' => 'Utwórz platformę',
      'desc' => 'Dodaj nową platformę dostaw.',
    ],
    'delivery_platform_update' => [
      'title' => 'Zaktualizuj platformę',
      'desc' => 'Zmodyfikuj ustawienia/prowizję platformy.',
    ],
    'delivery_platform_delete' => [
      'title' => 'Usuń platformę',
      'desc' => 'Usuń lub dezaktywuj platformę dostaw.',
    ],
    'delivery_exec_list' => [
      'title' => 'Lista dyrektorów',
      'desc' => 'Uzyskaj dostawcę z filtrem statusu.',
    ],
    'delivery_exec_create' => [
      'title' => 'Utwórz dyrektora',
      'desc' => 'Dodaj nowego dyrektora ds. dostaw.',
    ],
    'delivery_exec_update' => [
      'title' => 'Zaktualizuj dyrektora',
      'desc' => 'Zmodyfikuj informacje o kierowniku dostawy.',
    ],
    'delivery_exec_delete' => [
      'title' => 'Usuń dyrektora',
      'desc' => 'Usuń lub dezaktywuj dyrektora ds. dostaw.',
    ],
    'delivery_exec_status' => [
      'title' => 'Status wykonawczy',
      'desc' => 'Aktualizuj dostępność (dostępne/w momencie dostawy/nieaktywne).',
    ],
    'delivery_assign' => [
      'title' => 'Przypisz dostawę',
      'desc' => 'Przypisz kierownika/platformę do zamówienia.',
    ],
    'delivery_order_status' => [
      'title' => 'Stan dostawy',
      'desc' => 'Zaktualizuj status dostawy zamówienia (przygotowanie, out_for_delivery, dostarczone).',
    ],
    'delivery_orders' => [
      'title' => 'Zamówienia dostawy',
      'desc' => 'Uzyskaj filtrowaną listę zamówień z dostawą.',
    ],
    'multipos_reg' => [
      'title' => 'Zarejestruj urządzenie',
      'desc' => 'Połącz sprzęt fizyczny.',
    ],
    'multipos_check' => [
      'title' => 'Sprawdź urządzenie',
      'desc' => 'Sprawdź rejestrację.',
    ],
    'notif_token' => [
      'title' => 'Zarejestruj FCM',
      'desc' => 'Zapisz token push.',
    ],
    'notif_list' => [
      'title' => 'Powiadomienia',
      'desc' => 'Otrzymuj powiadomienia w aplikacji.',
    ],
    'notif_read' => [
      'title' => 'Zaznacz Przeczytaj',
      'desc' => 'Odrzuć powiadomienie.',
    ],
    'pusher_settings' => [
      'title' => 'Pobierz ustawienia popychacza',
      'desc' => 'Pobierz pełną konfigurację Pushera. Dostępne dla wszystkich uwierzytelnionych użytkowników (superadministrator, administrator, personel).',
    ],
    'pusher_broadcast' => [
      'title' => 'Pobierz ustawienia transmisji',
      'desc' => 'Uzyskaj konfigurację transmisji Pusher w czasie rzeczywistym. Ustawienia ogólnosystemowe dla wszystkich użytkowników.',
    ],
    'pusher_beams' => [
      'title' => 'Pobierz ustawienia belek',
      'desc' => 'Pobierz konfigurację powiadomień Pusher Beams. Dostępne dla wszystkich uwierzytelnionych użytkowników.',
    ],
    'pusher_status' => [
      'title' => 'Sprawdź stan popychacza',
      'desc' => 'Szybkie sprawdzenie stanu w celu sprawdzenia, czy usługi Pusher są włączone. Dostępne dla wszystkich użytkowników.',
    ],
    'pusher_authorize' => [
      'title' => 'Autoryzuj kanał',
      'desc' => 'Autoryzuj dostęp użytkownika do kanałów prywatnych i obecności. Wymaga prawidłowego uwierzytelnienia.',
    ],
    'pusher_presence' => [
      'title' => 'Zdobądź członków obecności',
      'desc' => 'Pobierz listę użytkowników aktualnie podłączonych do kanału obecności. Dane ogólnosystemowe.',
    ],
  ],
];