<?php
/*
Plugin Name: Simple Event
Description: A very simple but free and open source plugin for event registration.
Version: 0.1
Author: Adrian Staeves
Plugin URI: https://github.com/Staeves/wp-simple-event
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/
defined('ABSPATH') or die('No!');

require_once plugin_dir_path(__FILE__) . "admin/menu.php";
require_once plugin_dir_path(__FILE__) . "frontend/shortcode.php";

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
