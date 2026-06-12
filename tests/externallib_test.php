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
 * Unit tests for the local_rocketchat external functions.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use core_external\external_api;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/rocketchat/externallib.php');

/**
 * Unit tests for the local_rocketchat external functions.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\local_rocketchat_external::class)]
final class externallib_test extends \core_external\tests\externallib_testcase {
    /**
     * Configure the plugin so the client can be constructed.
     */
    private function setup_client_config(): void {
        set_config('host', 'chat.example.com', 'local_rocketchat');
        set_config('port', '', 'local_rocketchat');
        set_config('protocol', 0, 'local_rocketchat');
        set_config('username', 'apiadmin', 'local_rocketchat');
        set_config('password', 'apipassword', 'local_rocketchat');
    }

    /**
     * Toggling the course sync stores the flag and returns a confirmation.
     */
    public function test_set_rocketchat_course_sync(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $result = \local_rocketchat_external::set_rocketchat_course_sync($course->id, 1);
        $result = external_api::clean_returnvalue(\local_rocketchat_external::set_rocketchat_course_sync_returns(), $result);

        $this->assertIsString($result);

        $record = $DB->get_record('local_rocketchat_courses', ['course' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(1, $record->pendingsync);
    }

    /**
     * The sync functions require the manage capability.
     */
    public function test_set_rocketchat_course_sync_requires_capability(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $course = $this->getDataGenerator()->create_course();

        $this->expectException(\required_capability_exception::class);
        \local_rocketchat_external::set_rocketchat_course_sync($course->id, 1);
    }

    /**
     * Toggling the role sync stores the flag and returns a confirmation.
     *
     * Regression test: the function used to return an external_value object
     * instead of the string, which fails the return value validation when
     * called through the web service layer.
     */
    public function test_set_rocketchat_role_sync(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $roleid = $this->getDataGenerator()->create_role();

        $result = \local_rocketchat_external::set_rocketchat_role_sync($roleid, 1);
        $result = external_api::clean_returnvalue(\local_rocketchat_external::set_rocketchat_role_sync_returns(), $result);

        $this->assertIsString($result);

        $record = $DB->get_record('local_rocketchat_roles', ['role' => $roleid], '*', MUST_EXIST);
        $this->assertEquals(1, $record->requiresync);
    }

    /**
     * Toggling the event based sync stores the flag and returns a confirmation.
     *
     * Regression test: see {@see self::test_set_rocketchat_role_sync()}.
     */
    public function test_set_rocketchat_event_based_sync(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $result = \local_rocketchat_external::set_rocketchat_event_based_sync($course->id, 1);
        $result = external_api::clean_returnvalue(
            \local_rocketchat_external::set_rocketchat_event_based_sync_returns(),
            $result
        );

        $this->assertIsString($result);

        $record = $DB->get_record('local_rocketchat_courses', ['course' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(1, $record->eventbasedsync);
    }

    /**
     * The manual trigger runs a sync for the course and reports back.
     *
     * Regression test: see {@see self::test_set_rocketchat_role_sync()}.
     */
    public function test_manually_trigger_sync(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setAdminUser();

        // The client login fails, so the sync records the failure on the course.
        \curl::mock_response(json_encode(['status' => 'error', 'message' => 'Unauthorized']));

        $course = $this->getDataGenerator()->create_course();

        $result = \local_rocketchat_external::manually_trigger_sync($course->id);
        $result = external_api::clean_returnvalue(\local_rocketchat_external::manually_trigger_sync_returns(), $result);

        $this->assertIsString($result);

        $record = $DB->get_record('local_rocketchat_courses', ['course' => $course->id], '*', MUST_EXIST);
        $this->assertStringContainsString(get_string('auth_failure', 'local_rocketchat'), $record->error);
    }
}
