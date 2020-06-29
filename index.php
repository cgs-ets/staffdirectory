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
 * CGS Staff Directory
 *
 * @package   local_staffdirectory
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

// Include required files and classes.
require_once('../../config.php');
require_once('locallib.php');

$context = context_system::instance();

// Set up page parameters.
$url = new moodle_url('/local/staffdirectory/index.php');
$PAGE->set_context($context);
$PAGE->set_url($url->out());
$title = get_string('staffdirectory', 'local_staffdirectory');
$PAGE->set_heading($title);
$PAGE->set_title($SITE->fullname . ': ' . $title);
$PAGE->navbar->add($title);

// Ensure user is logged in.
require_login();

// Include page CSS.
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/staffdirectory/styles.css'));
$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/local/staffdirectory/vendor/js/listjs/list.min.js'), true );


// Output header.
echo $OUTPUT->header();

// Load and check the global settings.
$config = get_config('local_staffdirectory');
if (empty($config->dbtype) || 
    empty($config->dbhost) || 
    empty($config->dbuser) || 
    empty($config->dbpass) || 
    empty($config->dbname) || 
    empty($config->sqldirectory))
{
        $notification = new \core\output\notification(
            get_string('config:missingsettings', 'local_staffdirectory'),
            \core\output\notification::NOTIFY_ERROR
        );
        $notification->set_show_closebutton(false);
        echo $OUTPUT->render($notification);
        echo $OUTPUT->footer();
        exit;
}

$staffdata = array();
try {
    // Get preferred driver. Last parameter (external = true) means we are connecting to an external database.
    $externalDB = moodle_database::get_driver_instance($config->dbtype, 'native', true);        
    // Connect to the DB.
    $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');
    // Get the staff listing
    $staffdata = array_values($externalDB->get_records_sql($config->sqldirectory));
} catch (Exception $e) {} 

// Process the listing.
$directory = array();
foreach ($staffdata as $staff) {
    $user = core_user::get_user_by_username($staff->staffid);
    $staff->photo = new moodle_url('/user/pix.php/0/f2.jpg');
    if ($user) {
        $staff->photo = new moodle_url('/user/pix.php/'.$user->id.'/f2.jpg');
    }
    $directory[] = (array) $staff;
}

// Get the user type.
$isstaff = false; //default
if(isset($USER->profile['CampusRoles'])) {
    $campusroles = strtolower($USER->profile['CampusRoles']);
    if (strpos($campusroles, 'staff') !== false) {
        $isstaff = true;
    }
}

// Set up the page data.
$data = array(
    'isstaff' => $isstaff,
    'staffblurb' => $config->staffblurb,
    'hasresults' => (count($directory) > 0),
    'directory' => $directory,
);

// Output page template.
echo $OUTPUT->render_from_template('local_staffdirectory/directory', $data);

// Include page scripts.
$PAGE->requires->js_call_amd('local_staffdirectory/directory', 'init');

// Output footer.
echo $OUTPUT->footer();
