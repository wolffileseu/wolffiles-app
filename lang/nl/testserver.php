<?php

return [
    // PAGE TITLES & HEADINGS
    'page_title'                => 'Testservers - Maps & mods testen',
    'page_heading'               => 'Testservers',
    'launch_page_title'          => 'Map testen',
    'launch_heading'             => 'Map starten',
    'session_page_title'         => 'Jouw sessie',
    'session_heading'            => 'Sessie loopt',

    // BREADCRUMBS
    'breadcrumb_home'            => 'Home',
    'breadcrumb_testserver'      => 'Testservers',
    'breadcrumb_session'         => 'Sessie',

    // INDEX
    'no_servers_available'       => 'Momenteel zijn er geen testservers beschikbaar.',
    'card_slot_number'           => 'Slot #:number',
    'card_max_duration'          => 'Max. duur',
    'card_max_players'           => 'Max. spelers',
    'card_default_mod'           => 'Standaard mod',
    'card_total_sessions'        => 'Totale sessies',
    'card_minutes'               => ':min min',
    'card_btn_start_session'     => 'Sessie starten',
    'card_btn_busy'              => 'Bezet',
    'rules_heading'              => 'Opmerkingen',

    // STATUS LABELS
    'status_idle'                => 'Vrij',
    'status_reserving'           => 'Reserveren',
    'status_active'              => 'Actief',
    'status_cleanup'             => 'Opruimen',
    'status_offline'             => 'Offline',
    'status_maintenance'         => 'Onderhoud',
    'status_pending'             => 'Wachten',
    'status_launching'           => 'Starten',
    'status_expiring'            => 'Beëindigen',
    'status_expired'             => 'Beëindigd',
    'status_cancelled'           => 'Geannuleerd',
    'status_failed'              => 'Mislukt',

    // LAUNCH PAGE
    'launch_choose_server'       => 'Server kiezen',
    'launch_choose_mod'          => 'Mod kiezen',
    'launch_choose_map'          => 'Map',
    'launch_map_label'           => 'Map',
    'launch_no_map_selected'     => 'Geen map geselecteerd',
    'launch_session_duration'    => 'Sessieduur: :min minuten',
    'launch_btn_reserve'         => 'Reserveren & Starten',
    'launch_btn_back'            => 'Terug',
    'launch_reserving'           => 'Server wordt voorbereid...',
    'launch_reserving_hint'      => 'Dit kan tot 30 seconden duren. Houd deze pagina open.',
    'launch_error_generic'       => 'Er is iets misgegaan. Probeer het opnieuw.',
    'launch_select_server_first' => 'Kies eerst een server.',
    'launch_select_mod_first'    => 'Kies eerst een mod.',

    // SESSION PAGE
    'session_ready'              => 'Server klaar!',
    'session_loading'            => 'Server start...',
    'session_ended'              => 'Sessie beëindigd',
    'session_failed'             => 'Sessie mislukt',
    'session_connect_address'    => 'Serveradres',
    'session_connect_password'   => 'Wachtwoord',
    'session_map'                => 'Map',
    'session_mod'                => 'Mod',
    'session_remaining'          => 'Resterende tijd',
    'session_started_at'         => 'Gestart om',
    'session_expires_at'         => 'Eindigt om',
    'session_btn_connect'        => 'Verbinden met ET',
    'session_btn_copy_address'   => 'Adres kopiëren',
    'session_btn_copy_password'  => 'Wachtwoord kopiëren',
    'session_btn_cancel'         => 'Sessie beëindigen',
    'session_btn_new_session'    => 'Nieuwe sessie starten',
    'session_copied'             => 'Gekopieerd!',
    'session_cancel_confirm'     => 'Sessie echt beëindigen?',
    'session_cancel_success'     => 'Sessie wordt beëindigd, server wordt over enkele seconden gereset.',
    'session_connect_help'       => 'Open ET, druk op `~` voor de console en typ: /connect :address;password :password',
    'session_minutes_short'      => 'min',
    'session_seconds_short'      => 'sec',

    // ERRORS / RATE LIMIT
    'error_feature_disabled'     => 'Het testserver-systeem is momenteel uitgeschakeld.',
    'error_no_server_free'       => 'Er is momenteel geen vrije testserver beschikbaar.',
    'error_login_required'       => 'Inloggen is vereist voor testsessies.',
    'error_already_active'       => 'Je hebt al een actieve sessie.',
    'error_rate_limit'           => 'Limiet bereikt. Probeer het later opnieuw.',
    'error_cooldown'             => 'Wacht nog :min minu(u)t(en) tot de volgende sessie.',
    'error_mod_not_allowed'      => 'Deze mod is niet toegestaan op deze server.',
    'error_server_not_available' => 'Deze server is momenteel niet beschikbaar.',

    // MAP-DETAIL SIDEBAR
    'sidebar_test_map_button'    => 'Map testen',
    'sidebar_test_map_hint'      => ':min-min-sessie op een vrije testserver',
];
