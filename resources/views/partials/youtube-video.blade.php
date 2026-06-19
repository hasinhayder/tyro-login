{{-- YouTube video background for 'youtube-video' layout --}}
@php
    $blur = $videoBackground['blur'] ?? '0px';
    $overlayColor = $videoBackground['overlay_color'] ?? '#111827';
    $overlayOpacity = $videoBackground['overlay_opacity'] ?? 0.1;
    $videoUrl = $videoBackground['url'] ?? '';
    $playSound = $videoBackground['sound'] ?? false;

    // Extract video ID from various YouTube URL formats
    $videoId = '';
    if ($videoUrl) {
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/|watch\?.*v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoUrl, $m)) {
            $videoId = $m[1];
        } elseif (preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoUrl)) {
            $videoId = $videoUrl;
        }
    }
@endphp

<div id="tyro-video-background">
    @if($videoId)
    <iframe
        src="https://www.youtube-nocookie.com/embed/{{ $videoId }}?autoplay=1&mute={{ $playSound ? '0' : '1' }}&loop=1&playlist={{ $videoId }}&controls=0&rel=0&playsinline=1&disablekb=1&iv_load_policy=3"
        title="Background"
        allow="autoplay; encrypted-media"
        allowfullscreen
        frameborder="0"
        onload="var f=this;setTimeout(function(){f.style.opacity=1},800)"></iframe>
    @endif
    <div class="tyro-video-overlay" style="background: {{ $overlayColor }}; opacity: {{ $overlayOpacity }}; backdrop-filter: blur({{ $blur }}); -webkit-backdrop-filter: blur({{ $blur }});"></div>
</div>
