<?php
/*
Plugin Name: Simple Event
Description: A very simple but free and open source plugin for event registration.
Version: 1.1.0
Author: Adrian Staeves
Plugin URI: https://github.com/Staeves/wp-simple-event
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/
defined('ABSPATH') or die('No!');
add_action( 'sieve_remove_old', 'sieve_remove_old_func' );

/* 
 * define a wraper around wp_mail, that respects simpleEvent settings
 */
function sieve_mail($rec, $sub, $con) {
	$header = array();
	$sender_name = get_option("sieve_sender_name");
	$reply_to = get_option("sieve_reply_to");
	$filter_hook = function( $name ) use ($sender_name) {
			return $sender_name;
		};
	if ("" != $sender_name) {
		add_filter( 'wp_mail_from_name', $filter_hook , 100);
		if (is_email($reply_to)) {
			$header[] = "Reply-To: " . $sender_name . "<" . $reply_to . ">";
		}
	}
	remove_filter('wp_mail_from_name', $filter_hook, 100);
	wp_mail($rec, $sub, $con, $header);
}

require_once plugin_dir_path(__FILE__) . "admin/menu.php";
require_once plugin_dir_path(__FILE__) . "admin/settings.php";
require_once plugin_dir_path(__FILE__) . "frontend/shortcode.php";
require_once plugin_dir_path(__FILE__) . "frontend/cancel_registration.php";

/*
 * setup the database tables:
 * sieve_events:	id, date, opendate,  maxspots
 * sieve_registrations: id, name, email, eventid, registrationtime
 */
register_activation_hook( __FILE__, "sieve_install");
function sieve_install () {
	global $wpdb;

	$table_name1 = $wpdb->prefix . "sieve_events";
	$table_name2 = $wpdb->prefix . "sieve_registrations";

	$charset_collate = $wpdb->get_charset_collate();

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$sql = "CREATE TABLE $table_name1 (
		id int(9) UNSIGNED NOT NULL AUTO_INCREMENT,
		date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		opendate datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		maxspots smallint(9) UNSIGNED NOT NULL,
		PRIMARY KEY  (id)
		) $charset_collate;";
	dbDelta( $sql );

	$sql = "CREATE TABLE $table_name2 (
		id int(9) UNSIGNED NOT NULL AUTO_INCREMENT,
		name varchar(50) DEFAULT '' NOT NULL,
		email varchar(50) DEFAULT '' NOT NULL,
		registrationtime datetime NOT NULL,
		eventid int(9) UNSIGNED NOT NULL,
		PRIMARY KEY  (id)
		) $charset_collate;";
	dbDelta( $sql );

	// shedule cron events
	if (! wp_next_scheduled ( 'sieve_remove_old' )) {
		wp_schedule_event( time() + 10, 'weekly', 'sieve_remove_old' ); // first trigger always 10s after activation of the plugin
		error_log("sheduled");
	}
}

register_deactivation_hook( __FILE__, 'sieve_deactivate' );
function sieve_deactivate() {
	wp_clear_scheduled_hook( 'sieve_remove_old' );
}

function sieve_remove_old_func() {
	global $wpdb;
	// called weekly, remove all registrations and events that are more than a month old
	error_log( 'Hello World!' );
	$old_events = $wpdb->get_col(
		"SELECT id 
		FROM " . $wpdb->prefix . "sieve_events
		WHERE DATE_SUB(NOW(), INTERVAL 1 MONTH) >= date");
	foreach ($old_events as $event) {
		// delete user data for these old events
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM " . $wpdb->prefix . "sieve_registrations
				WHERE eventid = %d",
			$event));
		// and delete the event
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM " . $wpdb->prefix . "sieve_events
				WHERE id = %d",
			$event));
		
	}

}
