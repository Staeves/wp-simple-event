<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// drop the custom database tables
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sieve_events" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sieve_registrations" );
