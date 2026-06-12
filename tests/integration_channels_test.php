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
 * Unit tests for the local_rocketchat channels integration.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use local_rocketchat\integration\channels;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the local_rocketchat channels integration.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(channels::class)]
final class integration_channels_test extends \advanced_testcase {
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
     * has_private_group() must return the group id on a successful lookup.
     */
    public function test_has_private_group_returns_id(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: groups.info first, login (consumed first) last.
        \curl::mock_response(json_encode([
            'success' => true,
            'group' => ['_id' => 'groupid1', 'name' => 'course-group'],
        ]));
        \curl::mock_response($this->login_success_response());

        $channels = new channels(new client());

        $this->assertSame('groupid1', $channels->has_private_group('course-group'));
    }

    /**
     * has_private_group() must return false instead of crashing when the API
     * returns no parseable response.
     *
     * Regression test: the method used to read ->success off a null response,
     * which aborted the whole sync task on a single transport error.
     */
    public function test_has_private_group_returns_false_on_failed_response(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: failed groups.info first, login (consumed first) last.
        \curl::mock_response('');
        \curl::mock_response($this->login_success_response());

        $channels = new channels(new client());

        $this->assertFalse($channels->has_private_group('course-group'));
    }

    /**
     * has_channel_for_group() must derive the channel name and return its id.
     */
    public function test_has_channel_for_group_returns_id(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        // LIFO: groups.info first, login (consumed first) last.
        \curl::mock_response(json_encode([
            'success' => true,
            'group' => ['_id' => 'groupid1', 'name' => 'course-group'],
        ]));
        \curl::mock_response($this->login_success_response());

        $channels = new channels(new client());

        $this->assertSame('groupid1', $channels->has_channel_for_group($group));
    }

    /**
     * A new private channel is created for each group matching the regex.
     */
    public function test_create_channels_for_course_creates_matching_channel(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        set_config('groupregex', '/^group-/', 'local_rocketchat');

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'group-a']);
        $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'other']);

        // Only group-a matches the regex. LIFO: channels.setType, channels.create,
        // rooms.get (no existing channels), login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response(json_encode(['success' => true, 'channel' => ['_id' => 'newchannelid']]));
        \curl::mock_response(json_encode(['success' => true, 'update' => []]));
        \curl::mock_response($this->login_success_response());

        $channels = new channels(new client());
        $channels->create_channels_for_course((object) ['course' => $course->id]);

        $this->assertEmpty($channels->errors);
    }

    /**
     * An already existing channel is not created again.
     */
    public function test_create_channels_for_course_skips_existing_channel(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        set_config('groupregex', '/^group-/', 'local_rocketchat');

        $course = $this->getDataGenerator()->create_course(['shortname' => 'C1']);
        $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'group-a']);

        // LIFO: rooms.get already listing the channel first, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true, 'update' => [['name' => 'C1-group-a']]]));
        \curl::mock_response($this->login_success_response());

        $channels = new channels(new client());
        $channels->create_channels_for_course((object) ['course' => $course->id]);

        $this->assertEmpty($channels->errors);
    }

    /**
     * A failed channel creation must record an error instead of crashing.
     *
     * Regression test: the error handler used to read ->success and ->error
     * off a null response.
     */
    public function test_create_channels_for_course_records_error_on_failed_response(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        set_config('groupregex', '/^group-/', 'local_rocketchat');

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'group-a']);

        // LIFO: failed channels.create, rooms.get (no existing channels), login (consumed first) last.
        \curl::mock_response('');
        \curl::mock_response(json_encode(['success' => true, 'update' => []]));
        \curl::mock_response($this->login_success_response());

        $channels = new channels(new client());
        $channels->create_channels_for_course((object) ['course' => $course->id]);

        $this->assertCount(1, $channels->errors);
        $this->assertStringContainsString('no response from server', $channels->errors[0]->error);
    }
}
