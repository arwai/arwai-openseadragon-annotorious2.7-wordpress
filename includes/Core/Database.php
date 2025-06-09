<?php
namespace ARWAI\Core;
// This class now handles only the database table creation upon activation.


class Database {
    /**
     * Create database tables on plugin activation.
     */
    public static function activate() {
        global $wpdb;

        $table_name_data = $wpdb->prefix . 'annotorious_data';
        $table_name_history = $wpdb->prefix . 'annotorious_history';
        $charset_collate = $wpdb->get_charset_collate();

        $sql_data = "CREATE TABLE $table_name_data (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            annotation_id_from_annotorious VARCHAR(255) NOT NULL,
            attachment_id BIGINT(20) UNSIGNED NOT NULL,
            annotation_data LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY annotorious_id (annotation_id_from_annotorious),
            KEY attachment_id (attachment_id)
        ) $charset_collate;";

        $sql_history = "CREATE TABLE $table_name_history (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            annotation_id_from_annotorious VARCHAR(255) NOT NULL,
            attachment_id BIGINT(20) UNSIGNED NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            annotation_data_snapshot LONGTEXT NOT NULL,
            user_id BIGINT(20) UNSIGNED,
            action_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY annotorious_id_idx (annotation_id_from_annotorious),
            KEY attachment_id_idx (attachment_id),
            KEY user_id_idx (user_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_data );
        dbDelta( $sql_history );
    }
}