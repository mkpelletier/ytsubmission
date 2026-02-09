<?php
/**
 * Restore functionality for the YouTube submission plugin
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore subplugin class for YouTube submissions
 *
 * Provides the necessary functionality to restore YouTube submission data
 * as part of the assignment restore process.
 */
class restore_assignsubmission_ytsubmission_subplugin extends restore_subplugin {

    /**
     * Define the restore path structure
     *
     * @return array Array of restore path elements
     */
    protected function define_submission_subplugin_structure() {
        try {
            $paths = array();

            // Define the path for YouTube submission data.
            $elename = $this->get_namefor('submission_ytsubmission');
            $elepath = $this->get_pathfor('/submission_ytsubmission');
            $paths[] = new restore_path_element($elename, $elepath);

            return $paths;
        } catch (Exception $e) {
            debugging('Error defining restore structure: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return array();
        }
    }

    /**
     * Process the YouTube submission data during restore
     *
     * @param array $data The data to restore
     */
    public function process_assignsubmission_ytsubmission_submission_ytsubmission($data) {
        global $DB;

        try {
            $data = (object)$data;

            // Get the new submission ID from the mapping.
            $data->submission = $this->get_new_parentid('submission');
            $data->assignment = $this->get_mappingid('assign', $this->task->get_activityid());

            // Insert the restored YouTube submission data.
            $DB->insert_record('assignsubmission_ytsubmission', $data);
        } catch (Exception $e) {
            debugging('Error processing restore data: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}