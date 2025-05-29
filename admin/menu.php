<?php
require_once plugin_dir_path(__FILE__) . "../common/remove_user.php";

add_action( 'admin_menu', 'sieve_admin_menu' );
add_action( 'admin_post_sieve-add-event', 'sieve_add_event_response');
add_action( 'admin_post_sieve-delete-event-by-id', 'sieve_delete_event_by_id_response');
add_action( 'admin_post_sieve-kick', 'sieve_kick_user_from_event');
add_action( 'sieve_notify_about_participants', 'sieve_send_mail_with_participants');

function sieve_admin_menu() {
	add_menu_page( 'Simple Event', 'Simple Event', 'manage_options', 'simple-event/admin.php', 'sieve_admin_page', 'dashicons-calendar');
}

/* 
 * returns a string with the html code to delete the event with the given id
 */
function sieve_delete_event_button($event_id) {
	return '<form action="'.esc_url( admin_url( 'admin-post.php' ) ). '" method="post" class="sieve_delete_event_form">
			<input type="hidden" name="action" value="sieve-delete-event-by-id">'
			. wp_nonce_field( 'sieve-delete_event', 'sieve-delete_event_nonce' ) 
			. '<input type="hidden" name="sieve-event_id" value="'.$event_id.'">
			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-small delete" value="Event absagen"></p>
		</form>';
		
}

/*
 * returns a string with the html code to remove the registration with the given id
 */
function sieve_kick_button($user_id) {
	return '<form action="'.esc_url( admin_url( 'admin-post.php' ) ). '" method="post" class="sieve_kick_form">
			<input type="hidden" name="action" value="sieve-kick">'
			. wp_nonce_field( 'sieve-kick', 'sieve-kick_nonce' ) 
			. '<input type="hidden" name="sieve-registration_id" value="'.$user_id.'">
			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-small delete" value="Person absagen"></p>
		</form>';
		
}

/*
 * returns a string with the html code for a table in the admin area
 * $headers	the column titles
 * $content	array of array with the content of the table
 * $width	width of the columns in percent
 */
function sieve_table($headers, $content, $width=array()) {
	$res = '<table class="widefat fixed" cellspacing="0"><thead><tr>';
	$i = 0;
	foreach ($headers as $head) {
		$res .= '<th id="columnname" class="manage-column column-columnname" ';
		if (sizeof($width) > $i) {
			$res .= 'style="width:' . $width[$i] . '%"';
		}
		$res .= '>' . $head . '</th>';
		$i += 1;
	}	
	$res .= '</tr></thead><tbody>';
	$alternate = true;
	foreach ($content as $row) {
		if ($alternate) {
			$res .= '<tr class="alternate">';
		} else {
			$res .= '<tr>';
		}
		$alternate = !$alternate;
		foreach ($row as $elem) {
			$res .= '<td class="column-columnname">' . $elem . '</td>';
		}
		$res .= "</tr>";
	}
	$res .= "</tbody></table>";
	return $res;
}

/*
 * render the admin page for this plugin
 */
