{{-- YouTube video background for 'youtube-video' layout --}}
@php
    $blur = $videoBackground['blur'] ?? '4px';
    $overlayColor = $videoBackground['overlay_color'] ?? 'rgba(17, 24, 39, 0.85)';
    $overlayOpacity = $videoBackground['overlay_opacity'] ?? 0.85;
    $videoUrl = $videoBackground['url'] ?? '';
@endphp

<div id="tyro-video-background">
    <div id="tyro-youtube-player" data-video-url="{{ $videoUrl }}"></div>
    <div class="tyro-video-overlay" style="background-color: {{ $overlayColor }}; opacity: {{ $overlayOpacity }}; backdrop-filter: blur({{ $blur }}); -webkit-backdrop-filter: blur({{ $blur }});"></div>
</div>

<script>
    (function() {
        'use strict';

        var videoUrl = document.getElementById('tyro-youtube-player').getAttribute('data-video-url');

        /**
         * Extract YouTube video ID from various URL formats.
         */
        function extractYouTubeId(url) {
            if (!url) return null;

            // Handle direct video ID (11 character string)
            if (/^[a-zA-Z0-9_-]{11}$/.test(url)) {
                return url;
            }

            var patterns = [
                /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/|youtube\.com\/watch\?.*v=)([a-zA-Z0-9_-]{11})/,
                /^https?:\/\/(?:www\.)?youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/,
                /^https?:\/\/youtu\.be\/([a-zA-Z0-9_-]{11})/,
                /^https?:\/\/(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
            ];

            for (var i = 0; i < patterns.length; i++) {
                var match = url.match(patterns[i]);
                if (match && match[1]) {
                    return match[1];
                }
            }

            return null;
        }

        var videoId = extractYouTubeId(videoUrl);

        // If no valid video ID, show fallback (solid dark background)
        if (!videoId) {
            var container = document.getElementById('tyro-video-background');
            if (container) {
                container.style.backgroundColor = '#000';
            }
            return;
        }

        // YouTube IFrame API state
        var player = null;
        var playerReady = false;

        /**
         * Resize the iframe to cover the viewport (like background-size: cover).
         */
        function resizePlayer() {
            var playerEl = document.getElementById('tyro-youtube-player');
            if (!playerEl) return;

            var windowWidth = window.innerWidth;
            var windowHeight = window.innerHeight;
            var aspectRatio = 16 / 9;

            var videoWidth = windowWidth;
            var videoHeight = windowWidth / aspectRatio;

            if (videoHeight < windowHeight) {
                videoHeight = windowHeight;
                videoWidth = windowHeight * aspectRatio;
            }

            playerEl.style.width = videoWidth + 'px';
            playerEl.style.height = videoHeight + 'px';
            playerEl.style.left = ((windowWidth - videoWidth) / 2) + 'px';
            playerEl.style.top = ((windowHeight - videoHeight) / 2) + 'px';
        }

        /**
         * Create the YouTube player once the API is ready.
         */
        window.onYouTubeIframeAPIReady = function() {
            player = new YT.Player('tyro-youtube-player', {
                videoId: videoId,
                playerVars: {
                    autoplay: 1,
                    mute: 1,
                    loop: 1,
                    playlist: videoId,
                    controls: 0,
                    showinfo: 0,
                    rel: 0,
                    disablekb: 1,
                    modestbranding: 1,
                    playsinline: 1,
                    enablejsapi: 1,
                },
                events: {
                    onReady: function(event) {
                        playerReady = true;
                        event.target.mute();
                        event.target.playVideo();
                        resizePlayer();
                    },
                    onStateChange: function(event) {
                        // If video ends, replay it (loop)
                        if (event.data === YT.PlayerState.ENDED) {
                            event.target.playVideo();
                        }
                    },
                },
            });
        };

        // Load the YouTube IFrame API
        var tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        // Resize player on window resize
        window.addEventListener('resize', function() {
            if (playerReady) {
                resizePlayer();
            }
        });

        // Fallback: If the API doesn't load within 10 seconds, show dark background
        var fallbackTimer = setTimeout(function() {
            if (!playerReady) {
                var container = document.getElementById('tyro-video-background');
                if (container) {
                    container.style.backgroundColor = '#000';
                }
            }
        }, 10000);

        // Clear fallback timer if API loads
        var originalOnReady = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = function() {
            clearTimeout(fallbackTimer);
            if (originalOnReady) originalOnReady();
        };
    })();
</script>
