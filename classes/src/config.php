<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Config functions.
 *
 * @package    logstore_trax
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_trax\src;

defined('MOODLE_INTERNAL') || die();

use logstore_trax\src\services\settings;

/**
 * Config functions.
 *
 * @package    logstore_trax
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {

    /**
     * No LRS.
     */
    const TARGET_NO = 0;

    /**
     * Main LRS.
     */
    const TARGET_MAIN = 1;

    /**
     * Secondary LRS.
     */
    const TARGET_SECONDARY = 2;

    /**
     * Synchronous mode.
     */
    const SYNC = 0;

    /**
     * Asynchronous mode.
     */
    const ASYNC = 1;

    /**
     * Actors identification: anonymous mode.
     */
    const ID_ANONYMOUS = 0;

    /**
     * Actors identification: account with username.
     */
    const ID_ACCOUNT_USERNAME = 1;

    /**
     * Actors identification: mbox.
     */
    const ID_MBOX = 2;

    /**
     * Get the target options.
     *
     * @param bool $remove_empty_lrs
     * @return array
     */
    public static function targets(bool $remove_empty_lrs = false) {
        $targets = [
            self::TARGET_NO => get_string('no_lrs', 'logstore_trax'),
        ];
        if (!$remove_empty_lrs || get_config('logstore_trax', 'lrs_endpoint')) {
            $targets[self::TARGET_MAIN] = get_string('main_lrs', 'logstore_trax');
        }
        if (!$remove_empty_lrs || get_config('logstore_trax', 'lrs2_endpoint')) {
            $targets[self::TARGET_SECONDARY] = get_string('secondary_lrs', 'logstore_trax');
        }
        return $targets;
    }

    /**
     * Get the default target.
     *
     * @return integer
     */
    public static function default_target() {
        return get_config('logstore_trax', 'courses_default_target');
    }

    /**
     * Get a course target: in fact the current target of the course.
     *
     * @param int $course_id
     * @return integer
     */
    public static function course_target($course_id) {
        $setting = (new settings)->get_last_setting('course', $course_id);
        return $setting === false
            ? self::default_target()
            : $setting->target;
    }

    /**
     * Get a course target at a given time.
     *
     * @param int $course_id
     * @param string $time
     * @return integer
     */
    public static function course_target_at($course_id, $time) {
        $setting = (new settings)->get_setting_at('course', $course_id, $time);
        return $setting === false
            ? self::default_target()
            : $setting->target;
    }

    /**
     * Get a course target.
     *
     * @param int $course_id
     * @param int $target
     * @return void
     */
    public static function set_course_target($course_id, $target) {
        (new settings)->add_setting('course', $course_id, $target);
    }

    /**
     * Get the sync modes.
     *
     * @return array
     */
    public static function sync_modes() {
        return [
            self::SYNC => get_string('sync', 'logstore_trax'),
            self::ASYNC => get_string('async', 'logstore_trax'),
        ];
    }

    /**
     * Is sync mode?
     *
     * @return bool
     */
    public static function sync() {
        return get_config('logstore_trax', 'sync_mode') == self::SYNC;
    }

    /**
     * Get the identification modes.
     *
     * @return array
     */
    public static function actors_identification_modes() {
        return [
            self::ID_ANONYMOUS => get_string('anonymous', 'logstore_trax'),
            self::ID_ACCOUNT_USERNAME => get_string('account_username', 'logstore_trax'),
            self::ID_MBOX => get_string('mbox', 'logstore_trax'),
        ];
    }

    /**
     * Is identification anonymous?
     *
     * @return bool
     */
    public static function anonymous() {
        return get_config('logstore_trax', 'actors_identification') == self::ID_ANONYMOUS;
    }

    /**
     * Is identification based on mbox?
     *
     * @return bool
     */
    public static function mbox() {
        return get_config('logstore_trax', 'actors_identification') == self::ID_MBOX;
    }

    /**
     * Get the loggable core events.
     *
     * @return array
     */
    public static function loggable_core_events() {
        return [
            'management' => get_string('management', 'logstore_trax'),
            'authentication' => get_string('authentication', 'logstore_trax'),
            'navigation' => get_string('navigation', 'logstore_trax'),
            'completion' => get_string('completion', 'logstore_trax'),
            'grading' => get_string('grading', 'logstore_trax'),
        ];
    }

    /**
     * Get the core events selected by default.
     *
     * @return array
     */
    public static function default_core_events() {
        $default = array_map(function ($key, $comp) {
            return [$key => 1];
        }, array_keys(self::loggable_core_events()), self::loggable_core_events());
        return call_user_func_array("array_merge", array_values($default));
    }

    /**
     * Get the core selected events.
     *
     * @param stdClass $config Config
     * @return array
     */
    public static function selected_core_events(\stdClass $config) {
        $families = explode(',', $config->core_events);
        $families = array_intersect_key(events::core(), array_flip($families));
        if (empty($families)) return [];
        return call_user_func_array("array_merge", array_values($families));
    }

    /**
     * Get the loggable Moodle components.
     *
     * @return array
     */
    public static function loggable_moodle_components() {
        $components = events::moodle_components();
        $components = array_map(function ($key, $comp) {
            $parts = explode('_', $key);
            return [$key => get_string('modulename', $parts[1])];
        }, array_keys($components), $components);
        $components = call_user_func_array("array_merge", array_values($components));
        return $components;
    }

    /**
     * Get the Moodle components selected by default.
     *
     * @return array
     */
    public static function default_moodle_components() {
        $default = array_map(function ($key, $comp) {
            return [$key => 1];
        }, array_keys(self::loggable_moodle_components()), self::loggable_moodle_components());
        return call_user_func_array("array_merge", array_values($default));

    }

    /**
     * Get the selected Moodle components.
     *
     * @param stdClass $config Config
     * @return array
     */
    public static function selected_moodle_events(\stdClass $config) {
        $components = explode(',', $config->moodle_components);
        $components = array_intersect_key(events::moodle_components(), array_flip($components));
        if (empty($components)) return [];
        return call_user_func_array("array_merge", array_values($components));

    }

    /**
     * Get the loggable additional components.
     *
     * @return array
     */
    public static function loggable_additional_components() {
        $components = events::additional_components();
        $components = array_map(function($key, $comp) {
            return [$key => get_string($key, 'logstore_trax')];
        }, array_keys($components), $components);
        $components = call_user_func_array("array_merge", array_values($components));
        $components['other'] = get_string('other_components', 'logstore_trax');
        return $components;
    }

    /**
     * Get the additional components selected by default.
     *
     * @return array
     */
    public static function default_additional_components() {
        $default = array_map(function ($key, $comp) {
            return [$key => 1];
        }, array_keys(self::loggable_additional_components()), self::loggable_additional_components());
        return call_user_func_array("array_merge", array_values($default));
    }

    /**
     * Get the additional selected events.
     *
     * @param stdClass $config Config
     * @return array
     */
    public static function selected_additional_events(\stdClass $config) {
        $components = explode(',', $config->additional_components);
        $key = array_search('other', $components);
        unset($components[$key]);
        $components = array_intersect_key(events::additional_components(), array_flip($components));
        if (empty($components)) return [];
        return call_user_func_array("array_merge", array_values($components));
    }

    /**
     * Return true when the "Other components" checkbox is selected.
     *
     * @param stdClass $config Config
     * @return bool
     */
    public static function other_components_selected(\stdClass $config) {
        $additional = $config->additional_components;
        return isset($additional['other']) && $additional['other'];
    }

    /**
     * Get the loggable scheduled components.
     *
     * @return array
     */
    public static function loggable_scheduled_statements() {
        $components = events::scheduled_statements();
        $components = array_map(function($key, $comp) {
            return [$key => get_string($key, 'logstore_trax')];
        }, array_keys($components), $components);
        return call_user_func_array("array_merge", array_values($components));
    }

    /**
     * Get the scheduled components selected by default.
     *
     * @return array
     */
    public static function default_scheduled_statements() {
        $default = array_map(function ($key, $comp) {
            return [$key => 1];
        }, array_keys(self::loggable_scheduled_statements()), self::loggable_scheduled_statements());
        return call_user_func_array("array_merge", array_values($default));
    }

    /**
     * Get the selected scheduled components.
     *
     * @param stdClass $config Config
     * @return array
     */
    public static function selected_scheduled_events(\stdClass $config) {
        $components = explode(',', $config->scheduled_statements);
        $components = array_intersect_key(events::scheduled_statements(), array_flip($components));
        if (empty($components)) return [];
        return call_user_func_array("array_merge", array_values($components));
    }

    /**
     * Get the selected scheduled statements.
     *
     * @param string $statement_name
     * @return array
     */
    public static function is_scheduled(string $statement_name) {
        $scheduled_statements = get_config('logstore_trax', 'scheduled_statements');
        $scheduled_statements = explode(',', $scheduled_statements);
        if (!$scheduled_statements) return false;
        return in_array($statement_name, $scheduled_statements);
    }

    /**
     * Get all the selected events.
     *
     * @param stdClass $config Config
     * @return array
     */
    public static function selected_events(\stdClass $config) {
        return array_merge(
            self::selected_core_events($config),
            self::selected_moodle_events($config),
            self::selected_additional_events($config),
            self::selected_scheduled_events($config)
        );
    }

    /**
     * Get all the known events.
     *
     * @return array
     */
    public static function known_events() {
        $coreEvents = call_user_func_array("array_merge", array_values(events::core()));
        $moodleEvents = call_user_func_array("array_merge", array_values(events::moodle_components()));
        $additionalEvents = call_user_func_array("array_merge", array_values(events::additional_components()));
        $scheduledEvents = call_user_func_array("array_merge", array_values(events::scheduled_statements()));
        return array_merge($coreEvents, $moodleEvents, $additionalEvents, $scheduledEvents);
    }

    /**
     * Are other events selected?
     *
     * @param stdClass $config Config
     * @return bool
     */
    public static function other_events(\stdClass $config) {
        $components = explode(',', $config->additional_components);
        return in_array('other', $components);
    }
}
