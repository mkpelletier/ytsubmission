<?php
/**
 * Backup functionality for the YouTube submission plugin
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backup subplugin class for YouTube submissions
 *
 * Provides the necessary functionality to backup YouTube submission data
 * as part of the assignment backup process.
 */
class backup_assignsubmission_ytsubmission_subplugin extends backup_subplugin {

    /**
     * Define the structure of the backup
     *
     * @return backup_subplugin_element The backup structure
     */
    protected function define_submission_subplugin_structure() {
        try {
            // Create the wrapper element for the YouTube submission data.
            $subplugin = $this->get_subplugin_element();
            $subpluginwrapper = new backup_nested_element($this->get_recommended_name());

            // Define the YouTube submission element with its fields.
            $subpluginyt = new backup_nested_element('submission_ytsubmission', array('id'), array(
                'youtubeurl',
                'videoid',
                'videotitle',
                'videoduration'
            ));

            // Build the tree structure.
            $subplugin->add_child($subpluginwrapper);
            $subpluginwrapper->add_child($subpluginyt);

            // Set the source for the YouTube submission data.
            $subpluginyt->set_source_table('assignsubmission_ytsubmission', 
                array('submission' => backup::VAR_PARENTID)
            );

            return $subplugin;
        } catch (Exception $e) {
            debugging('Error defining backup structure: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }
}