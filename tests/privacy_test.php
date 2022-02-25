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
 * Unit tests for the local_rocketchat implementation of the privacy API.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2021 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use core_privacy\local\request\writer;
use local_rocketchat\privacy\provider;

/**
 * Unit tests for the local_rocketchat implementation of the privacy API.
 *
 * @copyright  2021 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_test extends \core_privacy\tests\provider_testcase {

    /**
     * Ensure that export_user_preferences returns no data if the user has not linked the Rocket.Chat user account.
     */
    public function test_export_user_preferences_no_pref() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test the export_user_preferences given different inputs.
     *
     * @param string $type The name of the user preference to get/set
     * @param string $value The value you are storing
     * @param string $expected The expected value override
     *
     * @throws coding_exception
     * @throws dml_exception
     * @dataProvider user_preference_provider
     */
    public function test_export_user_preferences($type, $value, $expected) {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        set_user_preference($type, $value, $user);
        provider::export_user_preferences($user->id);

        $writer = writer::with_context(\context_system::instance());

        $preferences = $writer->get_user_preferences('local_rocketchat');
        if (!$expected) {
            $expected = $value;
        }

        $this->assertEquals($expected, $preferences->{$type}->value);
    }

    /**
     * Create an array of valid user preferences for a linked Rocket.Chat account.
     *
     * @return array Array of valid user preferences.
     */
    public function user_preference_provider(): array {
        return array(
                array('local_rocketchat_external_user', 'teacher@moodle.a', ''),
                array('local_rocketchat_external_user', 'student@moodle.a', ''),
                array('local_rocketchat_external_token', 'ySbuPDYnA883Kqi7lrz85sbkFAQA3h4iXrGg6qlnXLW', ''),
                array('local_rocketchat_external_token', 'cRrXOdD9F5FlHDVJBFyG6fA6XdICPOOdU637U4MJbrP', ''),
        );
    }
}
