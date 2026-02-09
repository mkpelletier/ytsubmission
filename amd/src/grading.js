/**
 * JavaScript for YouTube submission grading interface.
 *
 * @module    assignsubmission_ytsubmission/grading
 * @copyright 2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* global YT */
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    /** @var {Object|null} player YouTube player instance */
    var player = null;

    /** @var {string|null} videoId YouTube video identifier */
    var videoId = null;

    /** @var {number|null} submissionId Moodle submission id */
    var submissionId = null;

    /** @var {number|null} assignId Moodle assignment id */
    var assignId = null;

    /** @var {HTMLElement|null} timeDisplayEl Element that shows current time */
    var timeDisplayEl = null;

    /** @var {HTMLInputElement|null} timestampInputEl Hidden input for timestamp */
    var timestampInputEl = null;

    /** @var {number} videoDuration Total video duration in seconds */
    var videoDuration = 0;

    /** @var {HTMLElement|null} timelineContainer The timeline container element */
    var timelineContainer = null;

    /** @var {HTMLElement|null} playheadEl The playhead indicator element */
    var playheadEl = null;

    /** @var {Array} commentsData Array of comment objects for timeline markers */
    var commentsData = [];

    /** @var {Object} commentTypes Map of type key to {label, color} */
    var commentTypes = {};

    /** @var {boolean} readOnly Whether in read-only mode (student view) */
    var readOnly = false;

    /** @var {number} courseId Course ID for library sharing */
    var courseId = 0;

    /** @var {Object|null} libraryCache Cached library data */
    var libraryCache = null;

    /**
     * Load the YouTube IFrame API and create the player.
     */
    function setupPlayer() {
        if (!window.YT) {
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }

        window.onYouTubeIframeAPIReady = function() {
            createPlayer();
        };

        if (window.YT && window.YT.Player) {
            createPlayer();
        }
    }

    /**
     * Helper – actually instantiate the YT.Player.
     */
    function createPlayer() {
        player = new YT.Player('ytsubmission-grading-player', {
            height: '360',
            width: '640',
            videoId: videoId,
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
    }

    /**
     * Player is ready – start the time-update loop.
     */
    function onPlayerReady() {
        videoDuration = player.getDuration();
        initTimeline();
        setInterval(updateCurrentTime, 500);
    }

    /**
     * State change – currently a no-op.
     */
    function onPlayerStateChange() {
        // Intentionally empty.
    }

    /**
     * Update the visible time and the hidden timestamp input.
     */
    function updateCurrentTime() {
        if (player && typeof player.getCurrentTime === 'function') {
            try {
                // Lazy-init duration and timeline if not yet available.
                if (videoDuration <= 0 && typeof player.getDuration === 'function') {
                    videoDuration = player.getDuration();
                    if (videoDuration > 0) {
                        initTimeline();
                    }
                }

                var currentTime = Math.floor(player.getCurrentTime());
                var minutes = Math.floor(currentTime / 60);
                var seconds = currentTime % 60;
                var timeString = pad(minutes) + ':' + pad(seconds);

                if (timeDisplayEl) {
                    timeDisplayEl.textContent = timeString;
                }
                if (timestampInputEl) {
                    timestampInputEl.value = currentTime;
                }

                // Update playhead position on the timeline.
                if (playheadEl && videoDuration > 0) {
                    var ratio = currentTime / videoDuration;
                    var percent = Math.min(Math.max(ratio * 100, 0), 100);
                    playheadEl.style.left = percent + '%';
                }
            } catch (e) {
                // Swallow errors – they are usually transient.
            }
        }
    }

    /**
     * Pad a number with a leading zero.
     *
     * @param {number} num
     * @return {string}
     */
    function pad(num) {
        return (num < 10 ? '0' : '') + num;
    }

    /**
     * Initialize the timeline bar with existing markers.
     */
    function initTimeline() {
        timelineContainer = document.getElementById('ytsubmission-timeline-container');
        playheadEl = document.getElementById('ytsubmission-timeline-playhead');

        if (!timelineContainer || videoDuration <= 0) {
            return;
        }

        // Render initial markers from data passed by PHP.
        commentsData.forEach(function(comment) {
            addTimelineMarker(comment);
        });

        // Click on timeline bar to seek.
        timelineContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('ytsubmission-timeline-marker')) {
                return; // Marker clicks handled separately.
            }
            var rect = timelineContainer.getBoundingClientRect();
            var clickX = e.clientX - rect.left;
            var ratio = clickX / rect.width;
            var seekTime = Math.floor(ratio * videoDuration);
            if (player && typeof player.seekTo === 'function') {
                player.seekTo(seekTime, true);
            }
        });
    }

    /**
     * Add a single marker dot to the timeline.
     *
     * @param {Object} comment Object with id, timestamp, comment, gradername, commenttype
     */
    function addTimelineMarker(comment) {
        if (!timelineContainer || videoDuration <= 0) {
            return;
        }
        var ratio = comment.timestamp / videoDuration;
        var percent = Math.min(Math.max(ratio * 100, 0), 100);
        var type = comment.commenttype || 'general';
        var typeInfo = commentTypes[type] || commentTypes.general || {label: 'General', color: '#6c757d'};

        var marker = document.createElement('div');
        marker.className = 'ytsubmission-timeline-marker ytsubmission-timeline-marker-' + type;
        marker.id = 'ytsubmission-timeline-marker-' + comment.id;
        marker.style.left = percent + '%';
        marker.style.backgroundColor = typeInfo.color;
        marker.style.borderColor = '#fff';
        marker.setAttribute('data-timestamp', comment.timestamp);
        marker.setAttribute('data-commentid', comment.id);

        // Tooltip.
        var tooltip = document.createElement('div');
        tooltip.className = 'ytsubmission-timeline-tooltip';
        tooltip.textContent = '[' + typeInfo.label + '] ' + formatTimestamp(comment.timestamp) + ' - ' + comment.comment;
        marker.appendChild(tooltip);

        // Click to seek.
        marker.addEventListener('click', function(e) {
            e.stopPropagation();
            if (player && typeof player.seekTo === 'function') {
                player.seekTo(comment.timestamp, true);
                player.playVideo();
            }
        });

        timelineContainer.appendChild(marker);
    }

    /**
     * Remove a marker from the timeline.
     *
     * @param {number} commentId
     */
    function removeTimelineMarker(commentId) {
        var marker = document.getElementById('ytsubmission-timeline-marker-' + commentId);
        if (marker) {
            marker.remove();
        }
    }

    /**
     * Format seconds into MM:SS or HH:MM:SS string.
     *
     * @param {number} totalSeconds
     * @return {string}
     */
    function formatTimestamp(totalSeconds) {
        var hours = Math.floor(totalSeconds / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var seconds = totalSeconds % 60;
        if (hours > 0) {
            return pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
        }
        return pad(minutes) + ':' + pad(seconds);
    }

    /**
     * Clicking a timestamp link seeks the video.
     */
    function bindTimestampClicks() {
        $(document).on('click', '.ytsubmission-timestamp-link', function(e) {
            e.preventDefault();
            var timestamp = parseInt($(this).data('timestamp'), 10);
            if (player && typeof player.seekTo === 'function') {
                try {
                    player.seekTo(timestamp, true);
                    player.playVideo();
                } catch (err) {
                    // Ignore seek errors.
                }
            }
        });
    }

    /**
     * Get the comment text from the editor.
     *
     * @return {string} HTML content
     */
    function getEditorContent() {
        var commentText = '';
        if (window.tinymce) {
            var editorInstance = window.tinymce.get('ytsubmission-comment-text');
            if (editorInstance) {
                editorInstance.save();
                commentText = editorInstance.getContent().trim();
            }
        }
        if (!commentText) {
            commentText = $('#ytsubmission-comment-text').val().trim();
        }
        return commentText;
    }

    /**
     * Set the comment text in the editor.
     *
     * @param {string} html HTML content
     */
    function setEditorContent(html) {
        if (window.tinymce) {
            var editor = window.tinymce.get('ytsubmission-comment-text');
            if (editor) {
                editor.setContent(html);
                return;
            }
        }
        $('#ytsubmission-comment-text').val(html);
    }

    /**
     * Clear the editor content.
     */
    function clearEditor() {
        if (window.tinymce) {
            var editor = window.tinymce.get('ytsubmission-comment-text');
            if (editor) {
                editor.setContent('');
            }
        }
        $('#ytsubmission-comment-text').val('');
    }

    /**
     * Add-comment button handler.
     */
    function bindAddComment() {
        $('#ytsubmission-add-comment-btn').on('click', function(e) {
            e.preventDefault();

            var commentText = getEditorContent();

            // Strip HTML tags to check if truly empty (editor may produce empty markup).
            var plainText = commentText.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, '').trim();
            var timestamp = parseInt($('#ytsubmission-timestamp-input').val(), 10);
            var commenttype = $('#ytsubmission-commenttype').val() || 'general';
            var draftitemid = parseInt($('#ytsubmission-draftitemid').val(), 10) || 0;

            // Sync with player current time
            if (player && typeof player.getCurrentTime === 'function') {
                try {
                    timestamp = Math.floor(player.getCurrentTime());
                } catch (err) {
                    // Keep input value
                }
            }

            if (!plainText) {
                Notification.alert('Error', 'Please enter a comment.', 'OK');
                return;
            }

            var promises = Ajax.call([{
                methodname: 'assignsubmission_ytsubmission_add_comment',
                args: {
                    submissionid: submissionId,
                    assignmentid: assignId,
                    timestamp: timestamp,
                    comment: commentText,
                    commenttype: commenttype,
                    draftitemid: draftitemid
                }
            }]);

            promises[0].done(function(response) {
                if (response.success) {
                    // Clear form.
                    clearEditor();
                    $('#ytsubmission-timestamp-input').val('0');
                    $('#ytsubmission-commenttype').val('general');
                    // Draft area is consumed; reset to 0.
                    $('#ytsubmission-draftitemid').val('0');

                    // Append new comment.
                    var newComment = response.comment;
                    var $commentHtml = $(renderComment(newComment));

                    var $list = $('#ytsubmission-comments-list');
                    if ($list.children('p.text-muted').length > 0) {
                        $list.empty(); // Remove "No comments yet"
                    }
                    $list.append($commentHtml);

                    // Auto-scroll to new comment.
                    $('html, body').animate({
                        scrollTop: $commentHtml.offset().top - 100
                    }, 500);

                    // Add marker to timeline.
                    addTimelineMarker({
                        id: newComment.id,
                        timestamp: newComment.timestamp,
                        comment: newComment.comment.replace(/<[^>]*>/g, '').substring(0, 80),
                        commenttype: newComment.commenttype || 'general',
                        gradername: newComment.gradername
                    });

                    // Flash effect.
                    $commentHtml.hide().fadeIn(400);
                } else {
                    Notification.alert('Error', response.message || 'Failed to add comment.', 'OK');
                }
            }).fail(function(error) {
                Notification.exception(error);
            });
        });
    }

    /**
     * Delete-comment handler.
     */
    function bindDeleteHandlers() {
        $(document).on('click', '.ytsubmission-delete-comment', function(e) {
            e.preventDefault();

            var commentId = parseInt($(this).data('commentid'), 10);
            var commentElement = $('#ytsubmission-comment-' + commentId);

            if (!confirm('Are you sure you want to delete this comment?')) {
                return;
            }

            var promises = Ajax.call([{
                methodname: 'assignsubmission_ytsubmission_delete_comment',
                args: {
                    commentid: commentId
                }
            }]);

            promises[0].done(function(response) {
                if (response.success) {
                    // Remove marker from timeline.
                    removeTimelineMarker(commentId);

                    commentElement.fadeOut(300, function() {
                        $(this).remove();
                        if ($('.ytsubmission-comment-item').length === 0) {
                            $('#ytsubmission-comments-list').html(
                                '<p class="text-muted">No comments yet.</p>'
                            );
                        }
                    });
                } else {
                    Notification.alert('Error', response.message || 'Failed to delete comment.', 'OK');
                }
            }).fail(function(error) {
                Notification.exception(error);
            });
        });
    }

    /**
     * Render a comment object into HTML (client-side version of PHP render_comment).
     *
     * @param {Object} comment
     * @return {string}
     */
    function renderComment(comment) {
        var hours = Math.floor(comment.timestamp / 3600);
        var minutes = Math.floor((comment.timestamp % 3600) / 60);
        var seconds = comment.timestamp % 60;
        var timeStr = (hours > 0 ?
            ('0' + hours).slice(-2) + ':' : '') +
            ('0' + minutes).slice(-2) + ':' +
            ('0' + seconds).slice(-2);

        var type = comment.commenttype || 'general';
        var typeInfo = commentTypes[type] || commentTypes.general || {label: 'General', color: '#6c757d'};

        var html = '<div class="ytsubmission-comment-item card mb-2" id="ytsubmission-comment-' + comment.id + '">';
        html += '<div class="card-body">';
        html += '<div class="d-flex justify-content-between align-items-start">';
        html += '<div class="flex-grow-1">';
        html += '<a href="#" class="ytsubmission-timestamp-link badge text-white me-1 ytsubmission-badge-' + type + '" ' +
                'data-timestamp="' + comment.timestamp + '" style="background-color:' + typeInfo.color + '">';
        html += '<i class="fa fa-clock-o"></i> ' + timeStr + '</a>';
        html += '<span class="badge me-2 ytsubmission-badge-' + type + '" ' +
                'style="background-color:' + typeInfo.color + ';color:#fff;">' + typeInfo.label + '</span>';
        html += '<small class="text-muted">' + comment.gradername + ' - ' + comment.timecreated + '</small>';
        html += '</div>';
        if (!readOnly) {
            html += '<button class="btn btn-sm btn-danger ytsubmission-delete-comment" data-commentid="' + comment.id + '">';
            html += '<i class="fa fa-trash"></i></button>';
        }
        html += '</div>';
        html += '<div class="mt-2 mb-0">' + comment.comment + '</div>';
        html += '</div></div>';
        return html;
    }

    // ========================
    // Comment Library Functions
    // ========================

    /**
     * Bind library button handlers.
     */
    function bindLibrary() {
        // Insert from library button.
        $('#ytsubmission-library-insert-btn').on('click', function(e) {
            e.preventDefault();
            toggleLibraryPanel('insert');
        });

        // Save to library button.
        $('#ytsubmission-library-save-btn').on('click', function(e) {
            e.preventDefault();
            saveToLibrary();
        });

        // Delegate clicks inside the library panel.
        $(document).on('click', '.ytsubmission-library-item-insert', function(e) {
            e.preventDefault();
            var text = $(this).closest('.ytsubmission-library-item').data('commenttext');
            var type = $(this).closest('.ytsubmission-library-item').data('commenttype');
            insertLibraryItem(text, type);
        });

        $(document).on('click', '.ytsubmission-library-item-delete', function(e) {
            e.preventDefault();
            var itemId = parseInt($(this).data('itemid'), 10);
            deleteLibraryItem(itemId);
        });

        // Filter pills inside library panel.
        $(document).on('click', '.ytsubmission-library-filter', function(e) {
            e.preventDefault();
            var filterType = $(this).data('filtertype');
            $('.ytsubmission-library-filter').removeClass('active');
            $(this).addClass('active');
            filterLibraryItems(filterType);
        });

        // Search box inside library panel.
        $(document).on('input', '#ytsubmission-library-search', function() {
            var query = $(this).val().toLowerCase();
            filterLibraryItems($('.ytsubmission-library-filter.active').data('filtertype') || 'all', query);
        });

        // Close button.
        $(document).on('click', '#ytsubmission-library-close', function(e) {
            e.preventDefault();
            $('#ytsubmission-library-panel').slideUp(200);
        });
    }

    /**
     * Toggle the library panel open/closed.
     */
    function toggleLibraryPanel() {
        var $panel = $('#ytsubmission-library-panel');
        if ($panel.is(':visible')) {
            $panel.slideUp(200);
            return;
        }

        // Fetch library data (use cache if available).
        if (libraryCache) {
            renderLibraryPanel(libraryCache);
            $panel.slideDown(200);
            return;
        }

        var promises = Ajax.call([{
            methodname: 'assignsubmission_ytsubmission_get_library',
            args: {
                assignmentid: assignId,
                courseid: courseId
            }
        }]);

        promises[0].done(function(response) {
            libraryCache = response;
            renderLibraryPanel(response);
            $panel.slideDown(200);
        }).fail(function(error) {
            Notification.exception(error);
        });
    }

    /**
     * Render the library panel content.
     *
     * @param {Object} data Object with personal[] and shared[] arrays
     */
    function renderLibraryPanel(data) {
        var html = '<div class="ytsubmission-library-inner p-3">';

        // Header with close button.
        html += '<div class="d-flex justify-content-between align-items-center mb-3">';
        html += '<h5 class="mb-0">Comment Library</h5>';
        html += '<button id="ytsubmission-library-close" class="btn btn-sm btn-outline-secondary">' +
                '<i class="fa fa-times"></i></button>';
        html += '</div>';

        // Search box.
        html += '<input type="text" id="ytsubmission-library-search" class="form-control form-control-sm mb-2" ' +
                'placeholder="Search comments...">';

        // Filter pills.
        html += '<div class="mb-3">';
        html += '<span class="badge bg-secondary ytsubmission-library-filter active me-1" ' +
                'data-filtertype="all" role="button">All</span>';
        Object.keys(commentTypes).forEach(function(key) {
            var t = commentTypes[key];
            html += '<span class="badge ytsubmission-library-filter me-1" ' +
                    'data-filtertype="' + key + '" role="button" ' +
                    'style="background-color:' + t.color + ';color:#fff;cursor:pointer;">' + t.label + '</span>';
        });
        html += '</div>';

        // Personal section.
        html += '<div class="ytsubmission-library-section">';
        html += '<h6>My Comments</h6>';
        if (data.personal.length === 0) {
            html += '<p class="text-muted small">No personal comments saved.</p>';
        } else {
            data.personal.forEach(function(item) {
                html += renderLibraryItem(item, true);
            });
        }
        html += '</div>';

        // Shared section.
        if (courseId > 0) {
            html += '<hr>';
            html += '<div class="ytsubmission-library-section">';
            html += '<h6>Course Comments</h6>';
            if (data.shared.length === 0) {
                html += '<p class="text-muted small">No shared comments yet.</p>';
            } else {
                data.shared.forEach(function(item) {
                    html += renderLibraryItem(item, item.isowner);
                });
            }
            html += '</div>';
        }

        html += '</div>';

        $('#ytsubmission-library-panel').html(html);
    }

    /**
     * Render a single library item.
     *
     * @param {Object} item Library item data
     * @param {boolean} canDelete Whether delete button should show
     * @return {string} HTML
     */
    function renderLibraryItem(item, canDelete) {
        var type = item.commenttype || 'general';
        var typeInfo = commentTypes[type] || commentTypes.general || {label: 'General', color: '#6c757d'};
        var plainText = item.commenttext.replace(/<[^>]*>/g, '').substring(0, 120);

        var html = '<div class="ytsubmission-library-item d-flex align-items-start p-2 mb-1" ' +
                   'data-commenttext="' + escapeAttr(item.commenttext) + '" ' +
                   'data-commenttype="' + type + '" data-itemid="' + item.id + '">';
        html += '<span class="badge me-2" style="background-color:' + typeInfo.color +
                ';color:#fff;min-width:75px;font-size:0.7em;">' + typeInfo.label + '</span>';
        html += '<span class="flex-grow-1 small ytsubmission-library-item-insert" role="button">' + plainText + '</span>';
        if (canDelete) {
            html += '<button class="btn btn-sm btn-outline-danger ms-2 ytsubmission-library-item-delete" ' +
                    'data-itemid="' + item.id + '"><i class="fa fa-trash"></i></button>';
        }
        html += '</div>';
        return html;
    }

    /**
     * Escape a string for use in an HTML attribute.
     *
     * @param {string} str
     * @return {string}
     */
    function escapeAttr(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML.replace(/"/g, '&quot;');
    }

    /**
     * Insert a library item into the editor and set the category dropdown.
     *
     * @param {string} text HTML content
     * @param {string} type Comment type key
     */
    function insertLibraryItem(text, type) {
        setEditorContent(text);
        if (type) {
            $('#ytsubmission-commenttype').val(type);
        }
        $('#ytsubmission-library-panel').slideUp(200);
    }

    /**
     * Save the current editor content to the library.
     */
    function saveToLibrary() {
        var commentText = getEditorContent();
        var plainText = commentText.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, '').trim();

        if (!plainText) {
            Notification.alert('Error', 'Please enter a comment to save.', 'OK');
            return;
        }

        var commenttype = $('#ytsubmission-commenttype').val() || 'general';

        // Show scope selection modal.
        var scopeHtml = '<div class="p-3">';
        scopeHtml += '<p>Save this comment to:</p>';
        scopeHtml += '<button class="btn btn-primary me-2 ytsubmission-save-scope" data-scope="personal">My Library</button>';
        if (courseId > 0) {
            scopeHtml += '<button class="btn btn-outline-primary ytsubmission-save-scope" data-scope="course">' +
                         'Course Library</button>';
        }
        scopeHtml += '</div>';

        // Use a simple approach: prepend a temporary panel.
        var $savePanel = $('<div class="ytsubmission-save-scope-panel card p-2 mb-2">' + scopeHtml + '</div>');
        $('#ytsubmission-library-save-btn').after($savePanel);

        $savePanel.find('.ytsubmission-save-scope').on('click', function() {
            var scope = $(this).data('scope');
            var targetCourseId = (scope === 'course') ? courseId : 0;
            $savePanel.remove();

            var promises = Ajax.call([{
                methodname: 'assignsubmission_ytsubmission_save_library_comment',
                args: {
                    assignmentid: assignId,
                    commenttext: commentText,
                    commenttype: commenttype,
                    courseid: targetCourseId,
                    itemid: 0
                }
            }]);

            promises[0].done(function(response) {
                if (response.success) {
                    libraryCache = null; // Invalidate cache.
                    Notification.alert('Saved', 'Comment saved to library.', 'OK');
                }
            }).fail(function(error) {
                Notification.exception(error);
            });
        });

        // Auto-remove after 10 seconds.
        setTimeout(function() {
            $savePanel.remove();
        }, 10000);
    }

    /**
     * Delete a library item.
     *
     * @param {number} itemId
     */
    function deleteLibraryItem(itemId) {
        if (!confirm('Delete this library comment?')) {
            return;
        }

        var promises = Ajax.call([{
            methodname: 'assignsubmission_ytsubmission_delete_library_comment',
            args: {
                assignmentid: assignId,
                itemid: itemId
            }
        }]);

        promises[0].done(function(response) {
            if (response.success) {
                libraryCache = null; // Invalidate cache.
                // Remove from DOM.
                $('.ytsubmission-library-item[data-itemid="' + itemId + '"]').fadeOut(200, function() {
                    $(this).remove();
                });
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    }

    /**
     * Filter library items by type and search query.
     *
     * @param {string} filterType Type key or 'all'
     * @param {string} [query] Search query string
     */
    function filterLibraryItems(filterType, query) {
        query = query || $('#ytsubmission-library-search').val().toLowerCase();
        $('.ytsubmission-library-item').each(function() {
            var $item = $(this);
            var itemType = $item.data('commenttype');
            var itemText = $item.text().toLowerCase();
            var typeMatch = (filterType === 'all' || itemType === filterType);
            var textMatch = (!query || itemText.indexOf(query) !== -1);
            $item.toggle(typeMatch && textMatch);
        });
    }

    /**
     * Public init function – called from PHP renderer.
     *
     * @param {Object} data - Initialization data
     * @param {string} data.videoId - YouTube video ID
     * @param {number} data.submissionId - Moodle submission ID
     * @param {number} data.assignId - Moodle assignment ID
     * @param {Array} data.comments - Existing comments
     * @param {Object} data.commentTypes - Comment type definitions
     * @param {boolean} data.readOnly - Whether in read-only mode
     * @param {number} data.courseId - Course ID for library
     */
    function init(data) {
        videoId = data.videoId;
        submissionId = data.submissionId;
        assignId = data.assignId;
        commentsData = data.comments || [];
        commentTypes = data.commentTypes || {};
        readOnly = !!data.readOnly;
        courseId = data.courseId || 0;

        if (!readOnly && !assignId) {
            Notification.alert('Error', 'Assignment ID missing. Cannot add comments.', 'OK');
            return;
        }

        timeDisplayEl = document.getElementById('ytsubmission-current-time');
        timestampInputEl = document.getElementById('ytsubmission-timestamp-input');

        setupPlayer();
        bindTimestampClicks();

        if (!readOnly) {
            bindAddComment();
            bindDeleteHandlers();
            bindLibrary();
        }
    }

    return {
        init: init
    };
});
