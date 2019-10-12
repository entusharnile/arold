<?php

return [
    /*
      |--------------------------------------------------------------------------
      | Authentication Language Lines
      |--------------------------------------------------------------------------
      |
      | The following language lines are used during authentication for various
      | messages that we need to display to the user. You are free to modify
      | these language lines according to your application's requirements.
      |
     */

    'failed' => 'These credentials do not match our records.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'db' => [
        'db_not_connected' => 'Failed to connect database.'
    ],
    'login' => [
        'invalid_credentials' => 'Invalid credentials.',
        'upgrade_account_to_stripe'=>'Upgrade account to stripe',
        'storage_limit_reached_upgrade_account' => 'Your account data storage limit has reached. Please upgrade your account to continue.',
        'storage_limit_reached_buy_storage' => 'Your account data storage limit has reached. Please buy more data storage to continue',
        'already_logged_in' => 'You are already logged in to another device. Do you want to login here and logout from other device?',
        'subscription_cancelled' => 'Your subscription has been cancelled and your subscription cycle is expired. Please resume your subscription to continue our services.',
        'subscription_cancelled_by_admin' => 'Your subscription has been cancelled by your account admin and your subscription cycle is expired. Please contact your account admin to resume your account.',
        'trial_subscription_expired' => 'Your trial subscription has expired. Please upgrade your account to continue our services.',
        'trial_subscription_expired_contact_admin' => 'Your trial subscription has expired. Please contact your account admin to upgrade your account.',
        'payment_failure_account_locked' => 'Your account has been locked due to payment failure. Please contact info@aestheticrecord.com to resume your account.',
        'payment_failure_account_locked_contact_admin' => 'Your account has been locked due to payment failure. Please contact your account admin.',
        'storage_limit_reached_contact_admin' => 'Your account data storage limit has reached. Please contact your account admin.',
        'account_locked_contact_administrator' => 'Your account has been locked by system administrator. Please contact info@aestheticrecord.com to resume your account',
        'login_success' => 'Logged in successfully.',
        'payment_failure_account_onhold' => 'Your account is on hold due to payment failure and you have :days_left :days to reactivate your account by paying failed amount, otherwise your account will be suspended if you failed to pay by :suspension_date',
        'sub_limit_reach_account_onhold' => 'Your account is on hold due to account subscription limit reached and you have :days_left :days as grace period, otherwise your account will be suspended by :suspension_date . Please contact here info@aestheticrecord.com',
        'account_suspend_reactivate_subscription'=>'You have cancelled your subscription, your account will be suspended in :days :days_string. Please reactivate your subscription to avoid your account suspension.',
    ],
];

