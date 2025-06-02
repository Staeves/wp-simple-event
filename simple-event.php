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


}
