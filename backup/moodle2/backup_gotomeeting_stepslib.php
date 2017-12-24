<?php
/**
 * GoToWebinar module view file
 *
 * @package mod_gotomeeting
 * @copyright 2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_gotomeeting_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $gotomeeting = new backup_nested_element('gotomeeting', array('id'), 
                                                    array('course', 
                                                          'name', 
                                                          'intro',
                                                          'introformat',
                                                          'meetingtype', 
                                                          'userid',
                                                          'meetinginfo',
                                                          'gotomeetingid',
                                                          'startdatetime',
                                                          'enddatetime',
                                                          'completionparticipation',
                                                          'meetingpublic',
                                                          'timecreated',
                                                          'timemodified'));

        $gotomeeting->set_source_table('gotomeeting', array('id' => backup::VAR_ACTIVITYID));
         return $this->prepare_activity_structure($gotomeeting);
    }

}
