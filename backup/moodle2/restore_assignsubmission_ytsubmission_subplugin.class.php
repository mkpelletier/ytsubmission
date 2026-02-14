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
        $paths = [];

        $elename = $this->get_namefor('submission_ytsubmission');
        $elepath = $this->get_pathfor('/submission_ytsubmission');
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = $this->get_namefor('submission_ytsubmission_comment');
        $elepath = $this->get_pathfor('/submission_ytsubmission/submission_ytsubmission_comment');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Process the YouTube submission data during restore
     *
     * @param array $data The data to restore
     */
    public function process_assignsubmission_ytsubmission_submission_ytsubmission($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->submissionid = $this->get_new_parentid('submission');
        $data->assignment = $this->get_mappingid('assign', $data->assignment);

        $newid = $DB->insert_record('assignsubmission_ytsubmission', $data);
        $this->set_mapping('assignsubmission_ytsubmission', $oldid, $newid);
    }

    /**
     * Process a comment during restore
     *
     * @param array $data The comment data to restore
     */
    public function process_assignsubmission_ytsubmission_submission_ytsubmission_comment($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->submissionid = $this->get_new_parentid('submission');
        $data->assignmentid = $this->get_mappingid('assign', $data->assignmentid);
        $data->graderid = $this->get_mappingid('user', $data->graderid);

        $newid = $DB->insert_record('assignsubmission_ytsubmission_comments', $data);
        $this->set_mapping('assignsubmission_ytsubmission_comment', $oldid, $newid, true);

        // Restore files for this comment.
        $this->add_related_files('assignsubmission_ytsubmission', 'commentfiles', 'assignsubmission_ytsubmission_comment', null, $oldid);
    }
}
