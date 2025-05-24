<?php

add_shortcode("simple_event", "sieve_shortcode");
add_action( 'admin_post_nopriv_sieve-register', 'sieve_register_for_event');

/* define the shortcode for displaying the event registrations in the frontend */
function sieve_shortcode( $atts = [], $content=null ) {
	global $wpdb;
	$open_events = $wpdb->get_results(
		"
		SELECT id, date, maxspots
		FROM " . $wpdb->prefix . "sieve_events
		WHERE date >= now()
		AND opendate <= now()
		ORDER BY date
		"
		);
	$locked_event = $wpdb->get_row(
		"
		SELECT date, opendate
		FROM " . $wpdb->prefix . "sieve_events
		WHERE opendate > now()
		ORDER BY date
		"
		);
	$res = '<div>';
	// if there are open registrations, display those events
	if (sizeof($open_events) > 0) {
		$res .= '<div><h2>Offene Anmeldungen:</h2>';
		foreach ($open_events as $event) {
			// get the number of registrations for that event
			$num_reg = $wpdb->get_var(
				"SELECT COUNT(*)
				FROM " . $wpdb->prefix . "sieve_registrations
				WHERE eventid = " . $event->id .";");
			// biold the entry for the event with the login option
			$res .= '<h3>' . date_format(date_create($event->date), "j.n.y G:i") . '</h3>';
			if ($num_reg < $event->maxspots) {
				$res .= "<p>Noch " . ($event->maxspots - $num_reg) . " freie Plätze</p>";
			} else {
				$res .= "<p>Alle " . ($event->maxspots) . " Plätze sind schon belegt. Länge der Warteliste: " . ($num_reg - $event->maxspots) . "<p>";
			}
			$res .= sieve_booking_form($event->id);
		}
	} else {
		$res .= '<h2>Aktuell sind keine Anmeldungen offen</h2>';
	}
	// show the next event with locked registration
	if (is_null($locked_event)) {
	} else {
		$res .= '<p> Die Anmeldung für den '. date_format(date_create($locked_event->date), "j.n.y G:i") .' startet am ' . date_format(date_create($locked_event->opendate), "j.n.y G:i") . '</p>';
	}

	$res .= '</div>';
	return $res;
}

/*
 * return a string with the html code for registering to the event with the id
 */
function sieve_booking_form($eventid) {
	$res = '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post">
		<input type="hidden" name="action" value="sieve-register"/>'
		. wp_nonce_field("sieve-register", "sieve-register_nonce") 
		. ' <input type="hidden" name="event_id" value="' . $eventid . '/>"
		<div>
			<label for="sieve-first_name">Vorname:</label><br>
			<input required id="sieve-first_name" type="text" name="sieve-first_name" value="" placeholder="Vorname"/><br>
			<label for="sieve-name">Nachname:</label><br>
			<input required id="sieve-name" type="text" name="sieve-name" value="" placeholder="Nachname"/>
		</div>
		<div>
			<label for="sieve-mail">E-Mail:</label><br>
			<input required id="sieve-mail" type="email" name="sieve-mail" value="" placeholder="E-Mail"/>
		</div>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Anmelden"></p>
		</form>';
	return $res;
}

/* 
 * handle the event registration form submit
 */
function sieve_register_for_event() {
	if( isset( $_POST['sieve-register_nonce'] ) && wp_verify_nonce( $_POST['sieve-register_nonce'], 'sieve-register') ) {
		// sanitize inputs
		$name = sanitize_text_field($_POST['sieve-first_name']) . " " . sanitize_text_field($_POST['sieve-name']);
		$mail = sanitize_email($_POST['sieve-mail']);
		$date = new DateTimeImmutable();	// current time
		$eventid = intval(sanitize_text_field($_POST['event_id']));

		// insert into db
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO " . $wpdb->prefix . "sieve_registrations
				(name, email, registrationtime, eventid)
				VALUES (%s, %s, %s, %d);",
				$name,
				$mail,
				$date->format("Y-m-d H:i:s"),
				$eventid));
		
		echo "Anmeldung erfolgreich. Sie erhlaten in kürze eine Bestätigungs-Mail.";	
		
		// send confirmation E-Mail
		$mail_content_spot = "Hallo {name},\n"
			. "Du hast dich erforglreich zum Event am {date} um {time} Uhr angemeldet.";
		$mail_content_wait = "Hallo {name},\n"
			. "Du bist aktuell auf der Warteliste für das event am {date} um {time} Uhr.";
		$mail_subject = "Anmeldung zum event am {date}";

		// pick the right e-mail text
		$num_registrations = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*)
			FROM " . $wpdb->prefix . "sieve_registrations
			WHERE eventid = %d
			AND registrationtime < %s;", 
			$eventid, 
			$date->format("Y-m-d H:i:s")));
		
		$event_date = $wpdb->get_row($wpdb->prepare(
			"
			SELECT date, maxspots
			FROM " . $wpdb->prefix . "sieve_events
			WHERE id = %d;", $eventid));
		
		if ($num_registrations < $event_date->maxspots) {
			$mail_content = $mail_content_spot;
		} else {
			$mail_content = $mail_content_wait;
		}
		
		// replace {} in mail_content with values
		$replace_pairs = [["{name}", $name], 
			["{date}", (new DateTimeImmutable($event_date->date))->format("j.n.y")],
			["{time}", (new DateTimeImmutable($event_date->date))->format("G:i")]];
		foreach ($replace_pairs as $rp) {
			$mail_subject = str_replace($rp[0], $rp[1], $mail_subject);
			$mail_content = str_replace($rp[0], $rp[1], $mail_content);
		}

		// actually send the mail
		wp_mail( $mail, $mail_subject, $mail_content );
	}
	else {
		wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
					'response' 	=> 403,
					'back_link' => 'admin.php?page=' . $this->plugin_name,

			) );
	}
}