function sieve_admin_page() {
	// get the registration data
	global $wpdb;
	$open_events_list = $wpdb->get_results(
		"
		SELECT id, date, maxspots
		FROM " . $wpdb->prefix . "sieve_events
		WHERE date + INTERVAL 12 HOUR >= now()
		AND opendate <= now()
		ORDER BY date
		"
		);

	$open_events_table_content = array();
	foreach ($open_events_list as $event) {
		// for each event with open registrations create a table with all registrations
		$regs = $wpdb->get_results(
			"SELECT id, name, email
			FROM " . $wpdb->prefix . "sieve_registrations
			WHERE eventid = " . $event->id . "
			ORDER BY registrationtime
			");
		$regsc = array();
		foreach ($regs as $reg) {
			$regsc[] = [sizeof($regsc) + 1, $reg->name, $reg->email, sieve_kick_button($reg->id)];
		}
		$open_events_table_content[] = [$event->date, sizeof($regsc) . "/" . $event->maxspots, sieve_table(["", "Name", "E-Mail", ""], $regsc), sieve_delete_event_button($event->id)];
	}
	$open_events = sieve_table(["Datum", "Anzahl Plätze", ""], $open_events_table_content, [10, 10, 70, 10]);
	
	$locked_events_list = $wpdb->get_results(
		"
		SELECT id, date, opendate, maxspots
		FROM " . $wpdb->prefix . "sieve_events
		WHERE opendate > now()
		ORDER BY date
		"
		);
	
	$locked_events = sieve_table(["Datum", "Anmeldung möglich ab", "Anzahl Plätze", ""], 
		array_map(function($x){return [$x->date, $x->opendate, $x->maxspots, sieve_delete_event_button($x->id)];}, $locked_events_list), [30, 30, 30, 5]);
	
	// adimn page
	?>
	<div class="wrap">
		<h1>Simple Event</h1>
		<h2>Events mit offener Anmeldung</h2>
		<?php echo $open_events;?>
		<h2>Events ohne offene Anmeldung</h2>
		<?php echo $locked_events;?>
		<h2>Event hinzufügen:</h2>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" >
			<input type="hidden" name="action" value="sieve-add-event">
			<?php wp_nonce_field( 'sieve-add_event', 'sieve-add_event_nonce' ); ?>
			<div>
				<label for="sieve-date"> Datum: </label><br>
				<input required id="sieve-date" type="datetime-local" name="sieve-date" value=""/><br>
			</div>
			<div>
				<label for="sieve-max_spots"> Maximale Anzahl Teilnehmer: </label><br>
				<input required id="sieve-max_spots" type="number" name="sieve-max_spots" value="10"/><br>
			</div>
			<div>
				<label for="sieve-login_delta"> Anmeldezeitraum in Tagen: </label><br>
				<input required id="sieve-login_delta" type="number" name="sieve-login_delta" value="7"/><br>
			</div>
			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Hinzufügen"></p>
		</form>
		<script>
	let forms = document.getElementsByClassName("sieve_delete_event_form");
	for (let form of forms) {
		form.addEventListener("submit", function(event) {
				var confirmMessage = "Das Event wirklich endgültig absagen?";
				if (!confirm(confirmMessage)) {
					event.preventDefault();
				}
		})
	}

	forms = document.getElementsByClassName("sieve_kick_form");
	for (let form of forms) {
		form.addEventListener("submit", function(event) {
				var confirmMessage = "Diese Anmeldung wirklich endgültig absagen? Die Person bekommt keine E-Mail, weil ich das noch nicht implementiert habe";
				if (!confirm(confirmMessage)) {
					event.preventDefault();
				}
		})
	}
		</script>
	</div>
	<?php
}

// handle the addition of an event
function sieve_add_event_response() {
	if( isset( $_POST['sieve-add_event_nonce'] ) && wp_verify_nonce( $_POST['sieve-add_event_nonce'], 'sieve-add_event') ) {

		// sanitize the input
		$date = new DateTimeImmutable(sanitize_text_field( $_POST['sieve-date'] ), wp_timezone());
		$max_spots = intval(sanitize_text_field( $_POST['sieve-max_spots'] ));
		$login_delta = intval(sanitize_text_field( $_POST['sieve-login_delta'] ));
		
		// process
		$opendate = $date->sub(new DateInterval("P".$login_delta."D"));

		global $wpdb;
		$wpdb->insert($wpdb->prefix."sieve_events", ["date" =>$date->format("Y-m-d H:i:s"), "opendate" => $opendate->format("Y-m-d H:i:s"), "maxspots" => $max_spots], ["%s", "%s", "%d"]);
		$event_id = $wpdb->insert_id;
		 
		// add cron job to send participant list as mail before event
		wp_schedule_single_event( $date->sub(new DateInterval("PT1H"))->getTimestamp(), "sieve_notify_about_participants", [$event_id] );
		// TODO admin notice
		
		// redirect the user to the appropriate page
		wp_redirect( home_url("/wp-admin/admin.php?page=simple-event%2Fadmin.php") );
		
		exit;
	}			
	else {
		wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
					'response' 	=> 403,
					'back_link' => 'admin.php?page=' . $this->plugin_name,

			) );
	}
}

