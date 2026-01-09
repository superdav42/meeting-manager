
=== Meeting Manager ===

Contributors:      WordPress Telex
Tags:              block, meeting, jitsi, notifications, scheduling
Tested up to:      6.8
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive block for managing single or recurring virtual meetings with Jitsi integration, email notifications, and push notifications.

== Description ==

Meeting Manager is a powerful WordPress block that enables you to set up and manage virtual meetings directly on your website. Perfect for recurring team meetings, webinars, office hours, or any scheduled video conferences.

**Key Features:**

* **Flexible Scheduling**: Configure single meetings or complex recurring schedules (e.g., "2nd Friday of every month")
* **Jitsi Integration**: Embedded video conferencing with customizable Jitsi domains and room URLs
* **Smart Notifications**: Automated email reminders before meetings start
* **Push Notifications**: Browser push notifications when meetings begin
* **Countdown Timer**: Display countdown to next meeting on frontend
* **Email Subscriptions**: Allow visitors to subscribe for meeting notifications
* **Timezone Support**: Clear timezone display for all meeting times
* **User-Friendly Interface**: Clean, intuitive interface for both admins and visitors

**How It Works:**

1. **Admin Configuration**: Set up your meeting schedule, times, Jitsi settings, and notification preferences
2. **Visitor Registration**: Site visitors see a countdown and can subscribe for notifications
3. **Automated Reminders**: System sends email notifications before meetings start
4. **Meeting Launch**: When countdown reaches zero, Jitsi meeting embeds automatically
5. **Push Notifications**: Subscribers receive browser notifications when meetings begin

**Use Cases:**

* Weekly team meetings
* Monthly webinars
* Office hours for consultations
* Regular community gatherings
* Scheduled training sessions
* Recurring support sessions

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/meeting-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Add the Meeting Manager block to any post or page
4. Configure your meeting settings in the block inspector
5. Publish your page and visitors will see the meeting countdown and subscription form

== Frequently Asked Questions ==

= What is Jitsi? =

Jitsi is a free, open-source video conferencing platform. This block integrates Jitsi Meet to provide secure video meetings directly on your website.

= Can I use my own Jitsi server? =

Yes! You can configure a custom Jitsi domain in the block settings. By default, it uses meet.jit.si.

= How do recurring meetings work? =

You can set up complex recurring patterns like "Every Monday", "2nd Friday of every month", or "Last Tuesday of every quarter". The block automatically calculates the next occurrence.

= Are email addresses stored securely? =

Yes, email addresses are stored in your WordPress database and follow WordPress security best practices.

= Do I need additional plugins for email notifications? =

No, the block uses WordPress's built-in wp_mail() function. However, we recommend configuring SMTP for reliable email delivery.

= How do push notifications work? =

The block uses the Web Push API, a browser standard. Visitors must grant permission for notifications in their browser.

= Can I customize the meeting room URL? =

Yes, you can set a custom room URL in the block settings to create branded or consistent meeting rooms.

== Screenshots ==

1. Block editor interface showing meeting configuration options
2. Frontend countdown timer and subscription form
3. Active meeting with embedded Jitsi interface
4. Recurring schedule configuration options

== Changelog ==

= 1.0.0 =
* Initial public release
* Single and recurring meeting support
* Jitsi Meet integration (free tier)
* JaaS 8x8 integration (paid, no time limits)
* RS256 JWT authentication for JaaS
* Email notifications with configurable reminder times
* Push notifications
* Countdown timer
* Email subscription management
* Full-width responsive iframe for live meetings
* Automatic user name detection for logged-in WordPress users

== Privacy & Data ==

This plugin stores email addresses of users who subscribe to meeting notifications. Email addresses are stored in your WordPress database and are used solely for sending meeting notifications. Users can unsubscribe at any time.

The plugin integrates with Jitsi Meet for video conferencing. When users join a meeting, they connect directly to the configured Jitsi server. Please review Jitsi's privacy policy if using their hosted service.

== Support ==

For support, feature requests, or bug reports, please contact WordPress Telex.
