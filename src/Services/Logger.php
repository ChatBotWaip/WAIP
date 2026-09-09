<?php
namespace Waip\Services;

use Waip\Config\Constants;

if (!defined('ABSPATH')) {
    exit;
}

class Logger {

    public static function log($level, $component, $message) {
        global $wpdb;

        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $table = Constants::tableLogs();

        $wpdb->insert(
            $table,
            array(
                'level'      => $level,
                'component'  => $component,
                'message'    => $message,
                'created_at' => current_time('mysql')
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );
    }

    public static function info($component, $message) {
        self::log('INFO', $component, $message);
    }

    public static function error($component, $message) {
        self::log('ERROR', $component, $message);
    }

    public static function debug($component, $message) {
        self::log('DEBUG', $component, $message);
    }
}
