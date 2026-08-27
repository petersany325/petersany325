<?php
/**
 * Lightweight plugin loader (hooks/filters) — does not replace core logic.
 */
if (!defined('HDD_PLUGINS_LOADED')) {
    define('HDD_PLUGINS_LOADED', true);

    $GLOBALS['hdd_filters'] = array();
    $GLOBALS['hdd_actions'] = array();

    function add_filter($tag, $callback, $priority = 10) {
        $GLOBALS['hdd_filters'][$tag][$priority][] = $callback;
    }

    function apply_filters($tag, $value) {
        $args = func_get_args();
        array_shift($args); // tag
        if (empty($GLOBALS['hdd_filters'][$tag])) {
            return $value;
        }
        ksort($GLOBALS['hdd_filters'][$tag]);
        foreach ($GLOBALS['hdd_filters'][$tag] as $callbacks) {
            foreach ($callbacks as $cb) {
                $args[0] = $value;
                $value = call_user_func_array($cb, $args);
            }
        }
        return $value;
    }

    function add_action($tag, $callback, $priority = 10) {
        $GLOBALS['hdd_actions'][$tag][$priority][] = $callback;
    }

    function do_action($tag) {
        $args = func_get_args();
        array_shift($args);
        if (empty($GLOBALS['hdd_actions'][$tag])) {
            return;
        }
        ksort($GLOBALS['hdd_actions'][$tag]);
        foreach ($GLOBALS['hdd_actions'][$tag] as $callbacks) {
            foreach ($callbacks as $cb) {
                call_user_func_array($cb, $args);
            }
        }
    }

    // Auto-load enabled plugins (never fatal the whole app)
    $dir = __DIR__;
    foreach (glob($dir . '/*/plugin.php') as $pluginFile) {
        try {
            require_once $pluginFile;
        } catch (Throwable $e) {
            @file_put_contents(dirname(__DIR__) . '/error.log', date('c') . ' plugin ' . basename(dirname($pluginFile)) . ': ' . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}
