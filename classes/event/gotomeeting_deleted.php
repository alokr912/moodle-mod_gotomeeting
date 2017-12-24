<?php
/**
 * GoToWebinar module config test file
 *
 * @package mod_gotomeeting
 * @copyright  2017 Alok Kumar Rai <alokr.mail@gmail.com,alokkumarrai@outlook.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_gotomeeting\event;
defined('MOODLE_INTERNAL') || die();


class gotomeeting_deleted extends \core\event\base {
    /**
     * Init method
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'gotomeeting';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('gotomeetingdeleted', 'mod_gotomeeting');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' deleted the GoToLMS with id '$this->objectid' in the " .
            "GoToLMS activity with the course module id '$this->contextinstanceid'.";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url("/mod/glossary/editcategories.php",
                array('id' => $this->contextinstanceid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'glossary', 'delete category',
            "editcategories.php?id={$this->contextinstanceid}",
            $this->objectid, $this->contextinstanceid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        // Make sure this class is never used without proper object details.
        if (!$this->contextlevel === CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}

