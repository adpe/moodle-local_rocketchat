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
 * Unit tests for the local_rocketchat library functions.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use PHPUnit\Framework\Attributes\CoversFunction;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/rocketchat/lib.php');

/**
 * Unit tests for the local_rocketchat library functions.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('local_rocketchat_extend_navigation_user_settings')]
final class lib_test extends \advanced_testcase {
    /**
     * Build a minimal user settings navigation tree.
     *
     * @return \navigation_node the user account container node
     */
    private function build_useraccount_node(): \navigation_node {
        $root = new \navigation_node(['text' => 'Preferences']);

        return $root->add('User account', null, \navigation_node::TYPE_CONTAINER, null, 'useraccount');
    }

    /**
     * Call the navigation hook for a user.
     *
     * @param \navigation_node $useraccount the user account container node
     * @param \stdClass $user the user the settings page belongs to
     */
    private function call_hook(\navigation_node $useraccount, \stdClass $user): void {
        global $DB;

        $course = $DB->get_record('course', ['id' => SITEID], '*', MUST_EXIST);

        local_rocketchat_extend_navigation_user_settings(
            $useraccount,
            $user,
            \context_user::instance($user->id),
            $course,
            \context_course::instance(SITEID)
        );
    }

    /**
     * The link is added for the own settings page when external connections are allowed.
     */
    public function test_navigation_extended_for_own_user(): void {
        $this->resetAfterTest();
        set_config('allowexternalconnection', 1, 'local_rocketchat');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $useraccount = $this->build_useraccount_node();
        $this->call_hook($useraccount, $user);

        $this->assertCount(1, $useraccount->children);
        foreach ($useraccount->children as $node) {
            $this->assertSame(get_string('linkaccount', 'local_rocketchat'), $node->text);
        }
    }

    /**
     * No link is added when external connections are disallowed.
     */
    public function test_navigation_not_extended_when_disallowed(): void {
        $this->resetAfterTest();
        set_config('allowexternalconnection', 0, 'local_rocketchat');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $useraccount = $this->build_useraccount_node();
        $this->call_hook($useraccount, $user);

        $this->assertCount(0, $useraccount->children);
    }

    /**
     * No link is added on another user's settings page.
     */
    public function test_navigation_not_extended_for_other_user(): void {
        $this->resetAfterTest();
        set_config('allowexternalconnection', 1, 'local_rocketchat');

        $this->setAdminUser();
        $otheruser = $this->getDataGenerator()->create_user();

        $useraccount = $this->build_useraccount_node();
        $this->call_hook($useraccount, $otheruser);

        $this->assertCount(0, $useraccount->children);
    }
}
