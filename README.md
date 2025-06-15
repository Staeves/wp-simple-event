# wp-simple-event
## About
A simple and free WordPress plugin that allows people to register to an event. Most plugins that allow peaople to register for an event, have a lot of features, but there apears to be none, that is free and simple.

Currently only German

Simply choose a date how many participants you want and how many days in advance to open the registration. Simple Event then handels the rest, including a waitlist and E-Mail notifications.

- Add as many events as you want
- registration opens n days before the event takes place.
- Choose the number of participants for each event.
- When more participants register than an event has spots, there is a waitlist.
- Participants get confirmation E-Mails when registering and when leaving the waitlist.
- You can see and manage the registrations in the admin area.
- You can get an E-Mail with all regitrations before the event.
  
## Instalation
Currently you have to install Simple Event manually. 

It is also highly recomended to use a plugin that allows WordPress to send E-Mails using SMTP, such as [SMTP Mailer](https://wordpress.org/plugins/smtp-mailer/) (available through the plugin store).

Use the Short Code `[simple_event]` to add Simple Event to your frontend.

Please go through the settings and test that everything works.

## Updating
As this plugin is not available in the WP-Plugin Store automatic upgrades are not possible.
You could uninstall the old version and install the new one. This however will remove all events and registrations without notice.

A better approach, is to connect to the wordpress backend using FTP and replacing the files of the plugin directly. This will update the plugin, WordPress might however still show the old version number in the plugin area. Just check for a new feature, to ensure, that the update worked. 

If you know of any better way, please let me know.
