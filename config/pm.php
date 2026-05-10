<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    | Days after which message bodies and attachments are purged.
    | Metadata (sender, timestamps) is kept longer for abuse pattern detection.
    */
    "retention_body_days"     => env("PM_RETENTION_BODY_DAYS", 180),
    "retention_metadata_days" => env("PM_RETENTION_META_DAYS", 730),

    /*
    |--------------------------------------------------------------------------
    | Conversation limits
    |--------------------------------------------------------------------------
    */
    "max_participants_group"  => 10,
    "max_subject_length"      => 200,

    /*
    |--------------------------------------------------------------------------
    | Message limits
    |--------------------------------------------------------------------------
    */
    "max_body_length"         => 10_000,
    "edit_window_minutes"     => env("PM_EDIT_WINDOW_MINUTES", 15),
    "max_attachments_per_msg" => 6,
    "max_attachment_size_mb"  => 10,
    "allowed_mime_types"      => [
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limits per role
    |--------------------------------------------------------------------------
    | Per-user thresholds. Lower of hourly/daily applies.
    | Roles checked in order; first matching role wins.
    */
    "rate_limits" => [
        "default" => [
            "new_conversation" => ["hour" => 5,   "day" => 20],
            "send_message"     => ["hour" => 30,  "day" => 200],
        ],
        "moderator" => [
            "new_conversation" => ["hour" => 30,  "day" => 200],
            "send_message"     => ["hour" => 200, "day" => 1000],
        ],
        "admin" => [
            "new_conversation" => ["hour" => 100, "day" => 500],
            "send_message"     => ["hour" => 500, "day" => 2000],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    "notification_throttle_default_minutes" => 15,

    /*
    |--------------------------------------------------------------------------
    | Storage disk for attachments
    |--------------------------------------------------------------------------
    | Should match a configured filesystem in config/filesystems.php.
    | Hetzner Object Storage is configured as "s3" in this project.
    */
    "attachment_disk" => env("PM_ATTACHMENT_DISK", "s3"),
    "attachment_path_prefix" => "pm-attachments",

];
