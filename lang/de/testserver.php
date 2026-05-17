<?php

return [
    // ───────── PAGE TITLES & HEADINGS ─────────
    'page_title'                => 'Testserver - Maps & Mods testen',
    'page_heading'               => 'Testserver',
    'launch_page_title'          => 'Map testen',
    'launch_heading'             => 'Map starten',
    'session_page_title'         => 'Deine Session',
    'session_heading'            => 'Session läuft',

    // ───────── BREADCRUMBS ─────────
    'breadcrumb_home'            => 'Home',
    'breadcrumb_testserver'      => 'Testserver',
    'breadcrumb_session'         => 'Session',

    // ───────── INDEX (Übersicht) ─────────
    'no_servers_available'       => 'Aktuell sind keine Testserver verfügbar.',
    'card_slot_number'           => 'Slot #:number',
    'card_max_duration'          => 'Max. Dauer',
    'card_max_players'           => 'Max. Spieler',
    'card_default_mod'           => 'Default Mod',
    'card_total_sessions'        => 'Sessions gesamt',
    'card_minutes'               => ':min Min',
    'card_btn_start_session'     => 'Session starten',
    'card_btn_busy'              => 'Belegt',
    'rules_heading'              => 'Hinweise',

    // ───────── STATUS LABELS ─────────
    'status_idle'                => 'Frei',
    'status_reserving'           => 'Wird reserviert',
    'status_active'              => 'Aktiv',
    'status_cleanup'             => 'Wird beendet',
    'status_offline'             => 'Offline',
    'status_maintenance'         => 'Wartung',
    'status_pending'             => 'Warte',
    'status_launching'           => 'Startet',
    'status_expiring'            => 'Beende',
    'status_expired'             => 'Beendet',
    'status_cancelled'           => 'Abgebrochen',
    'status_failed'              => 'Fehler',

    // ───────── LAUNCH PAGE ─────────
    'launch_choose_server'       => 'Server wählen',
    'launch_choose_mod'          => 'Mod auswählen',
    'launch_choose_map'          => 'Map',
    'launch_map_label'           => 'Map',
    'launch_no_map_selected'     => 'Keine Map ausgewählt',
    'launch_session_duration'    => 'Session-Dauer: :min Minuten',
    'launch_btn_reserve'         => 'Reservieren & Starten',
    'launch_btn_back'            => 'Zurück',
    'launch_reserving'           => 'Server wird vorbereitet...',
    'launch_reserving_hint'      => 'Das kann bis zu 30 Sekunden dauern. Bitte Seite offen lassen.',
    'launch_error_generic'       => 'Etwas ist schiefgelaufen. Bitte versuche es erneut.',
    'launch_select_server_first' => 'Bitte wähle einen Server.',
    'launch_select_mod_first'    => 'Bitte wähle einen Mod.',

    // ───────── SESSION PAGE (Connect) ─────────
    'session_ready'              => 'Server bereit!',
    'session_loading'            => 'Server startet...',
    'session_ended'              => 'Session beendet',
    'session_failed'             => 'Session fehlgeschlagen',
    'session_connect_address'    => 'Server-Adresse',
    'session_connect_password'   => 'Password',
    'session_map'                => 'Map',
    'session_mod'                => 'Mod',
    'session_remaining'          => 'Verbleibende Zeit',
    'session_started_at'         => 'Gestartet um',
    'session_expires_at'         => 'Endet um',
    'session_btn_connect'        => 'In ET verbinden',
    'session_btn_copy_address'   => 'Adresse kopieren',
    'session_btn_copy_password'  => 'Password kopieren',
    'session_btn_cancel'         => 'Session beenden',
    'session_btn_new_session'    => 'Neue Session starten',
    'session_copied'             => 'Kopiert!',
    'session_cancel_confirm'     => 'Session wirklich beenden?',
    'session_cancel_success'     => 'Session wird beendet, Server resetted in wenigen Sekunden.',
    'session_connect_help'       => 'Öffne ET, drücke `~` für die Konsole und tippe: /password :password und dann /connect :address',
    'session_minutes_short'      => 'Min',
    'session_seconds_short'      => 'Sek',

    // ───────── ERRORS / RATE LIMIT ─────────
    'error_feature_disabled'     => 'Das Testserver-System ist aktuell deaktiviert.',
    'error_no_server_free'       => 'Aktuell ist kein freier Testserver verfügbar.',
    'error_login_required'       => 'Für Testsessions ist ein Login erforderlich.',
    'error_already_active'       => 'Du hast bereits eine aktive Session.',
    'error_rate_limit'           => 'Rate-Limit erreicht. Bitte später erneut versuchen.',
    'error_cooldown'             => 'Bitte warte noch :min Minute(n) bis zur nächsten Session.',
    'error_mod_not_allowed'      => 'Dieser Mod ist auf diesem Server nicht erlaubt.',
    'error_server_not_available' => 'Dieser Server ist gerade nicht verfügbar.',

    // ───────── MAP-DETAIL SIDEBAR ─────────
    'sidebar_test_map_button'    => 'Map testen',
    'sidebar_test_map_hint'      => ':min-Min-Session auf freiem Testserver',
    'intro_text' => 'Teste hier Maps und Mods live auf unseren Testservern. Wähle einen freien Server und starte sofort eine Session.',
    'fair_play_notice' => 'Bitte fair spielen. Sessions sind zeitlich begrenzt. Bei Missbrauch wird die IP gesperrt.',
];
