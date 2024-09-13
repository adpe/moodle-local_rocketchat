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
 * External Rocket.Chat API
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * Class for API method calls.
 */
class local_rocketchat_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function set_rocketchat_course_sync_parameters() {
        return new external_function_parameters([
                'courseid' => new external_value(PARAM_INT, get_string('coursesyncparam_courseid', 'local_rocketchat')),
                'pendingsync' => new external_value(PARAM_BOOL, get_string('coursesyncparam_pendingsync', 'local_rocketchat'),
                        VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @param $courseid
     * @param $pendingsync
     * @return string
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function set_rocketchat_course_sync($courseid, $pendingsync) {
        $params = self::validate_parameters(self::set_rocketchat_course_sync_parameters(),
                ['courseid' => $courseid, 'pendingsync' => $pendingsync]
        );

        \local_rocketchat\utilities::set_rocketchat_course_sync($courseid, $pendingsync);

        return get_string('coursesyncresult', 'local_rocketchat', $params);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @throws coding_exception
     */
    public static function set_rocketchat_course_sync_returns() {
        return new external_value(PARAM_TEXT, get_string('sync_returns', 'local_rocketchat'));
    }

    /**
     * Can this function be called directly from ajax?
     *
     * @return boolean
     * @since Moodle 2.9
     */
    public static function set_rocketchat_course_sync_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function set_rocketchat_role_sync_parameters() {
        return new external_function_parameters([
                'roleid' => new external_value(PARAM_INT, get_string('coursesyncparam_roleid', 'local_rocketchat')),
                'requiresync' => new external_value(PARAM_BOOL, get_string('coursesyncparam_requiresync', 'local_rocketchat'),
                        VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @param $roleid
     * @param $requiresync
     * @return external_description
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function set_rocketchat_role_sync($roleid, $requiresync) {
        $params = self::validate_parameters(self::set_rocketchat_role_sync_parameters(),
                ['roleid' => $roleid, 'requiresync' => $requiresync]
        );

        \local_rocketchat\utilities::set_rocketchat_role_sync($roleid, $requiresync);

        return new external_value(PARAM_TEXT, get_string('coursesyncresult', 'local_rocketchat', $params));
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @throws coding_exception
     */
    public static function set_rocketchat_role_sync_returns() {
        return new external_value(PARAM_TEXT, get_string('sync_returns', 'local_rocketchat'));
    }

    /**
     * Can this function be called directly from ajax?
     *
     * @return boolean
     * @since Moodle 2.9
     */
    public static function set_rocketchat_role_sync_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function set_rocketchat_event_based_sync_parameters() {
        return new external_function_parameters([
                'courseid' => new external_value(PARAM_INT, get_string('coursesyncparam_courseid', 'local_rocketchat')),
                'eventbasedsync' => new external_value(PARAM_BOOL, get_string('coursesyncparam_enentbasedsync', 'local_rocketchat'),
                        VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @param $courseid
     * @param $eventbasedsync
     * @return external_description
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function set_rocketchat_event_based_sync($courseid, $eventbasedsync) {
        $params = self::validate_parameters(self::set_rocketchat_event_based_sync_parameters(),
                ['courseid' => $courseid, 'eventbasedsync' => $eventbasedsync]
        );

        \local_rocketchat\utilities::set_rocketchat_event_based_sync($courseid, $eventbasedsync);

        return new external_value(PARAM_TEXT, get_string('courseeventbasedsyncresult', 'local_rocketchat', $params));
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @throws coding_exception
     */
    public static function set_rocketchat_event_based_sync_returns() {
        return new external_value(PARAM_TEXT, get_string('sync_returns', 'local_rocketchat'));
    }

    /**
     * Can this function be called directly from ajax?
     *
     * @return boolean
     * @since Moodle 2.9
     */
    public static function set_rocketchat_event_based_sync_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @throws coding_exception
     */
    public static function manually_trigger_sync_parameters() {
        return new external_function_parameters([
                'courseid' => new external_value(PARAM_INT, get_string('coursesyncparam_courseid', 'local_rocketchat')),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @param $courseid
     * @return external_description
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function manually_trigger_sync($courseid) {
        $params = self::validate_parameters(self::manually_trigger_sync_parameters(),
                ['courseid' => $courseid]
        );

        $sync = new local_rocketchat\sync;
        $sync->sync_pending_course($courseid);

        return new external_value(PARAM_TEXT, get_string('coursetriggeryncresult', 'local_rocketchat', $params));
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @throws coding_exception
     */
    public static function manually_trigger_sync_returns() {
        return new external_value(PARAM_TEXT, get_string('sync_returns', 'local_rocketchat'));
    }

    /**
     * Can this function be called directly from ajax?
     *
     * @return boolean
     * @since Moodle 2.9
     */
    public static function manually_trigger_sync_is_allowed_from_ajax() {
        return true;
    }
}
