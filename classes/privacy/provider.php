<?php
/**
 * Privacy provider for the YouTube submission plugin
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_ytsubmission\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\local\request\contextlist;
use mod_assign\privacy\assign_plugin_request_data;

/**
 * Privacy provider class for the YouTube submission plugin
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \mod_assign\privacy\assignsubmission_provider {

    /**
     * Return metadata about this plugin
     *
     * @param collection $collection The collection to add metadata to
     * @return collection The updated collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('assignsubmission_ytsubmission', [
            'assignment' => 'privacy:metadata:assignmentid',
            'submission' => 'privacy:metadata:submissionpurpose',
            'youtubeurl' => 'privacy:metadata:youtubeurl',
        ], 'privacy:metadata:tablepurpose');

        // YouTube URLs are external data.
        $collection->add_external_location_link('youtube', [
            'videoid' => 'privacy:metadata:youtubeurl',
        ], 'privacy:metadata:externalpurpose');

        return $collection;
    }

    /**
     * Export all user data for this plugin
     *
     * @param assign_plugin_request_data $exportdata Data used to export user information
     */
    public static function export_submission_user_data(assign_plugin_request_data $exportdata) {
        global $DB;

        try {
            // Get the submission context and user.
            $submission = $exportdata->get_pluginobject();
            $context = $exportdata->get_context();
            $user = $exportdata->get_user();

            // Retrieve YouTube submission data.
            $youtubesubmission = $DB->get_record(
                'assignsubmission_ytsubmission',
                array('submission' => $submission->id)
            );

            if ($youtubesubmission) {
                // Export the YouTube URL data.
                $data = new \stdClass();
                $data->youtubeurl = $youtubesubmission->youtubeurl;
                $data->videoid = $youtubesubmission->videoid;
                $data->videotitle = $youtubesubmission->videotitle;
                
                writer::with_context($context)->export_data(
                    [get_string('privacy:path', 'assignsubmission_ytsubmission')],
                    $data
                );
            }
        } catch (\Exception $e) {
            debugging('Error exporting user data: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Delete all user data for this context
     *
     * @param assign_plugin_request_data $deletedata Data used to delete user information
     */
    public static function delete_submission_for_context(assign_plugin_request_data $deletedata) {
        global $DB;

        try {
            $DB->delete_records('assignsubmission_ytsubmission', [
                'assignment' => $deletedata->get_assign()->get_instance()->id
            ]);
        } catch (\Exception $e) {
            debugging('Error deleting submission for context: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Delete all user data for this user in this context
     *
     * @param assign_plugin_request_data $deletedata Data used to delete user information
     */
    public static function delete_submission_for_userid(assign_plugin_request_data $deletedata) {
        global $DB;

        try {
            $submission = $deletedata->get_pluginobject();
            if ($submission) {
                $DB->delete_records('assignsubmission_ytsubmission', [
                    'submission' => $submission->id
                ]);
            }
        } catch (\Exception $e) {
            debugging('Error deleting submission for user: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}