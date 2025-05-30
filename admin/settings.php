<?php

add_action( 'admin_menu', 'sieve_add_options_page');
add_action( 'admin_init', 'sieve_settings_init' );

function sieve_add_options_page() {
	add_options_page(
		"Simple Event Options",
		"Simple Event",
		'manage_options',
		'sieve_options',
		'sieve_settings_page'
	);
}

function sieve_settings_page() {
	?><div class="wrap"><h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
	<?php
	settings_fields( 'sieve_settings' );
	do_settings_sections('sieve_options');
	submit_button( 'Save Settings' );
	?><form></div><?php
}

function sieve_settings_init() {
	// register all settings
	register_setting( 'sieve_settings', 'sieve_open_delta', 
		['default'=> 7, 'sanitize_callback' => 'intval'] );
	register_setting( 'sieve_settings', 'sieve_num_spots', 
		['default'=> 10, 'sanitize_callback' => 'intval'] );
	register_setting( 'sieve_settings', 'sieve_sender_name', 
		['default'=> "", 'sanitize_callback' => 'sanitize_text_field'] );
	register_setting( 'sieve_settings', 'sieve_reply_to', 
		['default'=> "", 'sanitize_callback' => 'sanitize_email'] );
	register_setting( 'sieve_settings', 'sieve_confirm_spot_subject', 
		['default'=> "Anmeldung zum Event am {date}", 'sanitize_callback' => 'sanitize_text_field'] );
	register_setting( 'sieve_settings', 'sieve_confirm_spot_content', 
		['default'=> "Hallo {name},\nSie haben sich erfolgreich zum Event am {date} um {time} Uhr registriert und einen Fixplatz erhalten.\nSollten sie nicht Teilnehmen können, melden sie sich bitte über diesen Link ab:\n{cancel_link}", 'sanitize_callback' => 'sanitize_textarea_field'] );
	register_setting( 'sieve_settings', 'sieve_confirm_waitlist_subject', 
		['default'=> "Wartelistenplatz zum Event am {date}", 'sanitize_callback' => 'sanitize_text_field'] );
	register_setting( 'sieve_settings', 'sieve_confirm_waitlist_content', 
		['default'=> "Hallo {name},\nSie haben sich zum Event am {date} um {time} Uhr angemeldet und befinden sich auf der Warteliste. Sollten sie einen Fixplatz erhalten, bekommen sie eine weitere Mail.\n Sollten sie nicht teilnehmen können, melden sie sich bitte über folgenden link ab\n{cancel_link}", 'sanitize_callback' => 'sanitize_textarea_field'] );
	register_setting( 'sieve_settings', 'sieve_lw_subject', 
		['default'=> "Fixplatz zum Event am {date} erhalten", 'sanitize_callback' => 'sanitize_text_field'] );
	register_setting( 'sieve_settings', 'sieve_lw_content', 
		['default'=> "Hallo {name},\nSie haben für das Event am {date} um {time} Uhr einen Fixplatz erhalten.\n Sollten sie nicht teilnehmen können, melden sie sich bitte über folgenden link ab\n{cancel_link}", 'sanitize_callback' => 'sanitize_textarea_field'] );
	register_setting( 'sieve_settings', 'sieve_canceled_subject', 
		['default'=> "Das Event am {date} wurde abgesagt", 'sanitize_callback' => 'sanitize_text_field'] );
	register_setting( 'sieve_settings', 'sieve_canceled_content', 
		['default'=> "Hallo {name},\nleider müssen wir das Event am {date} um {time} Uhr absagen.", 'sanitize_callback' => 'sanitize_textarea_field'] );
	register_setting( 'sieve_settings', 'sieve_results_email', 
		['default'=> "", 'sanitize_callback' => 'sanitize_email'] );
	register_setting( 'sieve_settings', 'sieve_results_delta', 
		['default'=> 1, 'sanitize_callback' => 'intval'] );
	register_setting( 'sieve_settings', 'sieve_results_subject', 
		['default'=> "Die Ergebnisse für das Event am {date}", 'sanitize_callback' => 'sanitize_text_field'] );
	register_setting( 'sieve_settings', 'sieve_results_content', 
		['default'=> "Hallo,\nFür das Event am {date} um {time} Uhr haben sich angemeldet:\n{registrations}\nAuf der Warteliste sind:\n{waitlist}", 'sanitize_callback' => 'sanitize_textarea_field'] );

	// register the setting sections and fields in those sections
	// general
	add_settings_section('sieve_settings_general', 'Allgemein',
	       	'sieve_handle_setting_section', "sieve_options");
	add_settings_field('sieve_open_delta',
		'Standart für Tage vorher die anmeldung öffnen',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_general",
		['label_for'	=> 'sieve_open_delta',
		'type'		=> 'number']
	);
	add_settings_field('sieve_num_spots',
		'Standart für Anzahl Plätze',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_general",
		['label_for'	=> 'sieve_num_spots',
		'type'		=> 'number']
	);
	// general EMail settings
	add_settings_section('sieve_settings_mail_general', 'E-Mail',
	       	'sieve_handle_setting_section', "sieve_options");
	add_settings_field('sieve_sender_name',
		'Anzeigename im Absenderfeld',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_mail_general",
		['label_for'	=> 'sieve_sender_name',
		'type'		=> 'text',
		'info' 		=> 'lehr für WP standart']
	);
	add_settings_field('sieve_reply_to',
		'Reply to',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_mail_general",
		['label_for'	=> 'sieve_reply_to',
		'type'		=> 'email',
		'info' 		=> "Hat nur einen Effekt, wenn der Anzeigename auch gesetzt ist; lehr für WP standart"]
	);
	// Confirmation EMail
	add_settings_section('sieve_settings_mail_confirm', 'Bestätigungs E-Mails',
	       	'sieve_handle_confirm_section', "sieve_options");
	add_settings_field('sieve_confirm_spot_subject',
		'Betreff bei Fixplatz',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_mail_confirm",
		['label_for'	=> 'sieve_confirm_spot_subject',
		'type'		=> 'text']
	);
	add_settings_field('sieve_confirm_spot_content',
		'E-Mail Inhalt bei Fixplatz',
		"sieve_settings_textarea_callback",
		"sieve_options",
		"sieve_settings_mail_confirm",
		['label_for'	=> 'sieve_confirm_spot_content']
	);
	add_settings_field('sieve_confirm_waitlist_subject',
		'Betreff bei Wartelistenplatz',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_mail_confirm",
		['label_for'	=> 'sieve_confirm_waitlist_subject',
		'type'		=> 'text']
	);
	add_settings_field('sieve_confirm_waitlist_content',
		'E-Mail Inhalt bei Wartelistenplatz',
		"sieve_settings_textarea_callback",
		"sieve_options",
		"sieve_settings_mail_confirm",
		['label_for'	=> 'sieve_confirm_waitlist_content']
	);
	// Leave waitlist EMail
	add_settings_section('sieve_settings_mail_lw', 'E-Mail beim Verlassen der Warteliste',
	       	'sieve_handle_confirm_section', "sieve_options");
	add_settings_field('sieve_lw_subject',
		'Betreff beim Verlassen der Warteliste',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_mail_lw",
		['label_for'	=> 'sieve_lw_subject',
		'type'		=> 'text']
	);
	add_settings_field('sieve_lw_content',
		'E-Mail Inhalt beim Verlassen der Warteliste',
		"sieve_settings_textarea_callback",
		"sieve_options",
		"sieve_settings_mail_lw",
		['label_for'	=> 'sieve_lw_content']
	);
	// Canceled E-mail
	add_settings_section('sieve_settings_mail_canceled', 'E-Mails wenn ein Event abgesagt wird',
	       	'sieve_handle_canceled_section', "sieve_options");
	add_settings_field('sieve_canceled_subject',
		'Betreff der Absage E-Mails',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_mail_canceled",
		['label_for'	=> 'sieve_canceled_subject',
		'type'		=> 'text']
	);
	add_settings_field('sieve_canceled_content',
		'E-Mail Inhalt bei Absage eines Events',
		"sieve_settings_textarea_callback",
		"sieve_options",
		"sieve_settings_mail_canceled",
		['label_for'	=> 'sieve_canceled_content']
	);
	
	// result notification
	add_settings_section('sieve_settings_mail_results', 'E-Mail mit den Anmeldungen',
	       	'sieve_handle_results_section', "sieve_options");
	add_settings_field('sieve_results_email',
		'Email an die die Ergebnisliste gesendet werden soll',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_mail_results",
		['label_for'	=> 'sieve_results_email',
		'type'		=> 'email',
		'info' 		=> 'lehr lassen um keine E-Mail mit den Anmelungen zu versenden']
	);
	add_settings_field('sieve_results_delta',
		'Stunden vor dem Event die Mail mit den Ergebnissen versenden',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_mail_results",
		['label_for'	=> 'sieve_results_delta',
		'type'		=> 'number']
	);
	add_settings_field('sieve_results_subject',
		'Betreff der E-Mail mit den Anmeldungen',
		"sieve_settings_field_callback",
		"sieve_options",
		"sieve_settings_mail_results",
		['label_for'	=> 'sieve_results_subject',
		'type'		=> 'text']
	);
	add_settings_field('sieve_results_content',
		'E-Mail Inhalt für die E-Mail mit den Anmeldungen',
		"sieve_settings_textarea_callback",
		"sieve_options",
		"sieve_settings_mail_results",
		['label_for'	=> 'sieve_results_content']
	);

}

