<?php
// This file is part of the GoToMeeting plugin for Moodle - http://moodle.org/
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
 * GoToWebinar module view file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/gotomeeting/backup/moodle2/backup_gotomeeting_stepslib.php');

class backup_gotomeeting_activity_task extends backup_activity_task {

    protected function define_my_settings() {

    }

    protected function define_my_steps() {
        $this->add_step(new backup_gotomeeting_activity_structure_step('gotomeeting_structure', 'gotomeeting.xml'));
    }

    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of adobeconnect instances.
        $search = "/(" . $base . "\/mod\/gotomeeting\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@GOTOLMS*$2@$', $content);

        // Link to adobeconnect view by moduleid.
        $search = "/(" . $base . "\/mod\/GOTOLMS\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@GOTOLMS*$2@$', $content);

        return $content;
    }

}
