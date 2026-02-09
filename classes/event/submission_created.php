<?php
/**
 * Event for when a YouTube submission is created
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_ytsubmission\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event class for submission created
 */
class submission_created extends \core\event\base {

    /**
     * Init method
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'assignsubmission_ytsubmission';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns description of what happened
     *
     * @return string Description of the event
     */
    public function get_description() {
        return "The user with id '$this->userid' created a YouTube submission " .
               "with id '$this->objectid' for the assignment with course module id '$this->contextinstanceid'.";
    }

    /**
     * Return localised event name
     *
     * @return string Localised event name
     */
    public static function get_name() {
        return get_string('eventsubmissioncreated', 'assignsubmission_ytsubmission');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url URL related to the event
     */
    public function get_url() {
        return new \moodle_url('/mod/assign/view.php', array('id' => $this->contextinstanceid));
    }
}