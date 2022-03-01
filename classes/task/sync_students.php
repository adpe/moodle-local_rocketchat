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
 * Adhoc task handling sync from Moodle to Rocket.Chat
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat\task;

/**
 * Class handling sync from Moodle to Rocket.Chat.
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_students extends \core\task\scheduled_task {

    private $currenttime = 0;

    public function get_name() {
        return get_string('scheduledtaskname', 'local_rocketchat');
    }

    /**
     * Run the sync task.
     */
    public function execute() {
        $this->currenttime = time();

        if ($this->get_last_run_time() > 0) {
            $sync = new \local_rocketchat\sync();
            $sync->sync_pending_courses();
        }

        $this->set_last_run_time($this->currenttime);
    }
}
