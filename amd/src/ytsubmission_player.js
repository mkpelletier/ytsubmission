/**
 * JavaScript for YouTube player with timestamp support
 *
 * @module     assignsubmission_ytsubmission/ytsubmission_player
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/log'], function($, Log) {
    'use strict';

    /**
     * YouTube player object
     */
    var player = null;
    var videoId = null;
    var timestamps = [];

    /**
     * Initialize the YouTube player
     *
     * @param {string} vid The YouTube video ID
     */
    var init = function(vid) {
        videoId = vid;

        // Load the YouTube IFrame API
        if (typeof window.YT === 'undefined' || typeof window.YT.Player === 'undefined') {
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

            // Wait for API to load
            window.onYouTubeIframeAPIReady = function() {
                initPlayer();
            };
        } else {
            initPlayer();
        }

        // Set up timestamp button handler
        setupTimestampControls();
    };

    /**
     * Initialize the YouTube player object
     */
    var initPlayer = function() {
        try {
            var playerElement = document.getElementById('ytsubmission-player-' + videoId);
            if (!playerElement) {
                Log.debug('YouTube player element not found');
                return;
            }

            // Wait for YT to be available
            if (typeof window.YT === 'undefined' || typeof window.YT.Player === 'undefined') {
                Log.debug('YouTube API not yet loaded, waiting...');
                setTimeout(initPlayer, 100);
                return;
            }

            player = new window.YT.Player('ytsubmission-player-' + videoId, {
                events: {
                    'onReady': onPlayerReady,
                    'onError': onPlayerError
                }
            });
        } catch (error) {
            Log.error('Error initializing YouTube player: ' + error.message);
        }
    };

    /**
     * Called when the player is ready
     */
    var onPlayerReady = function() {
        Log.debug('YouTube player ready');
    };

    /**
     * Called when there is a player error
     *
     * @param {object} event The error event
     */
    var onPlayerError = function(event) {
        Log.error('YouTube player error: ' + event.data);
    };

    /**
     * Set up timestamp controls for teachers
     */
    var setupTimestampControls = function() {
        var addTimestampBtn = $('#add-timestamp-btn');

        if (addTimestampBtn.length === 0) {
            // Not a teacher or controls not available
            return;
        }

        // Handle add timestamp button click
        addTimestampBtn.on('click', function() {
            try {
                if (!player || typeof player.getCurrentTime !== 'function') {
                    Log.error('Player not ready');
                    return;
                }

                // Get current video time
                var currentTime = Math.floor(player.getCurrentTime());
                var formattedTime = formatTime(currentTime);

                // Create timestamp entry
                var timestamp = {
                    time: currentTime,
                    formattedTime: formattedTime,
                    note: ''
                };

                // Add to timestamps array
                timestamps.push(timestamp);

                // Render the timestamp in the list
                renderTimestamp(timestamp, timestamps.length - 1);

            } catch (error) {
                Log.error('Error adding timestamp: ' + error.message);
            }
        });
    };

    /**
     * Format time in seconds to MM:SS or HH:MM:SS format
     *
     * @param {number} seconds The time in seconds
     * @return {string} Formatted time string
     */
    var formatTime = function(seconds) {
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;

        if (hours > 0) {
            return hours + ':' + pad(minutes) + ':' + pad(secs);
        } else {
            return minutes + ':' + pad(secs);
        }
    };

    /**
     * Pad a number with leading zero if needed
     *
     * @param {number} num The number to pad
     * @return {string} Padded number string
     */
    var pad = function(num) {
        return (num < 10 ? '0' : '') + num;
    };

    /**
     * Render a timestamp in the timestamp list
     *
     * @param {object} timestamp The timestamp object
     * @param {number} index The index in the timestamps array
     */
    var renderTimestamp = function(timestamp, index) {
        var timestampList = $('#timestamp-list');

        // Create timestamp item HTML
        var timestampHtml = '<div class="timestamp-item" data-index="' + index + '" data-time="' + timestamp.time + '">' +
            '<span class="timestamp-time">' + timestamp.formattedTime + '</span>' +
            '<input type="text" class="timestamp-note-input form-control" placeholder="Add feedback note..." ' +
            'style="display: inline-block; width: 70%; margin-left: 10px;" />' +
            '<button class="btn btn-sm btn-danger delete-timestamp" style="margin-left: 10px;">Delete</button>' +
            '</div>';

        var $timestampItem = $(timestampHtml);
        timestampList.append($timestampItem);

        // Handle click on timestamp to seek video
        $timestampItem.find('.timestamp-time').on('click', function() {
            try {
                if (player && typeof player.seekTo === 'function') {
                    player.seekTo(timestamp.time, true);
                    player.playVideo();
                }
            } catch (error) {
                Log.error('Error seeking video: ' + error.message);
            }
        });

        // Handle note input
        $timestampItem.find('.timestamp-note-input').on('change', function() {
            timestamps[index].note = $(this).val();
            Log.debug('Timestamp note updated: ' + timestamps[index].note);
        });

        // Handle delete button
        $timestampItem.find('.delete-timestamp').on('click', function() {
            timestamps.splice(index, 1);
            $timestampItem.remove();
            // Re-render all timestamps to update indices
            renderAllTimestamps();
        });
    };

    /**
     * Re-render all timestamps (used after deletion)
     */
    var renderAllTimestamps = function() {
        var timestampList = $('#timestamp-list');
        timestampList.empty();

        timestamps.forEach(function(timestamp, index) {
            renderTimestamp(timestamp, index);
        });
    };

    /**
     * Get all timestamps (can be called from grading form)
     *
     * @return {array} Array of timestamp objects
     */
    var getTimestamps = function() {
        return timestamps;
    };

    // Public API
    return {
        init: init,
        getTimestamps: getTimestamps
    };
});