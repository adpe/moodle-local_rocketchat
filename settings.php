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
 * Site administration settings page for setup Rocket.Chat in Moodle
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Add a category to the site administration menu.
$ADMIN->add('root', new admin_category('local_rocketchat', get_string('pluginname', 'local_rocketchat')));

// Add Rocket.Chat API and sync settings.
$settingspage = new admin_settingpage('local_rocketchat_settings', get_string('settings'), 'moodle/site:config');

$settingspage->add(new admin_setting_heading('local_rocketchat_settings_api', get_string('heading_api', 'local_rocketchat'), ''));
$settingspage->add(new admin_setting_configtext(
    'local_rocketchat/host',
    get_string('hostname', 'local_rocketchat'),
    get_string('hostname_desc', 'local_rocketchat'),
    'localhost'
));
$settingspage->add(new admin_setting_configtext(
    'local_rocketchat/port',
    get_string('port', 'local_rocketchat'),
    get_string('port_desc', 'local_rocketchat'),
    '3000'
));
$settingspage->add(new admin_setting_configselect(
    'local_rocketchat/protocol',
    get_string('protocol', 'local_rocketchat'),
    get_string('protocol_desc', 'local_rocketchat'),
    '0',
    [0 => 'https', 1 => 'http']
));
$settingspage->add(new admin_setting_configtext(
    'local_rocketchat/username',
    get_string('username', 'local_rocketchat'),
    get_string('username_desc', 'local_rocketchat'),
    ''
));
$settingspage->add(new admin_setting_configpasswordunmask(
    'local_rocketchat/password',
    get_string('password', 'local_rocketchat'),
    get_string('password_desc', 'local_rocketchat'),
    ''
));
$settingspage->add(new admin_setting_configcheckbox(
    'local_rocketchat/allowexternalconnection',
    get_string('allowexternalconnection', 'local_rocketchat'),
    get_string('allowexternalconnection_desc', 'local_rocketchat'),
    1
));

$settingspage->add(new admin_setting_heading('local_rocketchat_settings_sync', get_string('heading_sync', 'local_rocketchat'), ''));
$settingspage->add(new admin_setting_configtextarea(
    'local_rocketchat/groupregex',
    get_string('groupregex', 'local_rocketchat'),
    get_string('groupregex_desc', 'local_rocketchat'),
    '/all/'
));

$ADMIN->add('local_rocketchat', $settingspage);

$ADMIN->add('local_rocketchat', new admin_externalpage(
    'local_rocketchat_course_integration',
    get_string('heading_course', 'local_rocketchat'),
    '/local/rocketchat/settings/course_integration.php',
    'local/rocketchat:view'
));
$ADMIN->add('local_rocketchat', new admin_externalpage(
    'local_rocketchat_role_integration',
    get_string('heading_role', 'local_rocketchat'),
    '/local/rocketchat/settings/role_integration.php',
    'local/rocketchat:view'
));
