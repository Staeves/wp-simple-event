<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// unshedule cron jobs
wp_unschedule_hook('sieve_notify_about_participants');

// drop the custom database tables
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sieve_events" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sieve_registrations" );
