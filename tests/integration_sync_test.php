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
 * Unit tests for the local_rocketchat course sync.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use local_rocketchat\integration\sync;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the local_rocketchat course sync.
 *
 * All Rocket.Chat HTTP traffic is simulated through \curl::mock_response().
 * The sync constructor creates a client which performs the login request, so
 * every test mocks at least that one response.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(sync::class)]
final class integration_sync_test extends \advanced_testcase {
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
     * The JSON body of a successful /api/v1/login response.
     *
     * @return string
     */
    private function login_success_response(): string {
        return json_encode([
            'status' => 'success',
            'data' => ['authToken' => 'token123', 'userId' => 'adminid'],
        ]);
    }

    /**
     * A failed client login must be recorded as a sync error on the course.
     *
     * Regression test: run_sync() used "$object = []" and then assigned
     * properties to it, which fatals on PHP 8 with "Attempt to assign
     * property on array" - so an unreachable Rocket.Chat server crashed the
     * whole sync task instead of recording the failure.
     */
    public function test_sync_pending_course_records_auth_failure(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setup_client_config();

        // The client login fails, no further requests are made.
        \curl::mock_response(json_encode(['status' => 'error', 'message' => 'Unauthorized']));

        $course = $this->getDataGenerator()->create_course();

        $sync = new sync();
        $sync->sync_pending_course($course->id);

        $record = $DB->get_record('local_rocketchat_courses', ['course' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(0, $record->pendingsync);
        $this->assertStringContainsString(get_string('auth_failure', 'local_rocketchat'), $record->error);
        $this->assertStringContainsString(get_string('connection_failure', 'local_rocketchat'), $record->error);
    }

    /**
     * A successful sync clears the pending flag and records the sync time.
     */
    public function test_sync_pending_course_passes_without_errors(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setAdminUser();

        \curl::mock_response($this->login_success_response());

        // A course without groups or enrolments needs no further API calls.
        $course = $this->getDataGenerator()->create_course();

        $sync = new sync();
        $sync->sync_pending_course($course->id);

        $record = $DB->get_record('local_rocketchat_courses', ['course' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(0, $record->pendingsync);
        $this->assertGreaterThan(0, $record->lastsync);
        $this->assertEmpty($record->error);
    }

    /**
     * Only courses flagged as pending are processed.
     */
    public function test_sync_pending_courses_processes_pending_only(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setAdminUser();

        \curl::mock_response($this->login_success_response());

        $pendingcourse = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        utilities::set_rocketchat_course_sync($pendingcourse->id, 1);

        $sync = new sync();
        $sync->sync_pending_courses();

        $record = $DB->get_record('local_rocketchat_courses', ['course' => $pendingcourse->id], '*', MUST_EXIST);
        $this->assertEquals(0, $record->pendingsync);
        $this->assertGreaterThan(0, $record->lastsync);
        $this->assertFalse($DB->record_exists('local_rocketchat_courses', ['course' => $othercourse->id]));
    }

    /**
     * The event based sync flag is read from the helper table.
     */
    public function test_is_event_based_sync_on_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->assertFalse(sync::is_event_based_sync_on_course($course->id));

        utilities::set_rocketchat_event_based_sync($course->id, 1);
        $this->assertEquals(1, sync::is_event_based_sync_on_course($course->id));
    }
}
