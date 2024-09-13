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
 * Strings for component 'local_rocketchat', language 'en', branch 'MOODLE_35_STABLE'
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowexternalconnection'] = 'External connection';
$string['allowexternalconnection_desc'] = 'If enabled, users can <a href="/local/rocketchat/linkaccount.php" target="_blank">connect</a> to the Rocket.Chat instance and store the retrieved token in Moodle. With this setting, your users must link only once the Rocket.Chat account.';
$string['auth_failure'] = 'Rocket.Chat integration - authentication failure';
$string['button_sync'] = 'Manual sync';
$string['channel_creation'] = 'Rocket.Chat integration - channel creation';
$string['connection_failure'] = 'Failed to establish a client connection with the Rocket.Chat server';
$string['course_desc'] = 'Manage integration between Moodle and Rocket.Chat. Specify which users and courses require Rocket.Chat integration and manually trigger sync.';
$string['courseeventbasedsyncresult'] = 'The courseid is - {$a->courseid}  / The eventbasedsync is {$a->eventbasedsync}';
$string['courseinfo_1'] = 'Courses with event based sync active will be affected by certain events - group_member_added, group_member_removed and user_enrolment_updated. Ensure that you have done an initial sync before turning it on.';
$string['courseinfo_2'] = 'Courses pending sync will be sync\'d to rocketchat on the next cron execution in the background. Pending sync will be removed after syncing.';
$string['courseinfo_3'] = 'Hovering on the three dots will display any errors.';
$string['courseinfo_4'] = 'Manual sync execution will be run immediately';
$string['coursesyncparam_courseid'] = 'The course id';
$string['coursesyncparam_enentbasedsync'] = 'Highlights if a course has event based sync active';
$string['coursesyncparam_pendingsync'] = 'Highlights if a course is pending sync';
$string['coursesyncparam_requiresync'] = 'Highlights if a course is requiring sync';
$string['coursesyncparam_roleid'] = 'The role id';
$string['coursesyncresult'] = 'The courseid is - {$a->courseid}  / The pendingsync is {$a->pendingsync}';
$string['coursetable_column_1'] = 'Course';
$string['coursetable_column_2'] = 'Event based sync';
$string['coursetable_column_3'] = 'Pending sync';
$string['coursetable_column_4'] = 'Last sync date';
$string['coursetriggeryncresult'] = 'Success: The courseid is - {$a->courseid}';
$string['groupregex'] = 'Group Regex filters';
$string['groupregex_desc'] = 'Used for matching which groups should be sync to Rocket.Chat:<ul><li>/all/</li><li>/coach group [a-z][0-9]/</li><li>/example project group [0-9][0-9]/</li></ul>';
$string['heading_api'] = 'API Connection';
$string['heading_course'] = 'Course integration';
$string['heading_role'] = 'Role integration';
$string['heading_sync'] = 'Sync Configuration';
$string['hostname'] = 'Hostname';
$string['hostname_desc'] = 'Please specify the fully qualified domain name to Rocket.Chat instance.';
$string['linkaccount'] = 'Rocket.Chat preferences';
$string['linkaccount_connected'] = 'Rocket.Chat account is connected';
$string['linkaccount_disconnected'] = 'Rocket.Chat account is disconnected';
$string['linkaccount_unexpectedmessage'] = '<br/><br/>Rocket.Chat returned the error: "{$a}"';
$string['linkaccount_unexpectedresult'] = 'There was a problem connecting to your Rocket.Chat account. Please check the credentials and try again.';
$string['password'] = 'Password';
$string['password_desc'] = 'Specified password for the given user.';
$string['pluginname'] = 'Rocket.Chat';
$string['port'] = 'Port';
$string['port_desc'] = 'Please specify the port if it\'s not the default (3000).';
$string['privacy:metadata:local_rocketchat'] = 'User and API credentials sent to external Rocket.Chat host.';
$string['privacy:metadata:local_rocketchat_api:password'] = 'The password associated with the Rocket.Chat API account.';
$string['privacy:metadata:local_rocketchat_api:username'] = 'The username associated with the Rocket.Chat API account.';
$string['privacy:metadata:local_rocketchat_user:password'] = 'The password associated with the Rocket.Chat user account.';
$string['privacy:metadata:local_rocketchat_user:username'] = 'The email associated with the Rocket.Chat user account.';
$string['privacy:metadata:preference:local_rocketchat_external_token'] = 'The external token returned from linking the Rocket.Chat user account.';
$string['privacy:metadata:preference:local_rocketchat_external_user'] = 'The email associated with the Rocket.Chat user account.';
$string['protocol'] = 'Protocol';
$string['protocol_desc'] = 'Please specify the HTTP protocol if it\'s not the default (https).';
$string['rocketchat:linkaccount'] = 'Manage own linked Rocket.Chat account';
$string['rocketchat:manage'] = 'Manage Rocket.chat global settings';
$string['rocketchat:view'] = 'Manage Rocket.Chat course- and role integration global settings';
$string['role_desc'] = 'Manage integration between Moodle and Rocket.Chat. Specify which roles are included in the Rocket.Chat integration.';
$string['roleinfo_1'] = 'Checked roles will be included in sync. Removing a role will not remove users already uploaded to Rocket.Chat.';
$string['roletable_column_1'] = 'Course';
$string['roletable_column_2'] = 'Requires sync';
$string['scheduledtaskname'] = 'Sync students to Rocket.Chat';
$string['subscription_creation'] = 'Rocket.Chat integration - subscription creation';
$string['sync_returns'] = 'Whether or not the update was successful or not';
$string['user_creation'] = 'Rocket.Chat integration - user creation';
$string['username'] = 'Username';
$string['username_desc'] = 'Username for accessing the API. Please create a Rocket.Chat Moodle user for this purpose.';
