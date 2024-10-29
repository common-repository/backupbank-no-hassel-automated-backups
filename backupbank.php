<?php
/**
* Plugin Name: BackupBank - No-Hassel WordPress BackUps
* Plugin URI: https://www.backupbank.io/wordpress-backup-plugin
* Description: Use BackupBank to easily and securely backup your website property to your own storage.
* Version: 1.3
* Author: BruteBank
* Author URI: https://www.backupbank.io
**/

global $wpdb;

define( 'BACKUPBANK_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BACKUPBANK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
$backupbank_plugin_version   = '1.3';
$backupbank_db_version       = '1.1';

register_activation_hook(__FILE__, 'backupbank_db_install');
register_deactivation_hook(__FILE__, 'backupbank_db_uninstall');

/**
 * Upgrade database if needed
 */
if (get_option('backupbank_db_version') != $backupbank_db_version) {
    require_once( BACKUPBANK_PLUGIN_PATH . 'includes/db_upgrade.php' );
}

/**
 * Add settings link under plugin on plugins page
 */
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'backupbank_add_settings_link' );

function backupbank_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=backupbank-settings">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
      return $links;
}

/**
 * Add menu item to wp-admin
 */
function backupbank_admin_menu() {
    add_menu_page(
        'BackupBank',
        'BackupBank',
        'manage_options',
        'backupbank-settings',
        'backupbank_settings_page',
'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI0LjIuMSwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiCgkgdmlld0JveD0iMCAwIDM2IDM2IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAzNiAzNjsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsOiNGRkZGRkY7fQo8L3N0eWxlPgo8ZyBpZD0iTGF5ZXJfMSI+CjwvZz4KPGcgaWQ9IkxheWVyXzIiPgoJPHBhdGggaWQ9ImJibG9nbyIgY2xhc3M9InN0MCIgZD0iTTIyLjMsOC45bDYuNyw2LjdsLTQuOCw0LjhsLTIsMmwtNC44LDQuOEw2LjksMTYuNWwyLTJsMy45LDMuOWwyLjgtMi44bC0zLjktMy45bDItMmwzLjksMy45CgkJTDIyLjMsOC45IE0yMi4zLDE4LjRsMi44LTIuOGwtMi44LTIuOGwtMi44LDIuOEwyMi4zLDE4LjQgTTE3LjYsMjMuMmwyLjgtMi44bC0wLjctMC43bC0yLjEtMi4xbC0yLjgsMi44TDE3LjYsMjMuMiBNMjIuMyw1bC0yLDIKCQlsLTIuOCwyLjhsLTItMmwtMi0ybC0yLDJsLTIsMmwtMiwybDAsMGwtMC45LDAuOWwtMiwybC0yLDJsMiwybDEwLjcsMTAuN2wyLDJsMi0ybDQuOC00LjhsMi0ybDQuOC00LjhsMi0ybC0yLTJsLTYuNy02LjdMMjIuMyw1CgkJTDIyLjMsNXoiLz4KPC9nPgo8L3N2Zz4K'
    );
}
add_action( 'admin_menu', 'backupbank_admin_menu' );

/**
 * Create settings page
 */
function backupbank_settings_page() {
    if (!current_user_can( 'manage_options')) {
        wp_die( __('Access denied', 'backupbank') );
    }

    $backupbank_options = get_option( 'backupbank-settings' );
    require_once( BACKUPBANK_PLUGIN_PATH . 'admin/settings.php' );
}

/**
 * Register settings in the database
 */
function backupbank_register_settings() {
    register_setting('backupbank_settings_group', 'backupbank-settings');
}
add_action('admin_init', 'backupbank_register_settings');

/**
 * Database install
 */
function backupbank_db_install() {
    global $wpdb;
    global $backupbank_db_version;

    $table_name = $wpdb->prefix.'backupbank_settings';

    $charset_collate = $wpdb->get_charset_collate();

    $wpdb->query('DROP TABLE IF EXISTS '.$table_name);
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        enabled tinyint(1) DEFAULT 0 NOT NULL,
        schedule enum('daily', 'weekly') default 'daily' not null,
        license_key varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $wpdb->query($sql);
    
    $sql = "ALTER TABLE $table_name ADD COLUMN backup_enabled boolean default 0 not null";
    $wpdb->query($sql);
    $sql = "ALTER TABLE $table_name ADD COLUMN backup_type enum('','sftp_scp') default 'sftp_scp' not null";
    $wpdb->query($sql);
    $sql = "ALTER TABLE $table_name ADD COLUMN backup_path varchar(200)";
    $wpdb->query($sql);
    $sql = "ALTER TABLE $table_name ADD COLUMN website_name varchar(200)";
    $wpdb->query($sql);
    
    // SFTP / SCP
    $sql = "ALTER TABLE $table_name ADD COLUMN sftp_scp_host varchar(200)";
    $wpdb->query($sql);
    $sql = "ALTER TABLE $table_name ADD COLUMN sftp_scp_user varchar(200)";
    $wpdb->query($sql);
    $sql = "ALTER TABLE $table_name ADD COLUMN sftp_scp_pass varchar(400)";
    $wpdb->query($sql);
    $sql = "ALTER TABLE $table_name ADD COLUMN sftp_scp_folder varchar(200)";
    $wpdb->query($sql);

    // Retention
    $sql = "ALTER TABLE $table_name ADD COLUMN `retention` int DEFAULT 5 NOT NULL AFTER enabled";
    $wpdb->query($sql);

    add_option('backupbank_db_version', $backupbank_db_version);
}

/**
 * Database uninstall
 */
function backupbank_db_uninstall() {
    global $wpdb;

    $table_name = $wpdb->prefix.'backupbank_settings';
    $wpdb->query('DROP TABLE IF EXISTS '.$table_name);
}

/**
* Backup library
*/
require_once( BACKUPBANK_PLUGIN_PATH . 'includes/bb_backup.php' );

/**
 * Add backup cron
 */
add_action( 'bl_cron_hook', 'bb_backup_cron' );
if ( ! wp_next_scheduled( 'bl_cron_hook' ) ) {
    $table_name = $wpdb->prefix.'backupbank_settings';
    $sql = 'SELECT * FROM '.$table_name;
    $results = $wpdb->get_results($sql);
    $enabled = false;
    $schedule = null;
    foreach ($results as $result) {
        $enabled = $result->enabled;
        $schedule = $result->schedule;
    }
    if ($enabled) {
        date_default_timezone_set(wp_timezone_string());

        $time = strtotime('tomorrow 12:00am');
        switch ($schedule) {
            case 'weekly':
                wp_schedule_event($time, 'weekly', 'bl_cron_hook');
                break;
            default:
            case 'daily':
                wp_schedule_event($time, 'daily', 'bl_cron_hook');
                break;
        }
    }
}
function bb_backup_cron() {
    $bb_backup = new BackupBank_Backup();
    $bb_backup->runBackup();
}
?>