// handel the cancelation of an event
function sieve_delete_event_by_id_response() {
	if( isset( $_POST['sieve-delete_event_nonce'] ) && wp_verify_nonce( $_POST['sieve-delete_event_nonce'], 'sieve-delete_event') ) {

		// sanitize the input
		$id = intval(sanitize_text_field( $_POST['sieve-event_id'] ));
		
		// process
		global $wpdb;
		// delete registrations
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM " . $wpdb->prefix . "sieve_registrations 
				WHERE eventid = %d;", 
			$id));
		// delete the event
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM " . $wpdb->prefix . "sieve_events
				WHERE id = %d;", 
			$id));
		 
		// unshedule the notification about the participants
		wp_clear_scheduled_hook("sieve_notify_about_participants", [$id]);

		// TODO admin notice

		// redirect the user to the appropriate page
		wp_redirect( home_url("/wp-admin/admin.php?page=simple-event%2Fadmin.php") );
		exit;
	}			
	else {
		wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
					'response' 	=> 403,
					'back_link' => 'admin.php?page=' . $this->plugin_name,

			) );
	}
}

// handle the remolal of a registration
function sieve_kick_user_from_event() {
	if( isset( $_POST['sieve-kick_nonce'] ) && wp_verify_nonce( $_POST['sieve-kick_nonce'], 'sieve-kick') ) {

		// sanitize the input
		$id = intval(sanitize_text_field( $_POST['sieve-registration_id'] ));
		
		// process
		remove_registration($id);
		 
		// TODO admin notice

		// redirect the user to the appropriate page
		wp_redirect( home_url("/wp-admin/admin.php?page=simple-event%2Fadmin.php") );
		exit;
	}			
	else {
		wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
					'response' 	=> 403,
					'back_link' => 'admin.php?page=' . $this->plugin_name,

			) );
	}
}

function sieve_send_mail_with_participants($eventid) {
	// get event data	
	global $wpdb;
	$event = $wpdb->get_row(
		"
		SELECT date, maxspots
		FROM " . $wpdb->prefix . "sieve_events
		WHERE id=". $eventid. ";
		"
		);

	$regs = $wpdb->get_results(
		"SELECT id, name, email
		FROM " . $wpdb->prefix . "sieve_registrations
		WHERE eventid = " . $eventid . "
		ORDER BY registrationtime
		");
	
	// split regs into accepted and wait list
	$registered = array_splice($regs, 0, $event->maxspots);
	// sort the registered alphabetically
	usort($registered, function ($a, $b) {return strcasecmp($a->name, $b->name);});
	// build the lists as text
	$regs_t = "";
	foreach ($registered as $r) {
		$regs_t .= $r->name."\t" . $r->email . "\n";
	}
	$waitlist_t = "";
	$i = 0;
	foreach ($regs as $r) {
		$i += 1;
		$waitlist_t .= $i. ": ". $r->name . "\t" . $r->email . "\n";
	}
	
	$mail_subject = "Anmeldungen für den {date}";
	$mail_content = "Für den {date} um {time} Uhr haben sich angemeldet:\n{registrations}Auf der Warteliste sind:\n{waitlist}";

	// replace {} in mail_content with values
	$replace_pairs = [["{date}", (new DateTimeImmutable($event->date))->format("j.n.y")],
		["{time}", (new DateTimeImmutable($event->date))->format("G:i")],
		["{registrations}", $regs_t],
		["{waitlist}", $waitlist_t]];
	foreach ($replace_pairs as $rp) {
		$mail_subject = str_replace($rp[0], $rp[1], $mail_subject);
		$mail_content = str_replace($rp[0], $rp[1], $mail_content);
	}
	
	// send the mail
	wp_mail( "todo@a.staeves.de", $mail_subject, $mail_content);	
}
