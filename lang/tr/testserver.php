<?php

return [
    // PAGE TITLES & HEADINGS
    'page_title'                => 'Test Sunucuları - Map ve Mod Test Et',
    'page_heading'               => 'Test Sunucuları',
    'launch_page_title'          => 'Map Test Et',
    'launch_heading'             => 'Map Başlat',
    'session_page_title'         => 'Oturumun',
    'session_heading'            => 'Oturum aktif',

    // BREADCRUMBS
    'breadcrumb_home'            => 'Ana Sayfa',
    'breadcrumb_testserver'      => 'Test Sunucuları',
    'breadcrumb_session'         => 'Oturum',

    // INDEX
    'no_servers_available'       => 'Şu anda kullanılabilir test sunucusu yok.',
    'card_slot_number'           => 'Slot #:number',
    'card_max_duration'          => 'Maks. süre',
    'card_max_players'           => 'Maks. oyuncu',
    'card_default_mod'           => 'Varsayılan mod',
    'card_total_sessions'        => 'Toplam oturum',
    'card_minutes'               => ':min dk',
    'card_btn_start_session'     => 'Oturum başlat',
    'card_btn_busy'              => 'Meşgul',
    'rules_heading'              => 'Notlar',

    // STATUS LABELS
    'status_idle'                => 'Boş',
    'status_reserving'           => 'Rezerve ediliyor',
    'status_active'              => 'Aktif',
    'status_cleanup'             => 'Temizleniyor',
    'status_offline'             => 'Çevrimdışı',
    'status_maintenance'         => 'Bakım',
    'status_pending'             => 'Bekliyor',
    'status_launching'           => 'Başlatılıyor',
    'status_expiring'            => 'Sonlandırılıyor',
    'status_expired'             => 'Sonlandı',
    'status_cancelled'           => 'İptal edildi',
    'status_failed'              => 'Hata',

    // LAUNCH PAGE
    'launch_choose_server'       => 'Sunucu seç',
    'launch_choose_mod'          => 'Mod seç',
    'launch_choose_map'          => 'Map',
    'launch_map_label'           => 'Map',
    'launch_no_map_selected'     => 'Map seçilmedi',
    'launch_session_duration'    => 'Oturum süresi: :min dakika',
    'launch_btn_reserve'         => 'Rezerve Et & Başlat',
    'launch_btn_back'            => 'Geri',
    'launch_reserving'           => 'Sunucu hazırlanıyor...',
    'launch_reserving_hint'      => 'Bu 30 saniyeye kadar sürebilir. Lütfen bu sayfayı açık tutun.',
    'launch_error_generic'       => 'Bir şeyler ters gitti. Lütfen tekrar deneyin.',
    'launch_select_server_first' => 'Lütfen önce bir sunucu seçin.',
    'launch_select_mod_first'    => 'Lütfen önce bir mod seçin.',

    // SESSION PAGE
    'session_ready'              => 'Sunucu hazır!',
    'session_loading'            => 'Sunucu başlıyor...',
    'session_ended'              => 'Oturum sonlandı',
    'session_failed'             => 'Oturum başarısız oldu',
    'session_connect_address'    => 'Sunucu adresi',
    'session_connect_password'   => 'Şifre',
    'session_map'                => 'Map',
    'session_mod'                => 'Mod',
    'session_remaining'          => 'Kalan süre',
    'session_started_at'         => 'Başlangıç',
    'session_expires_at'         => 'Bitiş',
    'session_btn_connect'        => 'ET\'ye bağlan',
    'session_btn_copy_address'   => 'Adresi kopyala',
    'session_btn_copy_password'  => 'Şifreyi kopyala',
    'session_btn_cancel'         => 'Oturumu sonlandır',
    'session_btn_new_session'    => 'Yeni oturum başlat',
    'session_copied'             => 'Kopyalandı!',
    'session_cancel_confirm'     => 'Oturum gerçekten sonlandırılsın mı?',
    'session_cancel_success'     => 'Oturum sonlandırılıyor, sunucu birkaç saniye içinde sıfırlanacak.',
    'session_connect_help'       => 'ET\'yi aç, konsol için `~` tuşuna bas ve şunu yaz: /connect :address;password :password',
    'session_minutes_short'      => 'dk',
    'session_seconds_short'      => 'sn',

    // ERRORS / RATE LIMIT
    'error_feature_disabled'     => 'Test sunucusu sistemi şu anda devre dışı.',
    'error_no_server_free'       => 'Şu anda boş test sunucusu yok.',
    'error_login_required'       => 'Test oturumları için giriş gerekli.',
    'error_already_active'       => 'Zaten aktif bir oturumun var.',
    'error_rate_limit'           => 'Limit aşıldı. Lütfen daha sonra tekrar deneyin.',
    'error_cooldown'             => 'Bir sonraki oturum için lütfen :min dakika daha bekle.',
    'error_mod_not_allowed'      => 'Bu mod bu sunucuda izin verilmiyor.',
    'error_server_not_available' => 'Bu sunucu şu anda kullanılabilir değil.',

    // MAP-DETAIL SIDEBAR
    'sidebar_test_map_button'    => 'Map test et',
    'sidebar_test_map_hint'      => 'Boş test sunucusunda :min dakikalık oturum',
];
