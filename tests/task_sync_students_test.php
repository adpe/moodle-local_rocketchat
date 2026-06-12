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
 * Unit tests for the local_rocketchat scheduled sync task.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use local_rocketchat\task\sync_students;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the local_rocketchat scheduled sync task.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(sync_students::class)]
final class task_sync_students_test extends \advanced_testcase {
    /**
     * The task exposes its localised name.
     */
    public function test_get_name(): void {
        $task = new sync_students();

        $this->assertSame(get_string('scheduledtaskname', 'local_rocketchat'), $task->get_name());
    }

    /**
     * The very first run only records the run time without syncing.
     *
     * No response is mocked on purpose: a sync attempt would construct the
     * client and hit the empty mock stack.
     */
    public function test_execute_skips_sync_on_first_run(): void {
        $this->resetAfterTest();

        $task = new sync_students();
        $task->execute();

        $this->assertGreaterThan(0, $task->get_last_run_time());
    }

    /**
     * Subsequent runs sync the pending courses.
     *
     * Regression test: execute() referenced the non-existing class
     * \local_rocketchat\sync instead of \local_rocketchat\integration\sync,
     * so every scheduled run after the first one fataled.
     */
    public function test_execute_syncs_pending_courses(): void {
        global $DB;

        $this->resetAfterTest();

        set_config('host', 'chat.example.com', 'local_rocketchat');
        set_config('port', '', 'local_rocketchat');
        set_config('protocol', 0, 'local_rocketchat');
        set_config('username', 'apiadmin', 'local_rocketchat');
        set_config('password', 'apipassword', 'local_rocketchat');

        // The client login fails, so the sync records the failure on the course.
        \curl::mock_response(json_encode(['status' => 'error', 'message' => 'Unauthorized']));

        $course = $this->getDataGenerator()->create_course();
        utilities::set_rocketchat_course_sync($course->id, 1);

        $task = new sync_students();
        $task->set_last_run_time(time() - DAYSECS);
        $task->execute();

        $record = $DB->get_record('local_rocketchat_courses', ['course' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(0, $record->pendingsync);
        $this->assertStringContainsString(get_string('auth_failure', 'local_rocketchat'), $record->error);
    }
}
