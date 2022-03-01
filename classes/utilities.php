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

class utilities {

    private $client;

    public function __construct($client) {
        $this->client = $client;
    }

    /**
     * @param $courseid
     * @param int $pendingsync
     * @throws \dml_exception
     */
    public static function set_rocketchat_course_sync($courseid, $pendingsync = 0) {
        global $DB;

        $rocketchatcourse = $DB->get_record('local_rocketchat_courses', array('course' => $courseid));

        if ($rocketchatcourse) {
            $rocketchatcourse->pendingsync = $pendingsync;
            $DB->update_record('local_rocketchat_courses', $rocketchatcourse);
        } else {
            $$rocketchatcourse = array();
            $rocketchatcourse['course'] = $courseid;
            $rocketchatcourse['pendingsync'] = $pendingsync;
            $DB->insert_record('local_rocketchat_courses', $rocketchatcourse);
        }
    }

    /**
     * @param $roleid
     * @param int $requiresync
     * @throws \dml_exception
     */
    public static function set_rocketchat_role_sync($roleid, $requiresync=0) {
        global $DB;
        $rocketchatrole = $DB->get_record('local_rocketchat_roles', array('role' => $roleid));

        if ($rocketchatrole) {
            $rocketchatrole->requiresync = $requiresync;
            $DB->update_record('local_rocketchat_roles', $rocketchatrole);
        } else {
            $$rocketchatrole = array();
            $rocketchatrole['role'] = $roleid;
            $rocketchatrole['requiresync'] = $requiresync;
            $DB->insert_record('local_rocketchat_roles', $rocketchatrole);
        }
    }

    /**
     * @param $courseid
     * @param int $eventbasedsync
     * @throws \dml_exception
     */
    public static function set_rocketchat_event_based_sync($courseid, $eventbasedsync = 0) {
        global $DB;

        $rocketchatcourse = $DB->get_record('local_rocketchat_courses', array('course' => $courseid));

        if ($rocketchatcourse) {
            $rocketchatcourse->eventbasedsync = $eventbasedsync;
            $DB->update_record('local_rocketchat_courses', $rocketchatcourse);
        } else {
            $$rocketchatcourse = array();
            $rocketchatcourse['course'] = $courseid;
            $rocketchatcourse['eventbasedsync'] = $eventbasedsync;
            $DB->insert_record('local_rocketchat_courses', $rocketchatcourse);
        }
    }

    /**
     * @return array
     * @throws \dml_exception
     */
    public static function get_courses() {
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
     * @return array
     * @throws \dml_exception
     */
    public static function get_roles() {
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
     * @param $obj
     * @param $prop
     * @return mixed
     * @throws \ReflectionException
     */
    public static function access_protected($obj, $prop) {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * @param $url
     * @param $api
     * @param string $method
     * @param null $data
     * @param null $header
     * @return bool|mixed
     * @throws \dml_exception
     */
    public static function make_request($url, $api, $method, $data = null, $header = null) {
        $request = new \curl();

        if (!empty($header)) {
            $request->setHeader($header);
        }

        $url = $url . $api;

        if (isset($data)) {
            $data = json_encode($data);
        };

        if ($method == 'post') {
            if (isset($data)) {
                $response = $request->post($url, $data);
            } else {
                $response = $request->post($url);
            }
        } else if ($method == 'get') {
            if (isset($data)) {
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
     */
    public static function is_external_connection_allowed(): bool {
        if (get_config('local_rocketchat', 'allowexternalconnection')) {
            return true;
        }

        return false;
    }

    /**
     * @param $data
     * @return array
     * @throws \dml_exception
     */
    public static function get_user_and_group_by_event_data($data): array {
        global $DB;

        $user = $DB->get_record('user', [
                'id' => $data['relateduserid']
        ]);

        $group = $DB->get_record('groups', [
                'id' => $data['objectid']
        ]);

        return [$user, $group];
    }
}
