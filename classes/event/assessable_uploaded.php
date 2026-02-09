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
 * The assessable_uploaded event for YouTube submission plugin.
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_ytsubmission\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The assessable_uploaded event class.
 *
 * This event is triggered when a student uploads/submits a YouTube video URL.
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessable_uploaded extends \core\event\assessable_uploaded {

    /**
     * The assignment instance.
     *
     * @var \assign
     */
    private $assign;

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        try {
            return "The user with id '$this->userid' has uploaded a YouTube video URL " .
                   "to the submission with id '$this->objectid' " .
                   "in the assignment activity with course module id '$this->contextinstanceid'.";
        } catch (\Exception $e) {
            debugging('ytsubmission event: Error in get_description: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return "A YouTube video URL was uploaded.";
        }
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        try {
            return get_string('eventassessableuploaded', 'assignsubmission_ytsubmission');
        } catch (\Exception $e) {
            debugging('ytsubmission event: Error in get_name: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 'Assessable uploaded';
        }
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        try {
            return new \moodle_url('/mod/assign/view.php', array('id' => $this->contextinstanceid));
        } catch (\Exception $e) {
            debugging('ytsubmission event: Error in get_url: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return new \moodle_url('/');
        }
    }

    /**
     * Sets the assign object.
     *
     * This method is called by the plugin to set the assignment instance
     * so that it can be used by event observers.
     *
     * @param \assign $assign The assignment instance
     */
    public function set_assign(\assign $assign) {
        try {
            $this->assign = $assign;
            debugging('ytsubmission event: Assignment instance set successfully', DEBUG_DEVELOPER);
        } catch (\Exception $e) {
            debugging('ytsubmission event: Error in set_assign: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Gets the assign object.
     *
     * @return \assign|null The assignment instance or null if not set
     */
    public function get_assign() {
        try {
            return $this->assign;
        } catch (\Exception $e) {
            debugging('ytsubmission event: Error in get_assign: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        try {
            parent::validate_data();

            if (!isset($this->other['content'])) {
                throw new \coding_exception('The \'content\' value must be set in other.');
            }

            if (!isset($this->other['pathnamehashes'])) {
                throw new \coding_exception('The \'pathnamehashes\' value must be set in other.');
            }

            debugging('ytsubmission event: Data validation successful', DEBUG_DEVELOPER);
        } catch (\coding_exception $e) {
            debugging('ytsubmission event: Validation error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw $e;
        } catch (\Exception $e) {
            debugging('ytsubmission event: Unexpected error in validate_data: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        try {
            if ($this->assign) {
                $logmessage = get_string('submissionupdated', 'assignsubmission_ytsubmission');
                $this->set_legacy_logdata('upload', $logmessage);
                return parent::get_legacy_logdata();
            }
            return array();
        } catch (\Exception $e) {
            debugging('ytsubmission event: Error in get_legacy_logdata: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return array();
        }
    }
}