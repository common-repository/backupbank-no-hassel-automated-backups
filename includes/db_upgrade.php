<?php
global $backupbank_plugin_version, $wpdb;

/**
 * Upgrade database if needed
 */
if (get_option('backupbank_db_version') != $backupbank_db_version) {
    // v1.1
    if (get_option('backupbank_db_version') < 1.1) {
        $table_name = $wpdb->prefix.'backupbank_settings';
    
        $sql = "ALTER TABLE $table_name ADD COLUMN `retention` int DEFAULT 5 NOT NULL AFTER enabled";
        $wpdb->query($sql);
    
        update_option('backupbank_db_version', '1.1');
    }
}
?>