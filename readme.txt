=== BackupBank - No-Hassel WordPress BackUps ===
Contributors: brutebank
Tags: backups, disaster recovery, sftp, ftp, scp
Requires at least: 4.0
Tested up to: 6.1.1
Stable tag: 1.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Requires PHP: 5.4

Use BackupBank to easily and securely backup your website property to your own storage.

== Description ==

= A No-Hassel WordPress BackUp Plugin =
Use BackupBank to securely backup your website property with ease. Leverage your storage and schedule transfers via SFTP, Google Cloud Storage, AWS, and more.

= Installation =

Setting up the WordPress plugin is as easy as a few clicks. 

= Installing the Wordpress Plugin =

1. Login to your Wordpress WP-Admin area as an Administrator.
2. Click on “Plugins -> Add New” in the left hand menu.
3. Search for “BackupBank” in the keyword search. 
4. Click “Install Now” next to the BackupBank plugin. 

= Configuring Daily Backups =

1. Navigate to the “BackupBank” section in the left hand menu. 
2. Toggle the "Backup Enabled" switch to "ON"
3. Add your SFTP / SCP or Google Cloud Storage Bucket name & Authentication JSON ( See the FAQ for information on creating this file )
4. Click the “Update” button.

**Learn more and signup for Pro at [BackupBank.io](https://www.BackupBank.io)**

Be sure to add the wp-cron.php to your local cron configuration. 
`* */15 * * * wget --delete-after http://[ yourdomain.com ]/wp-cron.php > /dev/null 2>&1`

Additional, add the following line to your wp-config.php:

`define('DISABLE_WP_CRON', true);`

== Screenshots ==

1. Configuration of plugin

== Frequently Asked Questions ==
= How much does BackupBank cost? =
BackupBank for WordPress is $25 per year. That’s only $0.07 per day!

= How to I setup a Google Cloud Storage JSON Authentication file? =
Google provides documentation on this feature on the Cloud Website:
https://cloud.google.com/iam/docs/creating-managing-service-account-keys#iam-service-account-keys-create-console

= How do I purchase a Pro account? =
Registering on our website will provide you will the ability to purchase an annual license for your plugin.

Once you have registered you will have access to your Dashboard
Use the "Purchase Your Backup License" input to add in your WordPress URL
Press the purple "Create License" button
Enter your purchasing information on our secure form
Your license will be activated and available for you to copy and paste into our plugin.


== Changelog ==

= 1.3 =
*Release Date - April 25, 2023*
* Premium banner addition

= 1.2 =
*Release Date - February 7, 2023*
* Updated archiving process

= 1.1 =
*Release Date - February 2, 2023*
* Addition of retention policies.
* Detection of whether DISABLE_WP_CRON is enabled.

= 1.0 =
*Release Date - January 26, 2023*
* Initial release. 