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
 * Unit tests for the local_rocketchat utilities helpers.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

/**
 * Unit tests for the local_rocketchat utilities helpers.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class utilities_test extends \advanced_testcase {
    /**
     * A helper record must be inserted when a course is toggled for the first time.
     *
     * This is a regression test: the insert path used "$$rocketchatcourse = []"
     * (an accidental variable variable), which fataled on PHP 8 with
     * "Cannot use a scalar value as an array" before any record was created.
     *
     * @covers \local_rocketchat\utilities::set_rocketchat_course_sync
     */
    public function test_set_rocketchat_course_sync_creates_record(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->assertFalse($DB->record_exists('local_rocketchat_courses', ['course' => $course->id]));

        utilities::set_rocketchat_course_sync($course->id, 1);

        $record = $DB->get_record('local_rocketchat_courses', ['course' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(1, $record->pendingsync);
    }

    /**
     * An existing helper record must be updated, not duplicated.
     *
     * @covers \local_rocketchat\utilities::set_rocketchat_course_sync
     */
    public function test_set_rocketchat_course_sync_updates_existing_record(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        utilities::set_rocketchat_course_sync($course->id, 1);
        utilities::set_rocketchat_course_sync($course->id, 0);

        $records = $DB->get_records('local_rocketchat_courses', ['course' => $course->id]);
        $this->assertCount(1, $records);
        $this->assertEquals(0, reset($records)->pendingsync);
    }

    /**
     * A helper record must be inserted when a role is toggled for the first time.
     *
     * Regression test for the same variable variable bug as in
     * set_rocketchat_course_sync().
     *
     * @covers \local_rocketchat\utilities::set_rocketchat_role_sync
     */
    public function test_set_rocketchat_role_sync_creates_record(): void {
        global $DB;

        $this->resetAfterTest();

        $roleid = $this->getDataGenerator()->create_role();
        $this->assertFalse($DB->record_exists('local_rocketchat_roles', ['role' => $roleid]));

        utilities::set_rocketchat_role_sync($roleid, 1);

        $record = $DB->get_record('local_rocketchat_roles', ['role' => $roleid], '*', MUST_EXIST);
        $this->assertEquals(1, $record->requiresync);
    }

    /**
     * A helper record must be inserted when event based sync is enabled for the first time.
     *
     * Regression test for the same variable variable bug as in
     * set_rocketchat_course_sync().
     *
     * @covers \local_rocketchat\utilities::set_rocketchat_event_based_sync
     */
    public function test_set_rocketchat_event_based_sync_creates_record(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        utilities::set_rocketchat_event_based_sync($course->id, 1);

        $record = $DB->get_record('local_rocketchat_courses', ['course' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(1, $record->eventbasedsync);
    }

    /**
     * make_request() must return null when the response body is not valid JSON.
     *
     * All callers rely on this to detect transport failures, so the contract
     * is pinned down here.
     *
     * @covers \local_rocketchat\utilities::make_request
     */
    public function test_make_request_returns_null_on_unparseable_response(): void {
        $this->resetAfterTest();

        \curl::mock_response('this is not json');

        $response = utilities::make_request('https://chat.example.com', '/api/v1/me', 'get', [], []);

        $this->assertNull($response);
    }
}
