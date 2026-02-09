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
 * Language strings for YouTube submission plugin.
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'YouTube video submission';
$string['ytsubmission'] = 'YouTube video submission';
$string['enabled'] = 'YouTube video submission';
$string['enabled_help'] = 'If enabled, students can submit YouTube video links for their assignment submission.';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this submission method will be enabled by default for all new assignments.';

// Submission strings.
$string['youtubeurl'] = 'YouTube video URL';
$string['youtubeurl_help'] = 'Enter the full URL of your YouTube video. The video must be set to public or unlisted.';
$string['enterurl'] = 'Enter YouTube URL';
$string['invalidurl'] = 'Invalid YouTube URL. Please enter a valid YouTube video link.';
$string['videonotfound'] = 'Video not found or is private. Please check your video settings.';
$string['submissiontitle'] = 'YouTube Video Submission';

// Display strings.
$string['numwords'] = 'Video URL submitted';
$string['nosubmission'] = 'No YouTube video has been submitted for this assignment';
$string['videoid'] = 'Video ID';
$string['videourl'] = 'Video URL';
$string['watchvideo'] = 'Watch video';

// Privacy strings.
$string['privacy:metadata:assignmentid'] = 'Assignment ID';
$string['privacy:metadata:submissionpurpose'] = 'The submission ID that links to submissions for the user.';
$string['privacy:metadata:ytsubmissionpurpose'] = 'The YouTube video URL submitted by the user.';
$string['privacy:metadata:tablepurpose'] = 'Stores the YouTube video links submitted by students.';
$string['privacy:metadata:youtubeurl'] = 'The YouTube video URL submitted by the user.';
$string['privacy:metadata:externalpurpose'] = 'Links to externally hosted YouTube content.';
$string['privacy:metadata:tablepurpose'] = 'Stores YouTube link submissions.';
$string['privacy:path'] = 'YouTube submission';

// Event strings.
$string['eventassessableuploaded'] = 'A YouTube video link has been uploaded.';

// Error strings.
$string['errorsaving'] = 'Error saving YouTube submission';
$string['errorloading'] = 'Error loading YouTube submission';
$string['errordeleting'] = 'Error deleting YouTube submission';

// --- Feedback interface language strings ---
$string['existingfeedback'] = 'Existing Feedback';
$string['entercomment'] = 'Enter a comment...';
$string['timestampedfeedback'] = "Timestamped Feedback";
$string['currenttime'] = "Current time";
$string['addtimestampcomment'] = "Add new comment";
$string['timestamp'] = "Timestamp for this comment";
$string['comment'] = "Feedback comment";
$string['addtimestampcomment'] = "Add comment";
$string['commentadded'] = 'Comment added.';
$string['commentdeleted'] = 'Comment deleted.';
$string['viewfeedback'] = 'Feedback';
$string['yoursubmission'] = 'Your submission';
$string['nocomments'] = 'No comments yet.';

// Comment type / category strings.
$string['commenttype'] = 'Comment category';
$string['commenttype_general'] = 'General';
$string['commenttype_praise'] = 'Praise';
$string['commenttype_correction'] = 'Correction';
$string['commenttype_suggestion'] = 'Suggestion';
$string['commenttype_question'] = 'Question';

// File area strings.
$string['commentfiles'] = 'Comment file attachments';

// Comment library strings.
$string['insertfromlibrary'] = 'Insert from library';
$string['savetolibrary'] = 'Save to library';
$string['commentlibrary'] = 'Comment Library';
$string['mycomments'] = 'My Comments';
$string['coursecomments'] = 'Course Comments';
$string['nopersonalcomments'] = 'No personal comments saved.';
$string['nosharedcomments'] = 'No shared comments yet.';
$string['searchcomments'] = 'Search comments...';
$string['savedtolibrary'] = 'Comment saved to library.';
$string['deletelibrary'] = 'Delete this library comment?';
$string['mylibrary'] = 'My Library';
$string['courselibrary'] = 'Course Library';
