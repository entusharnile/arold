<?php

return [
    "default" => [
        "account_storage_folder" => "",
        "date_format" => "m/d/Y",
        "timezone" => "America/New_York",
        "booking_time_interval" => 5,
    ],
    "db" => [
        "master_db" => "betaar_master",
    ],
    "allowed_ips" => [
        "::1",
        "127.0.0.1",
        "192.168.0.200",
        "192.168.0.145",
        "192.168.0.133",
        "192.168.0.103"
    ],
    "roles" => [
        1 => "admin", 2 => "provider", 3 => "frontdesk", 4 => "md"
    ],
    "grace_period" => 10,
//    "secret_app_key" => "juvly12345",
    "security_salt" => "DYhG93b0qJfIxfs2guVoUuwvniR2G0FgaC",
    "force_upgrade_flag" => 1,
    "items_per_page" => 20,
    "status_codes" => [200, 201, 400, 404, 500],
    "min_login_attempts" => 5,
    "records_per_page" => 20,
    "default_user_id" => 169,
    "plan_code_array" => ['ar_subscription', 'ar_subscription-yearly'],
    "appointment_color" => [
        '#0000FF', '#DC143C', '#00008B', '#8B008B', '#FF8C00', '#9932CC', '#8B0000', '#FF1493', '#1E90FF', '#B22222',
        '#CD5C5C', '#DAA520', '#228B22', '#FF00FF', '#3CB371', '#008000', '#4B0082', '#20B2AA', '#32CD32', '#2F4F4F',
        '#0000CD', '#191970', '#FFA500', '#FF4500', '#FF0000', '#4682B4', '#8B4513', '#FF6347', '#800080', '#C71585'
    ],
    "ar_subscription_monthly" => [
        'plan_code' => 'ar_subscription',
        'storage_limit' => '2', // 2GB
        'sms_limit' => '100',
        'email_limit' => '300',
        'plan_type' => 'monthly',
        'price_per_user' => 12, // $12
        'data_price_per_gb' => 1, // $1
        'plan_description' => ['2GB Data Storage', '100 SMS/month', '300 Emails/month']
    ],
    "ar_subscription_yearly" => [
        'plan_code' => 'ar_subscription-yearly',
        'storage_limit' => '2', // 2GB
        'sms_limit' => '100',
        'email_limit' => '300',
        'plan_type' => 'yearly',
        'price_per_user' => 10, // $10
        'data_price_per_gb' => 1, // $1
        'plan_description' => ['2GB Data Storage', '100 SMS/month', '300 Emails/month']
    ],
    "default_language_id" => 1,
    "FROM_EMAIL" => "noreply@aestheticrecord.com"
];
