=== Plugin Name ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=DVAJJLS8548QY
Tags: comments, spam
Requires at least: 4.7
Requires PHP: 5.6
Tested up to: 4.8.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is a secure front-end extension to the [WP CRM System](https://wordpress.org/plugins/wp-crm-system/) plugin

== Description ==

This extension builds on the [WP CRM System](https://wordpress.org/plugins/wp-crm-system/) plugin and enables a secured front-end using the [WP Customer Area](https://wordpress.org/plugins/customer-area/) plugin.  The WP Customer Area plugin creates transient private pages which get destroyed after each session, therefore providing a secure front-end for customers' data.  Projects created in the WP CRM dashboard are viewed within these transient private pages.  In addition, WP CUstomer Area private files can be assoicated to any given project, ensuring added security for these documents.  Projects can be arranged using the project type taxonomy as customer orders, audit trails, quality reviews and many more as per your requirements.

This extensions links WP CRM custom post types such as that,

*   1 Organisation may have several contacts
*   1 organisatoin may have several projects.
*   1 project may have several taks, and by default 1 is always associated with each project.
*   Customer comments/dialogues are enabled and handled at the level of the task.
*   Each organisation have 1 secured private page automatically created within the Customer Area plugin
*   All contacts from an organisation have access rights to the its private page.
*   Customers view their projects within the transient private page.



== Installation ==

1. install the [WP CRM System](https://wordpress.org/plugins/wp-crm-system/) plugin.
2. Install the [WP Customer Area](https://wordpress.org/plugins/customer-area/) plugin.
1. Unpack the this plugin archive into the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Do I need to create user accounts for my customer contacts? =
No, the plugin creates accounts automatically for your once you start to create contacts in your WP CRM dashboard.

== Screenshots ==


== Changelog ==

= 1.0 =
* stable version with scrollabe tabs for project display

== Upgrade Notice ==
