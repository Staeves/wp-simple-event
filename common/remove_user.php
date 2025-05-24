<?php

/* remove a registration, either from admin, or through link in confirmation mail
 * $id 	is the id of the registration
 */
function remove_registration($id) {
	global $wpdb;
	// delete registrations
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM " . $wpdb->prefix . "sieve_registrations 
			WHERE id = %d;", 
		$id));
}
