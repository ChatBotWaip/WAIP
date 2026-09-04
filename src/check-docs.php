<?php
require_once __DIR__ . '/../../../../wp-load.php';
global $wpdb;
$table = \Waip\Config\Constants::DB_DOCUMENTS;
$wpdb->query("ALTER TABLE $table ADD COLUMN raw_text longtext DEFAULT NULL");
echo "Column added successfully";
