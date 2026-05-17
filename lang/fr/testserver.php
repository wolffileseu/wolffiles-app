<?php

return [
    // PAGE TITLES & HEADINGS
    'page_title'                => 'Serveurs de test - Tester maps & mods',
    'page_heading'               => 'Serveurs de test',
    'launch_page_title'          => 'Tester la map',
    'launch_heading'             => 'Lancer la map',
    'session_page_title'         => 'Votre session',
    'session_heading'            => 'Session en cours',

    // BREADCRUMBS
    'breadcrumb_home'            => 'Accueil',
    'breadcrumb_testserver'      => 'Serveurs de test',
    'breadcrumb_session'         => 'Session',

    // INDEX
    'no_servers_available'       => 'Aucun serveur de test n\'est disponible actuellement.',
    'card_slot_number'           => 'Emplacement #:number',
    'card_max_duration'          => 'Durée max.',
    'card_max_players'           => 'Joueurs max.',
    'card_default_mod'           => 'Mod par défaut',
    'card_total_sessions'        => 'Sessions totales',
    'card_minutes'               => ':min min',
    'card_btn_start_session'     => 'Démarrer la session',
    'card_btn_busy'              => 'Occupé',
    'rules_heading'              => 'Remarques',

    // STATUS LABELS
    'status_idle'                => 'Libre',
    'status_reserving'           => 'Réservation',
    'status_active'              => 'Actif',
    'status_cleanup'             => 'Nettoyage',
    'status_offline'             => 'Hors ligne',
    'status_maintenance'         => 'Maintenance',
    'status_pending'             => 'En attente',
    'status_launching'           => 'Démarrage',
    'status_expiring'            => 'Fin en cours',
    'status_expired'             => 'Terminé',
    'status_cancelled'           => 'Annulé',
    'status_failed'              => 'Échec',

    // LAUNCH PAGE
    'launch_choose_server'       => 'Choisir le serveur',
    'launch_choose_mod'          => 'Choisir le mod',
    'launch_choose_map'          => 'Map',
    'launch_map_label'           => 'Map',
    'launch_no_map_selected'     => 'Aucune map sélectionnée',
    'launch_session_duration'    => 'Durée de session : :min minutes',
    'launch_btn_reserve'         => 'Réserver & Lancer',
    'launch_btn_back'            => 'Retour',
    'launch_reserving'           => 'Préparation du serveur...',
    'launch_reserving_hint'      => 'Cela peut prendre jusqu\'à 30 secondes. Veuillez garder cette page ouverte.',
    'launch_error_generic'       => 'Une erreur est survenue. Veuillez réessayer.',
    'launch_select_server_first' => 'Veuillez sélectionner un serveur.',
    'launch_select_mod_first'    => 'Veuillez sélectionner un mod.',

    // SESSION PAGE
    'session_ready'              => 'Serveur prêt !',
    'session_loading'            => 'Le serveur démarre...',
    'session_ended'              => 'Session terminée',
    'session_failed'             => 'Échec de la session',
    'session_connect_address'    => 'Adresse du serveur',
    'session_connect_password'   => 'Mot de passe',
    'session_map'                => 'Map',
    'session_mod'                => 'Mod',
    'session_remaining'          => 'Temps restant',
    'session_started_at'         => 'Démarrée à',
    'session_expires_at'         => 'Se termine à',
    'session_btn_connect'        => 'Se connecter à ET',
    'session_btn_copy_address'   => 'Copier l\'adresse',
    'session_btn_copy_password'  => 'Copier le mot de passe',
    'session_btn_cancel'         => 'Terminer la session',
    'session_btn_new_session'    => 'Démarrer une nouvelle session',
    'session_copied'             => 'Copié !',
    'session_cancel_confirm'     => 'Vraiment terminer la session ?',
    'session_cancel_success'     => 'La session se termine, le serveur sera réinitialisé dans quelques secondes.',
    'session_connect_help'       => 'Ouvrez ET, appuyez sur `~` pour la console et tapez : /connect :address;password :password',
    'session_minutes_short'      => 'min',
    'session_seconds_short'      => 'sec',

    // ERRORS / RATE LIMIT
    'error_feature_disabled'     => 'Le système de serveurs de test est actuellement désactivé.',
    'error_no_server_free'       => 'Aucun serveur de test n\'est disponible pour le moment.',
    'error_login_required'       => 'Une connexion est requise pour les sessions de test.',
    'error_already_active'       => 'Vous avez déjà une session active.',
    'error_rate_limit'           => 'Limite atteinte. Veuillez réessayer plus tard.',
    'error_cooldown'             => 'Veuillez attendre encore :min minute(s) avant la prochaine session.',
    'error_mod_not_allowed'      => 'Ce mod n\'est pas autorisé sur ce serveur.',
    'error_server_not_available' => 'Ce serveur n\'est pas disponible actuellement.',

    // MAP-DETAIL SIDEBAR
    'sidebar_test_map_button'    => 'Tester la map',
    'sidebar_test_map_hint'      => 'Session de :min min sur un serveur libre',
];
