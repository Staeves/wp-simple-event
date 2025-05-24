<?php
require_once plugin_dir_path(__FILE__) . "../common/remove_user.php";

add_action( 'admin_post_nopriv_sieve_cancel', 'sieve_cancel');

/* 
 * handle cancelation through link
 * link has to be <admin_url>admin-post.php/?action=sieve_cancel&sieve-id=<id>&sieve-key=<hash>
 * the hash is sha256(name . registration_time)
 * that means an attacker can guess it, but we is it worth it to cancel one registration?
 */
function sieve_cancel() {
	$id = intval(sanitize_text_field($_GET['sieve-id']));
	$hash = sanitize_text_field($_GET['sieve-key']);

	// get values needed to verify hash	
	global $wpdb;
	$reg_data = $wpdb->get_row($wpdb->prepare(
		"
		SELECT name, registrationtime
		FROM " . $wpdb->prefix . "sieve_registrations
		WHERE id = %d;
		", $id)
	);
	// verify hash
	$hash_ref = hash('sha256', $reg_data->name . $reg_data->registrationtime);
	if ($hash != $hash_ref) {
		echo "Invalide cancel link";
		return;
	}

	remove_registration($id);

	echo "Sie wurden erfolgreich abgemeldet.";
	
}
	

