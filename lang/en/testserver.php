<?php

return [
    // PAGE TITLES & HEADINGS
    'page_title'                => 'Test Servers - Test Maps & Mods',
    'page_heading'               => 'Test Servers',
    'launch_page_title'          => 'Test Map',
    'launch_heading'             => 'Launch Map',
    'session_page_title'         => 'Your Session',
    'session_heading'            => 'Session running',

    // BREADCRUMBS
    'breadcrumb_home'            => 'Home',
    'breadcrumb_testserver'      => 'Test Servers',
    'breadcrumb_session'         => 'Session',

    // INDEX
    'no_servers_available'       => 'No test servers are currently available.',
    'card_slot_number'           => 'Slot #:number',
    'card_max_duration'          => 'Max. duration',
    'card_max_players'           => 'Max. players',
    'card_default_mod'           => 'Default mod',
    'card_total_sessions'        => 'Total sessions',
    'card_minutes'               => ':min min',
    'card_btn_start_session'     => 'Start session',
    'card_btn_busy'              => 'Busy',
    'rules_heading'              => 'Notes',

    // STATUS LABELS
    'status_idle'                => 'Free',
    'status_reserving'           => 'Reserving',
    'status_active'              => 'Active',
    'status_cleanup'             => 'Cleaning up',
    'status_offline'             => 'Offline',
    'status_maintenance'         => 'Maintenance',
    'status_pending'             => 'Waiting',
    'status_launching'           => 'Starting',
    'status_expiring'            => 'Ending',
    'status_expired'             => 'Ended',
    'status_cancelled'           => 'Cancelled',
    'status_failed'              => 'Failed',

    // LAUNCH PAGE
    'launch_choose_server'       => 'Choose server',
    'launch_choose_mod'          => 'Choose mod',
    'launch_choose_map'          => 'Map',
    'launch_map_label'           => 'Map',
    'launch_no_map_selected'     => 'No map selected',
    'launch_session_duration'    => 'Session duration: :min minutes',
    'launch_btn_reserve'         => 'Reserve & Start',
    'launch_btn_back'            => 'Back',
    'launch_reserving'           => 'Preparing server...',
    'launch_reserving_hint'      => 'This may take up to 30 seconds. Please keep this page open.',
    'launch_error_generic'       => 'Something went wrong. Please try again.',
    'launch_select_server_first' => 'Please select a server.',
    'launch_select_mod_first'    => 'Please select a mod.',

    // SESSION PAGE
    'session_ready'              => 'Server ready!',
    'session_loading'            => 'Server is starting...',
    'session_ended'              => 'Session ended',
    'session_failed'             => 'Session failed',
    'session_connect_address'    => 'Server address',
    'session_connect_password'   => 'Password',
    'session_map'                => 'Map',
    'session_mod'                => 'Mod',
    'session_remaining'          => 'Time remaining',
    'session_started_at'         => 'Started at',
    'session_expires_at'         => 'Ends at',
    'session_btn_connect'        => 'Connect to ET',
    'session_btn_copy_address'   => 'Copy address',
    'session_btn_copy_password'  => 'Copy password',
    'session_btn_cancel'         => 'End session',
    'session_btn_new_session'    => 'Start new session',
    'session_copied'             => 'Copied!',
    'session_cancel_confirm'     => 'Really end the session?',
    'session_cancel_success'     => 'Session is ending, server will reset in a few seconds.',
    'session_connect_help'       => 'Open ET, press `~` to open the console, then type: /password :password and /connect :address',
    'session_minutes_short'      => 'min',
    'session_seconds_short'      => 'sec',

    // ERRORS / RATE LIMIT
    'error_feature_disabled'     => 'The test server system is currently disabled.',
    'error_no_server_free'       => 'No free test server is currently available.',
    'error_login_required'       => 'Login is required for test sessions.',
    'error_already_active'       => 'You already have an active session.',
    'error_rate_limit'           => 'Rate limit reached. Please try again later.',
    'error_cooldown'             => 'Please wait :min more minute(s) until your next session.',
    'error_mod_not_allowed'      => 'This mod is not allowed on this server.',
    'error_server_not_available' => 'This server is not currently available.',

    // MAP-DETAIL SIDEBAR
    'sidebar_test_map_button'    => 'Test map',
    'sidebar_test_map_hint'      => ':min-min session on a free test server',
    'intro_text' => 'Test maps and mods live on our testservers. Pick a free server and start a session right away.',
    'fair_play_notice' => 'Please play fair. Sessions are time-limited. Misuse will result in IP bans.',
];
