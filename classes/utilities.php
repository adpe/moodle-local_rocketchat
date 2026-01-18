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
 * Helper functions for Rocket.Chat integration
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use core\event\group_member_added;
use core\event\group_member_removed;
use curl;
use dml_exception;
use ReflectionClass;
use ReflectionException;

/**
 * Class with general helper methods.
 */
class utilities {
    /**
     * Constructor.
     */
    public function __construct() {
    }

    /**
     * Update helper entry with pending sync status.
     *
     * @param int $courseid
     * @param int $pendingsync
     * @throws dml_exception
     */
    public static function set_rocketchat_course_sync(int $courseid, int $pendingsync = 0): void {
        global $DB;

        $rocketchatcourse = $DB->get_record('local_rocketchat_courses', ['course' => $courseid]);
        if ($rocketchatcourse) {
            $rocketchatcourse->pendingsync = $pendingsync;
            $DB->update_record('local_rocketchat_courses', $rocketchatcourse);
        } else {
            $$rocketchatcourse = [];
            $rocketchatcourse['course'] = $courseid;
            $rocketchatcourse['pendingsync'] = $pendingsync;
            $DB->insert_record('local_rocketchat_courses', $rocketchatcourse);
        }
    }

    /**
     * Update helper entry with role sync status.
     *
     * @param int $roleid
     * @param int $requiresync
     * @throws dml_exception
     */
    public static function set_rocketchat_role_sync(int $roleid, int $requiresync = 0): void {
        global $DB;

        $rocketchatrole = $DB->get_record('local_rocketchat_roles', ['role' => $roleid]);
        if ($rocketchatrole) {
            $rocketchatrole->requiresync = $requiresync;
            $DB->update_record('local_rocketchat_roles', $rocketchatrole);
        } else {
            $$rocketchatrole = [];
            $rocketchatrole['role'] = $roleid;
            $rocketchatrole['requiresync'] = $requiresync;
            $DB->insert_record('local_rocketchat_roles', $rocketchatrole);
        }
    }

    /**
     * Update helper entry with event based sync status.
     *
     * @param int $courseid
     * @param int $eventbasedsync
     * @throws dml_exception
     */
    public static function set_rocketchat_event_based_sync(int $courseid, int $eventbasedsync = 0): void {
        global $DB;

        $rocketchatcourse = $DB->get_record('local_rocketchat_courses', ['course' => $courseid]);

        if ($rocketchatcourse) {
            $rocketchatcourse->eventbasedsync = $eventbasedsync;
            $DB->update_record('local_rocketchat_courses', $rocketchatcourse);
        } else {
            $$rocketchatcourse = [];
            $rocketchatcourse['course'] = $courseid;
            $rocketchatcourse['eventbasedsync'] = $eventbasedsync;
            $DB->insert_record('local_rocketchat_courses', $rocketchatcourse);
        }
    }

    /**
     * Get all courses to process.
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_courses(): array {
        global $DB;

        $query = '
            SELECT
                c.id courseid,
                CASE WHEN lrc.id IS NULL THEN 0 ELSE lrc.eventbasedsync END eventbasedsync,
                CASE WHEN lrc.id IS NULL THEN 0 ELSE lrc.pendingsync END pendingsync,
                lrc.lastsync,
                lrc.error
            FROM
                {course} c

            LEFT JOIN {local_rocketchat_courses} lrc ON
                lrc.course = c.id
        ';

        $courses = $DB->get_records_sql($query);

        return $courses;
    }

    /**
     * Get all roles to process.
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_roles(): array {
        global $DB;

        $query = '
            SELECT
                r.id roleid,
                CASE WHEN lrr.id IS NULL THEN 0 ELSE lrr.requiresync END requiresync
            FROM
                {role} r

            LEFT JOIN {local_rocketchat_roles} lrr ON
                lrr.role = r.id;
        ';

        $roles = $DB->get_records_sql($query);

        return $roles;
    }

    /**
     * Map data from event to be accessible.
     *
     * @param string|object $obj
     * @param string $prop
     * @return mixed
     * @throws ReflectionException
     */
    public static function access_protected(string|object $obj, string $prop): mixed {
        $reflection = new ReflectionClass($obj);
        $prop = $reflection->getProperty($prop);
        $prop->setAccessible(true);
        return $prop->getValue($obj);
    }

    /**
     * Run API request.
     *
     * @param string $url
     * @param string $api
     * @param string $method
     * @param array $data
     * @param string|array $header
     * @return bool|mixed
     */
    public static function make_request(string $url, string $api, string $method, array $data, string|array $header): mixed {
        $request = new curl();

        if (!empty($header)) {
            $request->setHeader($header);
        }

        $url = $url . $api;

        if (!empty($data)) {
            $data = json_encode($data);
        }

        if ($method == 'post') {
            if (!empty($data)) {
                $response = $request->post($url, $data);
            } else {
                $response = $request->post($url);
            }
        } else if ($method == 'get') {
            if (!empty($data)) {
                $response = $request->get($url, $data);
            } else {
                $response = $request->get($url);
            }
        } else {
            $response = $request->delete($url);
        }

        $response = json_decode($response);

        return $response;
    }

    /**
     * Checks if users can link their Rocket.Chat account.
     *
     * @return bool
     * @throws dml_exception
     */
    public static function is_external_connection_allowed(): bool {
        if (get_config('local_rocketchat', 'allowexternalconnection')) {
            return true;
        }

        return false;
    }

    /**
     * Gets all channels and status from user data.
     *
     * @param array $data
     * @return array
     * @throws dml_exception
     */
    public static function get_user_and_group_by_event_data(array $data): array {
        global $DB;

        $user = $DB->get_record('user', [
                'id' => $data['relateduserid'],
        ]);

        $group = $DB->get_record('groups', [
                'id' => $data['objectid'],
        ]);

        return [$user, $group];
    }
}
