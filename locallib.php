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
 * Library functions for YouTube submission plugin.
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * YouTube submission plugin class.
 *
 * This plugin allows students to submit YouTube video links as their assignment submission.
 * Teachers can view the videos and provide timestamped feedback using the YouTube IFrame API.
 *
 * Database schema alignment:
 * - assignsubmission_ytsubmission: stores video URL and metadata
 * - assignsubmission_ytsubmission_comments: stores timestamped grading comments
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_ytsubmission extends assign_submission_plugin {

    /** @var array Comment type definitions with labels and colors. */
    const COMMENT_TYPES = [
        'general'    => ['label' => 'General',    'color' => '#6c757d'],
        'praise'     => ['label' => 'Praise',     'color' => '#28a745'],
        'correction' => ['label' => 'Correction', 'color' => '#dc3545'],
        'suggestion' => ['label' => 'Suggestion', 'color' => '#0d6efd'],
        'question'   => ['label' => 'Question',   'color' => '#6f42c1'],
    ];

    /**
     * Get the name of the YouTube submission plugin.
     *
     * @return string The plugin name
     */
    public function get_name() {
        try {
            return get_string('ytsubmission', 'assignsubmission_ytsubmission');
        } catch (\Exception $e) {
            return 'YouTube Submission';
        }
    }

    /**
     * Add form elements for the YouTube URL submission.
     *
     * This method is called when building the submission form. It adds a text field
     * for the YouTube URL and pre-populates it if a submission already exists.
     *
     * @param mixed $submission The submission object (can be null for new submissions)
     * @param MoodleQuickForm $mform The form to add elements to
     * @param stdClass $data The form data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $DB;

        try {

            // Get existing submission if available (using correct field name: submissionid).
            $videourl = '';

            if ($submission) {
                try {
                    $ytsubmission = $DB->get_record(
                        'assignsubmission_ytsubmission',
                        ['submissionid' => $submission->id],
                        'id, videourl, youtubeid',
                        IGNORE_MISSING
                    );

                    if ($ytsubmission) {
                        $videourl = $ytsubmission->videourl;
                    } else {
                    }
                } catch (\Exception $e) {
                }
            }

            // Add YouTube URL text field.
            $mform->addElement(
                'text',
                'assignsubmission_ytsubmission_youtubeurl',
                get_string('youtubeurl', 'assignsubmission_ytsubmission'),
                ['size' => '60']
            );

            $mform->setType('assignsubmission_ytsubmission_youtubeurl', PARAM_URL);
            $mform->addHelpButton(
                'assignsubmission_ytsubmission_youtubeurl',
                'youtubeurl',
                'assignsubmission_ytsubmission'
            );

            if ($videourl) {
                $mform->setDefault('assignsubmission_ytsubmission_youtubeurl', $videourl);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Save the YouTube URL submission.
     *
     * This method extracts the YouTube video ID from the submitted URL, validates it,
     * and stores it in the database. It handles both new submissions and updates to
     * existing submissions.
     *
     * @param stdClass $submission The submission object
     * @param stdClass $data The form data
     * @return bool True if successful
     * @throws moodle_exception if the URL is invalid or save fails
     */
    public function save(stdClass $submission, stdClass $data) {
        global $DB;

        try {

            // Get the YouTube URL from the form data.
            $youtubeurl = '';
            if (isset($data->assignsubmission_ytsubmission_youtubeurl)) {
                $youtubeurl = trim($data->assignsubmission_ytsubmission_youtubeurl);
            } else {
            }

            // Validate that we have a YouTube URL.
            if (empty($youtubeurl)) {
                return true; // Allow empty submissions.
            }

            // Validate the YouTube URL format and extract video ID.
            $videoid = $this->extract_video_id($youtubeurl);

            if (!$videoid) {
                throw new \moodle_exception('invalidurl', 'assignsubmission_ytsubmission');
            }


            // Check if a submission already exists (using correct field name: submissionid).
            try {
                $ytsubmission = $DB->get_record(
                    'assignsubmission_ytsubmission',
                    ['submissionid' => $submission->id],
                    '*',
                    IGNORE_MISSING
                );

                if ($ytsubmission) {
                } else {
                }
            } catch (\Exception $e) {
                $ytsubmission = false;
            }

            // Prepare the submission data (aligned with install.xml schema).
            $submissiondata = new \stdClass();
            $submissiondata->submissionid = $submission->id;  // Correct field name.
            $submissiondata->assignment = $this->assignment->get_instance()->id;
            $submissiondata->youtubeid = $videoid;
            $submissiondata->videourl = $youtubeurl;
            $submissiondata->thumbnail = 'https://img.youtube.com/vi/' . $videoid . '/hqdefault.jpg';
            $submissiondata->timemodified = time();


            if ($ytsubmission) {
                // Update existing submission.
                $submissiondata->id = $ytsubmission->id;

                try {
                    $result = $DB->update_record('assignsubmission_ytsubmission', $submissiondata);
                } catch (\Exception $e) {
                    throw new \moodle_exception('errorsaving', 'assignsubmission_ytsubmission');
                }
            } else {
                // Insert new submission.
                try {
                    $submissiondata->id = $DB->insert_record('assignsubmission_ytsubmission', $submissiondata);
                } catch (\Exception $e) {
                    throw new \moodle_exception('errorsaving', 'assignsubmission_ytsubmission');
                }
            }

            // Trigger assessable_uploaded event.
            $this->trigger_assessable_uploaded_event($submission, $youtubeurl);

            return true;
        } catch (\moodle_exception $e) {
            // Re-throw Moodle exceptions.
            throw $e;
        } catch (\Exception $e) {
            throw new \moodle_exception('errorsaving', 'assignsubmission_ytsubmission');
        }
    }

    /**
     * Extract the video ID from a YouTube URL.
     *
     * Supports various YouTube URL formats:
     * - https://www.youtube.com/watch?v=VIDEO_ID
     * - https://youtu.be/VIDEO_ID
     * - https://www.youtube.com/embed/VIDEO_ID
     * - https://www.youtube.com/v/VIDEO_ID
     *
     * @param string $url The YouTube URL
     * @return string|false The video ID or false if invalid
     */
    private function extract_video_id($url) {
        try {

            // Pattern to match various YouTube URL formats.
            $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';

            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Display the YouTube video with grading interface (for teachers) or feedback (for students).
     * This method is called when clicking the magnifying glass icon in the grading table.
     *
     * @param stdClass $submission The submission object
     * @return string The HTML output
     */
/**
 * Display the YouTube video with grading interface (for teachers) or feedback (for students).
 * This method is called when clicking the magnifying glass icon in the grading table.
 *
 * @param stdClass $submission The submission object
 * @return string The HTML output
 */
public function view(stdClass $submission) {
    global $CFG, $DB, $PAGE;
    try {
        // Get the YouTube submission (using correct field name: submissionid).
        $ytsubmission = $DB->get_record(
            'assignsubmission_ytsubmission',
            ['submissionid' => $submission->id],
            '*',
            IGNORE_MISSING
        );
        if (!$ytsubmission) {
            return get_string('nosubmission', 'assignsubmission_ytsubmission');
        }
        // Check if user can grade to determine which interface to show.
        $context = $this->assignment->get_context();
        $cangrade = has_capability('mod/assign:grade', $context);
        // Get existing feedback comments (using correct field name: submissionid).
        $comments = $DB->get_records(
            'assignsubmission_ytsubmission_comments',
            ['submissionid' => $submission->id],
            'timestamp ASC'
        );
        // Build the output HTML.
        $output = '';
        // Add custom CSS for the grading interface.
        $output .= html_writer::start_tag('style');
        $output .= '
            .ytsubmission-grading-container {
                max-width: 1200px;
                margin: 0 auto;
            }
            .ytsubmission-video-section {
                margin-bottom: 30px;
            }
            .ytsubmission-player-wrapper {
                position: relative;
                padding-bottom: 56.25%;
                height: 0;
                overflow: hidden;
                max-width: 100%;
                background: #000;
            }
            .ytsubmission-player-wrapper iframe {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }
            .ytsubmission-feedback-section {
                margin-top: 30px;
            }
            .ytsubmission-current-time-display {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .ytsubmission-comment-form {
                background: #fff;
                padding: 20px;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .ytsubmission-comment-item {
                transition: all 0.3s ease;
            }
            .ytsubmission-comment-item:hover {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .ytsubmission-timestamp-link {
                cursor: pointer;
                text-decoration: none;
            }
            .ytsubmission-timestamp-link:hover {
                text-decoration: underline;
            }
        ';
        $output .= html_writer::end_tag('style');
        $output .= html_writer::start_div('ytsubmission-grading-container', ['id' => 'ytsubmission-grading-container']);
        // Video section.
        $output .= html_writer::start_div('ytsubmission-video-section');
        if ($cangrade) {
            $output .= html_writer::tag('h3', get_string('submissiontitle', 'assignsubmission_ytsubmission'));
        } else {
            $output .= html_writer::tag('h3', get_string('yoursubmission', 'assignsubmission_ytsubmission'));
        }
        // Display the video URL.
        $output .= html_writer::tag(
            'p',
            html_writer::tag('strong', get_string('videourl', 'assignsubmission_ytsubmission') . ': ') .
            html_writer::link($ytsubmission->videourl, $ytsubmission->videourl, ['target' => '_blank'])
        );
        // Embed the YouTube video with API enabled for grading interface.
        if ($cangrade) {
            $output .= html_writer::start_div('ytsubmission-player-wrapper');
            $output .= html_writer::tag(
                'div',
                '',
                ['id' => 'ytsubmission-grading-player']
            );
            $output .= html_writer::end_div();

            // Timeline bar for comment markers (below the player).
            $output .= html_writer::start_div('ytsubmission-timeline-container',
                ['id' => 'ytsubmission-timeline-container']);
            $output .= html_writer::div('', 'ytsubmission-timeline-bar',
                ['id' => 'ytsubmission-timeline-bar']);
            $output .= html_writer::div('', 'ytsubmission-timeline-playhead',
                ['id' => 'ytsubmission-timeline-playhead']);
            $output .= html_writer::end_div();
        } else {
            // Student view — use IFrame API player for interactive timeline.
            $output .= html_writer::start_div('ytsubmission-player-wrapper');
            $output .= html_writer::tag('div', '', ['id' => 'ytsubmission-grading-player']);
            $output .= html_writer::end_div();

            // Timeline bar for students (read-only, colored markers).
            $output .= html_writer::start_div('ytsubmission-timeline-container',
                ['id' => 'ytsubmission-timeline-container']);
            $output .= html_writer::div('', 'ytsubmission-timeline-bar',
                ['id' => 'ytsubmission-timeline-bar']);
            $output .= html_writer::div('', 'ytsubmission-timeline-playhead',
                ['id' => 'ytsubmission-timeline-playhead']);
            $output .= html_writer::end_div();
        }
        $output .= html_writer::end_div(); // End video section.
        // Feedback section.
        if ($cangrade) {
            // Grader view - show comment form and existing comments.
            $output .= html_writer::start_div('ytsubmission-feedback-section');
            $output .= html_writer::tag('h3', get_string('timestampedfeedback', 'assignsubmission_ytsubmission'));
            // Current time display.
            $output .= html_writer::start_div('ytsubmission-current-time-display');
            $output .= html_writer::tag('strong', get_string('currenttime', 'assignsubmission_ytsubmission') . ': ');
            $output .= html_writer::tag('span', '00:00', ['id' => 'ytsubmission-current-time']);
            $output .= html_writer::end_div();
            // Comment form.
            $output .= html_writer::start_div('ytsubmission-comment-form');
            $output .= html_writer::tag('h4', get_string('addtimestampcomment', 'assignsubmission_ytsubmission'));
            $output .= html_writer::start_div('form-group mb-3');
            $output .= html_writer::tag('label', get_string('timestamp', 'assignsubmission_ytsubmission'),
                ['for' => 'ytsubmission-timestamp-input']);
            $output .= html_writer::empty_tag('input', [
                'type' => 'number',
                'id' => 'ytsubmission-timestamp-input',
                'class' => 'form-control',
                'value' => '0',
                'readonly' => 'readonly'
            ]);
            $output .= html_writer::end_div();

            // Comment type dropdown.
            $output .= html_writer::start_div('form-group mb-3');
            $output .= html_writer::tag('label', get_string('commenttype', 'assignsubmission_ytsubmission'),
                ['for' => 'ytsubmission-commenttype']);
            $typeoptions = '';
            foreach (self::COMMENT_TYPES as $key => $typeinfo) {
                $typeoptions .= html_writer::tag('option',
                    get_string('commenttype_' . $key, 'assignsubmission_ytsubmission'),
                    ['value' => $key]
                );
            }
            $output .= html_writer::tag('select', $typeoptions, [
                'id' => 'ytsubmission-commenttype',
                'class' => 'form-control'
            ]);
            $output .= html_writer::end_div();

            // Comment text editor.
            $output .= html_writer::start_div('form-group mb-3');
            $output .= html_writer::tag('label', get_string('comment', 'assignsubmission_ytsubmission'),
                ['for' => 'ytsubmission-comment-text']);
            $output .= html_writer::tag('textarea', '', [
                'id' => 'ytsubmission-comment-text',
                'name' => 'ytsubmission-comment-text',
                'class' => 'form-control',
                'rows' => '6',
                'placeholder' => get_string('entercomment', 'assignsubmission_ytsubmission')
            ]);

            // Initialize WYSIWYG editor with file picker support for audio recording.
            require_once($CFG->dirroot . '/repository/lib.php');
            $draftitemid = file_get_unused_draft_itemid();
            $editoroptions = [
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'context' => $context,
                'subdirs' => 0,
                'maxbytes' => $CFG->maxbytes,
            ];
            $editor = editors_get_preferred_editor(FORMAT_HTML);
            $editor->head_setup();

            // Build file picker options for the editor (enables audio recording, image upload, etc.).
            $fpoptions = [];
            $args = new stdClass();
            $args->accepted_types = ['image'];
            $args->return_types = FILE_INTERNAL | FILE_EXTERNAL;
            $args->context = $context;
            $args->env = 'editor';
            $imagepicker = initialise_filepicker($args);
            $imagepicker->itemid = $draftitemid;
            $fpoptions['image'] = $imagepicker;

            $args = new stdClass();
            $args->accepted_types = ['video', 'audio'];
            $args->return_types = FILE_INTERNAL | FILE_EXTERNAL;
            $args->context = $context;
            $args->env = 'editor';
            $mediapicker = initialise_filepicker($args);
            $mediapicker->itemid = $draftitemid;
            $fpoptions['media'] = $mediapicker;

            $editor->use_editor('ytsubmission-comment-text', $editoroptions, $fpoptions);

            $output .= html_writer::end_div();

            // Hidden draft item ID for JS to read.
            $output .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'id' => 'ytsubmission-draftitemid',
                'value' => $draftitemid
            ]);

            // Action buttons row.
            $output .= html_writer::start_div('d-flex gap-2 align-items-center');
            $output .= html_writer::tag('button', get_string('addtimestampcomment', 'assignsubmission_ytsubmission'), [
                'id' => 'ytsubmission-add-comment-btn',
                'class' => 'btn btn-primary'
            ]);
            $output .= html_writer::tag('button',
                html_writer::tag('i', '', ['class' => 'fa fa-book me-1']) .
                get_string('insertfromlibrary', 'assignsubmission_ytsubmission'), [
                'id' => 'ytsubmission-library-insert-btn',
                'class' => 'btn btn-outline-secondary',
                'type' => 'button'
            ]);
            $output .= html_writer::tag('button',
                html_writer::tag('i', '', ['class' => 'fa fa-save me-1']) .
                get_string('savetolibrary', 'assignsubmission_ytsubmission'), [
                'id' => 'ytsubmission-library-save-btn',
                'class' => 'btn btn-outline-secondary',
                'type' => 'button'
            ]);
            $output .= html_writer::end_div();

            // Library panel (hidden by default).
            $output .= html_writer::div('', 'ytsubmission-library-panel', [
                'id' => 'ytsubmission-library-panel',
                'style' => 'display:none;'
            ]);

            $output .= html_writer::end_div(); // End comment form.
            // Comments list.
            $output .= html_writer::tag('h4', get_string('existingfeedback', 'assignsubmission_ytsubmission'),
                ['class' => 'mt-4']);
            $output .= html_writer::start_div('', ['id' => 'ytsubmission-comments-list']);
            if (empty($comments)) {
                $output .= html_writer::tag('p', get_string('nocomments', 'assignsubmission_ytsubmission'),
                    ['class' => 'text-muted']);
            } else {
                foreach ($comments as $comment) {
                    $output .= $this->render_comment($comment, true);
                }
            }
            $output .= html_writer::end_div(); // End comments list.
            $output .= html_writer::end_div(); // End feedback section.

            // ———— INITIALISE JAVASCRIPT ————
            // Build comments array for JS timeline markers.
            $jscomments = [];
            foreach ($comments as $c) {
                $graderobj = \core_user::get_user($c->graderid, '*', MUST_EXIST);
                $jscomments[] = [
                    'id' => (int)$c->id,
                    'timestamp' => (int)$c->timestamp,
                    'comment' => shorten_text(strip_tags($c->comment), 80),
                    'commenttype' => !empty($c->commenttype) ? $c->commenttype : 'general',
                    'gradername' => fullname($graderobj),
                ];
            }

            // Build comment types map for JS.
            $jscommenttypes = [];
            foreach (self::COMMENT_TYPES as $key => $typeinfo) {
                $jscommenttypes[$key] = [
                    'label' => get_string('commenttype_' . $key, 'assignsubmission_ytsubmission'),
                    'color' => $typeinfo['color'],
                ];
            }

            // Embed large data via data attribute to avoid js_call_amd size limit.
            $embeddeddata = json_encode([
                'comments'     => $jscomments,
                'commentTypes' => $jscommenttypes,
            ]);
            $output .= html_writer::tag('div', '', [
                'id' => 'ytsubmission-jsdata',
                'data-payload' => $embeddeddata,
                'style' => 'display:none;',
            ]);

            $PAGE->requires->js_call_amd(
                'assignsubmission_ytsubmission/grading',
                'init',
                [[
                    'videoId'      => $ytsubmission->youtubeid,
                    'submissionId' => (int)$submission->id,
                    'assignId'     => (int)$this->assignment->get_instance()->id,
                    'readOnly'     => false,
                    'courseId'     => (int)$this->assignment->get_course()->id,
                ]]
            );
            // ——————————————————————————————————————

        } else {
            // Student view - show existing feedback (read-only).
            $output .= html_writer::start_div('ytsubmission-feedback-section');
            $output .= html_writer::tag('h3', get_string('viewfeedback', 'assignsubmission_ytsubmission'));
            if (empty($comments)) {
                $output .= html_writer::tag('p', get_string('nocomments', 'assignsubmission_ytsubmission'),
                    ['class' => 'text-muted']);
            } else {
                $output .= html_writer::start_div('', ['id' => 'ytsubmission-comments-list']);
                foreach ($comments as $comment) {
                    $output .= $this->render_comment($comment, false);
                }
                $output .= html_writer::end_div();
            }
            $output .= html_writer::end_div(); // End feedback section.

            // Initialize JS for student view (read-only timeline).
            $jscomments = [];
            foreach ($comments as $c) {
                $graderobj = \core_user::get_user($c->graderid, '*', MUST_EXIST);
                $jscomments[] = [
                    'id' => (int)$c->id,
                    'timestamp' => (int)$c->timestamp,
                    'comment' => shorten_text(strip_tags($c->comment), 80),
                    'commenttype' => !empty($c->commenttype) ? $c->commenttype : 'general',
                    'gradername' => fullname($graderobj),
                ];
            }

            $jscommenttypes = [];
            foreach (self::COMMENT_TYPES as $key => $typeinfo) {
                $jscommenttypes[$key] = [
                    'label' => get_string('commenttype_' . $key, 'assignsubmission_ytsubmission'),
                    'color' => $typeinfo['color'],
                ];
            }

            // Embed large data via data attribute to avoid js_call_amd size limit.
            $embeddeddata = json_encode([
                'comments'     => $jscomments,
                'commentTypes' => $jscommenttypes,
            ]);
            $output .= html_writer::tag('div', '', [
                'id' => 'ytsubmission-jsdata',
                'data-payload' => $embeddeddata,
                'style' => 'display:none;',
            ]);

            $PAGE->requires->js_call_amd(
                'assignsubmission_ytsubmission/grading',
                'init',
                [[
                    'videoId'      => $ytsubmission->youtubeid,
                    'submissionId' => (int)$submission->id,
                    'assignId'     => (int)$this->assignment->get_instance()->id,
                    'readOnly'     => true,
                    'courseId'     => 0,
                ]]
            );
        }
        $output .= html_writer::end_div(); // End grading container.
        return $output;
    } catch (\Exception $e) {
        return get_string('errorloading', 'assignsubmission_ytsubmission');
    }
}

    /**
     * Display the grading interface with timestamped feedback.
     * This is called when viewing the submission in grading mode.
     *
     * @param stdClass $submission The submission object
     * @param bool $showviewlink Whether to show a link to view the submission
     * @return string The HTML output
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        global $DB;

        try {

            // Use correct field name: submissionid.
            $ytsubmission = $DB->get_record(
                'assignsubmission_ytsubmission',
                ['submissionid' => $submission->id],
                'id, videourl, youtubeid',
                IGNORE_MISSING
            );

            if ($ytsubmission && !empty($ytsubmission->videourl)) {
                // Show the full grading/view interface directly without collapsing.
                $showviewlink = false;
                return $this->view($submission);
            }

            return get_string('nosubmission', 'assignsubmission_ytsubmission');
        } catch (\Exception $e) {
            return get_string('errorloading', 'assignsubmission_ytsubmission');
        }
    }

    /**
     * Check if the plugin has a custom grading interface.
     *
     * @return bool True if the plugin has a custom grading interface
     */
    public function has_user_summary() {
        return true;
    }

    /**
     * Render a single comment.
     *
     * @param stdClass $comment The comment object
     * @param bool $candelete Whether the user can delete the comment
     * @return string The HTML output
     */
    private function render_comment($comment, $candelete = true) {
        global $DB;

        try {
            // Get grader name.
            $grader = $DB->get_record('user', ['id' => $comment->graderid], '*', MUST_EXIST);
            $gradername = fullname($grader);

            // Format timestamp.
            $hours = floor($comment->timestamp / 3600);
            $minutes = floor(($comment->timestamp % 3600) / 60);
            $seconds = $comment->timestamp % 60;

            if ($hours > 0) {
                $timestring = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            } else {
                $timestring = sprintf('%02d:%02d', $minutes, $seconds);
            }

            // Determine comment type and color.
            $commenttype = !empty($comment->commenttype) ? $comment->commenttype : 'general';
            $typeinfo = self::COMMENT_TYPES[$commenttype] ?? self::COMMENT_TYPES['general'];

            $output = html_writer::start_div('ytsubmission-comment-item card mb-2',
                ['id' => 'ytsubmission-comment-' . $comment->id]);
            $output .= html_writer::start_div('card-body');

            $output .= html_writer::start_div('d-flex justify-content-between align-items-start');
            $output .= html_writer::start_div('flex-grow-1');

            // Timestamp link with category color.
            $output .= html_writer::link('#',
                html_writer::tag('i', '', ['class' => 'fa fa-clock-o']) . ' ' . $timestring,
                [
                    'class' => 'ytsubmission-timestamp-link badge text-white me-1 ytsubmission-badge-' . $commenttype,
                    'data-timestamp' => $comment->timestamp,
                    'style' => 'background-color:' . $typeinfo['color'],
                ]
            );

            // Category label badge.
            $output .= html_writer::tag('span',
                get_string('commenttype_' . $commenttype, 'assignsubmission_ytsubmission'),
                [
                    'class' => 'badge me-2 ytsubmission-badge-' . $commenttype,
                    'style' => 'background-color:' . $typeinfo['color'] . ';color:#fff;',
                ]
            );

            // Grader name and time.
            $output .= html_writer::tag('small',
                $gradername . ' - ' . userdate($comment->timecreated),
                ['class' => 'text-muted']
            );

            $output .= html_writer::end_div(); // End flex-grow-1.

            // Delete button (only for graders).
            if ($candelete) {
                $output .= html_writer::tag('button',
                    html_writer::tag('i', '', ['class' => 'fa fa-trash']),
                    [
                        'class' => 'btn btn-sm btn-danger ytsubmission-delete-comment',
                        'data-commentid' => $comment->id
                    ]
                );
            }

            $output .= html_writer::end_div(); // End d-flex.

            // Comment text — rewrite pluginfile URLs for embedded media.
            $context = $this->assignment->get_context();
            $commenttext = file_rewrite_pluginfile_urls(
                $comment->comment,
                'pluginfile.php',
                $context->id,
                'assignsubmission_ytsubmission',
                'commentfiles',
                $comment->id
            );
            $output .= html_writer::div(
                format_text($commenttext, FORMAT_HTML, ['context' => $context]),
                'mt-2 mb-0'
            );

            $output .= html_writer::end_div(); // End card-body.
            $output .= html_writer::end_div(); // End comment-item.

            return $output;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if a submission exists.
     *
     * @param stdClass $submission The submission object
     * @return bool True if a submission exists
     */
    public function is_empty(stdClass $submission) {
        global $DB;

        try {

            // Use correct field name: submissionid.
            $ytsubmission = $DB->get_record(
                'assignsubmission_ytsubmission',
                ['submissionid' => $submission->id],
                'id, videourl, youtubeid',
                IGNORE_MISSING
            );

            $isempty = empty($ytsubmission) || empty($ytsubmission->videourl);

            return $isempty;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Get file areas for this plugin.
     *
     * @return array Array of file area names
     */
    public function get_file_areas() {
        return ['commentfiles' => get_string('commentfiles', 'assignsubmission_ytsubmission')];
    }

    /**
     * Copy submission data from one submission to another.
     *
     * @param stdClass $sourcesubmission The source submission
     * @param stdClass $destsubmission The destination submission
     * @return bool True if successful
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        try {

            // Get the source YouTube submission (using correct field name: submissionid).
            $sourceyt = $DB->get_record(
                'assignsubmission_ytsubmission',
                ['submissionid' => $sourcesubmission->id],
                '*',
                IGNORE_MISSING
            );

            if (!$sourceyt) {
                return true;
            }

            // Create a copy for the destination submission.
            $destyt = clone $sourceyt;
            unset($destyt->id);
            $destyt->submissionid = $destsubmission->id;  // Correct field name.
            $destyt->timemodified = time();

            try {
                $newid = $DB->insert_record('assignsubmission_ytsubmission', $destyt);
            } catch (\Exception $e) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Removes a YouTube submission record safely.
     *
     * @param stdClass $submission The submission record.
     * @return bool True on success.
     * @throws moodle_exception if a DB error occurs.
     */
    public function remove($submission) {
        global $DB;


        try {
            // Check if there is a record to delete (using correct field name: submissionid).
            $exists = $DB->record_exists('assignsubmission_ytsubmission', ['submissionid' => $submission->id]);

            if (!$exists) {
                return true; // Nothing to delete, not an error.
            }

            $DB->delete_records('assignsubmission_ytsubmission', ['submissionid' => $submission->id]);
            return true;

        } catch (\Exception $e) {
            throw new \moodle_exception('errordeleting', 'assignsubmission_ytsubmission', '', null, $e->getMessage());
        }
    }

    /**
     * Trigger the assessable_uploaded event.
     *
     * @param stdClass $submission The submission object
     * @param string $youtubeurl The YouTube URL
     * @return void
     */
    private function trigger_assessable_uploaded_event(stdClass $submission, $youtubeurl) {
        try {

            $params = [
                'context' => $this->assignment->get_context(),
                'objectid' => $submission->id,
                'other' => [
                    'content' => $youtubeurl,
                    'pathnamehashes' => []
                ]
            ];


            $event = \assignsubmission_ytsubmission\event\assessable_uploaded::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();

        } catch (\Exception $e) {
            // Log the error but don't fail the submission.
        }
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        // This is a new plugin, no upgrade path needed.
        return false;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The submission object
     * @return string The log info
     */
    public function format_for_log(stdClass $submission) {
        global $DB;

        try {

            // Use correct field name: submissionid.
            $ytsubmission = $DB->get_record(
                'assignsubmission_ytsubmission',
                ['submissionid' => $submission->id],
                'videourl',
                IGNORE_MISSING
            );

            if ($ytsubmission && !empty($ytsubmission->videourl)) {
                return 'YouTube URL: ' . $ytsubmission->videourl;
            }

            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Return true if there are no submission files.
     * This is called BEFORE save() to determine if there's anything to save.
     *
     * @param stdClass $submission The submission object
     * @return bool True if empty
     */
    public function submission_is_empty(stdClass $submission) {
        global $DB;


        try {
            // First check if there's data in the database (using correct field name: submissionid).
            $ytsubmission = $DB->get_record(
                'assignsubmission_ytsubmission',
                ['submissionid' => $submission->id],
                'id, videourl, youtubeid',
                IGNORE_MISSING
            );

            if ($ytsubmission && !empty($ytsubmission->videourl)) {
                return false;
            }

            // If no database record, check if there's form data being submitted.
            // We need to check the current request for the YouTube URL field.
            $youtubeurl = optional_param('assignsubmission_ytsubmission_youtubeurl', '', PARAM_URL);


            if (!empty($youtubeurl)) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            return true;
        }
    }
}