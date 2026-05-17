<?php

return [
    // PAGE TITLES & HEADINGS
    'page_title'                => 'Serwery testowe - Testuj mapy i mody',
    'page_heading'               => 'Serwery testowe',
    'launch_page_title'          => 'Testuj mapę',
    'launch_heading'             => 'Uruchom mapę',
    'session_page_title'         => 'Twoja sesja',
    'session_heading'            => 'Sesja w toku',

    // BREADCRUMBS
    'breadcrumb_home'            => 'Start',
    'breadcrumb_testserver'      => 'Serwery testowe',
    'breadcrumb_session'         => 'Sesja',

    // INDEX
    'no_servers_available'       => 'Aktualnie brak dostępnych serwerów testowych.',
    'card_slot_number'           => 'Slot #:number',
    'card_max_duration'          => 'Maks. czas',
    'card_max_players'           => 'Maks. graczy',
    'card_default_mod'           => 'Domyślny mod',
    'card_total_sessions'        => 'Sesje łącznie',
    'card_minutes'               => ':min min',
    'card_btn_start_session'     => 'Rozpocznij sesję',
    'card_btn_busy'              => 'Zajęty',
    'rules_heading'              => 'Uwagi',

    // STATUS LABELS
    'status_idle'                => 'Wolny',
    'status_reserving'           => 'Rezerwacja',
    'status_active'              => 'Aktywny',
    'status_cleanup'             => 'Czyszczenie',
    'status_offline'             => 'Offline',
    'status_maintenance'         => 'Konserwacja',
    'status_pending'             => 'Oczekiwanie',
    'status_launching'           => 'Uruchamianie',
    'status_expiring'            => 'Kończenie',
    'status_expired'             => 'Zakończona',
    'status_cancelled'           => 'Anulowana',
    'status_failed'              => 'Błąd',

    // LAUNCH PAGE
    'launch_choose_server'       => 'Wybierz serwer',
    'launch_choose_mod'          => 'Wybierz mod',
    'launch_choose_map'          => 'Mapa',
    'launch_map_label'           => 'Mapa',
    'launch_no_map_selected'     => 'Nie wybrano mapy',
    'launch_session_duration'    => 'Czas sesji: :min minut',
    'launch_btn_reserve'         => 'Zarezerwuj i Uruchom',
    'launch_btn_back'            => 'Wstecz',
    'launch_reserving'           => 'Przygotowywanie serwera...',
    'launch_reserving_hint'      => 'Może to potrwać do 30 sekund. Pozostaw tę stronę otwartą.',
    'launch_error_generic'       => 'Coś poszło nie tak. Spróbuj ponownie.',
    'launch_select_server_first' => 'Wybierz najpierw serwer.',
    'launch_select_mod_first'    => 'Wybierz najpierw mod.',

    // SESSION PAGE
    'session_ready'              => 'Serwer gotowy!',
    'session_loading'            => 'Serwer się uruchamia...',
    'session_ended'              => 'Sesja zakończona',
    'session_failed'             => 'Sesja nieudana',
    'session_connect_address'    => 'Adres serwera',
    'session_connect_password'   => 'Hasło',
    'session_map'                => 'Mapa',
    'session_mod'                => 'Mod',
    'session_remaining'          => 'Pozostały czas',
    'session_started_at'         => 'Rozpoczęta o',
    'session_expires_at'         => 'Kończy się o',
    'session_btn_connect'        => 'Połącz z ET',
    'session_btn_copy_address'   => 'Kopiuj adres',
    'session_btn_copy_password'  => 'Kopiuj hasło',
    'session_btn_cancel'         => 'Zakończ sesję',
    'session_btn_new_session'    => 'Rozpocznij nową sesję',
    'session_copied'             => 'Skopiowano!',
    'session_cancel_confirm'     => 'Naprawdę zakończyć sesję?',
    'session_cancel_success'     => 'Sesja jest kończona, serwer zostanie zresetowany za kilka sekund.',
    'session_connect_help'       => 'Otwórz ET, naciśnij `~` aby otworzyć konsolę i wpisz: /password :password potem /connect :address',
    'session_minutes_short'      => 'min',
    'session_seconds_short'      => 'sek',

    // ERRORS / RATE LIMIT
    'error_feature_disabled'     => 'System serwerów testowych jest obecnie wyłączony.',
    'error_no_server_free'       => 'Aktualnie brak wolnego serwera testowego.',
    'error_login_required'       => 'Sesje testowe wymagają zalogowania.',
    'error_already_active'       => 'Masz już aktywną sesję.',
    'error_rate_limit'           => 'Osiągnięto limit. Spróbuj ponownie później.',
    'error_cooldown'             => 'Poczekaj jeszcze :min minut(y) do następnej sesji.',
    'error_mod_not_allowed'      => 'Ten mod nie jest dozwolony na tym serwerze.',
    'error_server_not_available' => 'Ten serwer jest aktualnie niedostępny.',

    // MAP-DETAIL SIDEBAR
    'sidebar_test_map_button'    => 'Testuj mapę',
    'sidebar_test_map_hint'      => ':min-min sesja na wolnym serwerze testowym',
    'intro_text' => 'Testuj mapy i mody na żywo na naszych serwerach testowych. Wybierz wolny serwer i rozpocznij sesję natychmiast.',
    'fair_play_notice' => 'Graj uczciwie. Sesje są ograniczone czasowo. Nadużycia skutkują banem IP.',
];