function sieve_handle_setting_section($arg) {
	//do_settings_fields('sieve_options', $arg['id']);
}

function sieve_handle_confirm_section() {
	?>
	<p>Folgende codes werden automatisch ersetzt:<p>
<table>
<tr><td>{name}</td><td>Der Vor und Nachname der Person, die sich angemeldet hat</td></tr>
<tr><td>{date}</td><td>Das Datum der Veranstaltung</td></tr>
<tr><td>{time}</td><td>Die Uhrzeit der Veranstaltung</td></tr>
<tr><td>{cancel_link}</td><td>Der Link um die Anmeldung ab zu sagen</td></tr>
</table>
	<?php
}

function sieve_handle_canceled_section() {
	?>
	<p>Folgende codes werden automatisch ersetzt:<p>
<table>
<tr><td>{name}</td><td>Der Vor und Nachname der Person, die sich angemeldet hat</td></tr>
<tr><td>{date}</td><td>Das Datum der Veranstaltung</td></tr>
<tr><td>{time}</td><td>Die Uhrzeit der Veranstaltung</td></tr>
</table>
	<?php
}

function sieve_handle_results_section() {
	?>
	<p>Folgende codes werden im E-Mail betreff und Inhalt automatisch ersetzt:<p>
<table>
<tr><td>{date}</td><td>Das Datum der Veranstaltung</td></tr>
<tr><td>{time}</td><td>Die Uhrzeit der Veranstaltung</td></tr>
<tr><td>{registrations}</td><td>Eine alphabetische liste aller Fixplatzanmeldungen</td></tr>
<tr><td>{waitlist}</td><td>Eine Liste aller Personen auf der Warteliste in der Reihnfolge ihrer Anmeldung</td></tr>
</table>
	<?php
}

function sieve_settings_field_callback($arg) {
	?><input type="<?php echo $arg["type"]; ?>" 
		class="regular-text"
		id="<?php echo $arg["label_for"]; ?>" 
		name="<?php echo $arg["label_for"]; ?>" 
		value="<?php echo get_option($arg["label_for"]); ?>"><?php
	if (isset($arg["info"])) {
		echo "<p>" . $arg["info"] . "</p>";
	}
}

function sieve_settings_textarea_callback($arg) {
	?><textarea class="large-text code" rows="10" 
		id="<?php echo $arg["label_for"]; ?>" 
		name="<?php echo $arg["label_for"]; ?>" 
		><?php echo get_option($arg["label_for"]); ?></textarea><?php
	if (isset($arg["info"])) {
		echo "<p>" . $arg["info"] . "</p>";
	}
}
