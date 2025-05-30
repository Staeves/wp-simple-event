<?php

/* remove a registration, either from admin, or through link in confirmation mail
 * $id 	is the id of the registration
 */
function remove_registration($id) {
	global $wpdb;
	// get some data we need to notify the first one on the wait list
	$event_id = $wpdb->get_var($wpdb->prepare(
		"SELECT eventid
		FROM " . $wpdb->prefix . "sieve_registrations
		WHERE id = %d;", $id));
	$event = $wpdb->get_row($wpdb->prepare(
		"SELECT date, maxspots
		FROM ". $wpdb->prefix. "sieve_events
		WHERE id = %d;", $event_id));
	$lucky_reg = $wpdb->get_row($wpdb->prepare(
		"SELECT name, email, registrationtime, id
		FROM ". $wpdb->prefix. "sieve_registrations
		WHERE eventid = %d
		ORDER BY registrationtime;", $event_id), OBJECT, $event->maxspots);
	// delete registration
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM " . $wpdb->prefix . "sieve_registrations 
			WHERE id = %d;", 
		$id));
	
	// if needed notify the lucky reg, that got a spot
	if ($lucky_reg !== null) {
		$mail_content = get_option("sieve_lw_content");
		$mail_subject = get_option("sieve_lw_subject");

		// compute hash
		$hash_ref = hash('sha256', $lucky_reg->name . $lucky_reg->registrationtime);
		$cancel_link = esc_url(admin_url('admin-post.php')) . '?action=sieve_cancel&sieve-id=' . $lucky_reg->id . '&sieve-key=' . $hash_ref;
		
		// replace {} in mail_content with values
		$replace_pairs = [["{name}", $lucky_reg->name], 
			["{date}", (new DateTimeImmutable($event->date))->format("j.n.y")],
			["{time}", (new DateTimeImmutable($event->date))->format("G:i")],
			["{cancel_link}", $cancel_link]];
		foreach ($replace_pairs as $rp) {
			$mail_subject = str_replace($rp[0], $rp[1], $mail_subject);
			$mail_content = str_replace($rp[0], $rp[1], $mail_content);
		}

		// actually send the mail
		sieve_mail( $lucky_reg->email, $mail_subject, $mail_content );
	}

}
