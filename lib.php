<?PHP
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
 * This file contains the moodle hooks for the submission onlinepoodll plugin
 *
 * @package    assignsubmission_onlinepoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use assignsubmission_onlinepoodll\constants;

/**
 * Serves assignment submissions and other files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function assignsubmission_onlinepoodll_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload) {
    global $USER, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    $itemid = (int)array_shift($args);
	
	//back image is a special case
	if(!($itemid==0 && $filearea=constants::M_WB_FILEAREA)){

		$record = $DB->get_record('assign_submission', array('id'=>$itemid), 'userid, assignment', MUST_EXIST);
		$userid = $record->userid;

		if (!$assign = $DB->get_record('assign', array('id'=>$cm->instance))) {
			return false;
		}

		if ($assign->id != $record->assignment) {
			return false;
		}

		// check is users submission or has grading permission
		if ($USER->id != $userid and
                !has_capability('mod/assign:grade', $context) &&
                !has_capability('assignsubmission/onlinepoodll:reviewsubmissions', $context)) {
			return false;
		}
	}

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/assignsubmission_onlinepoodll/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    $forcedownload = ($filearea !=constants::M_WB_FILEAREA);
    send_stored_file($file, 0, 0, $forcedownload); // download MUST be forced - security!
}
