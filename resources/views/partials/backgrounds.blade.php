{{-- Background dispatcher: renders the right animated/static background partial for the active layout --}}
@if($layout === 'youtube-video')
    @include('tyro-login::partials.youtube-video')
@elseif($layout === 'animated-birds')
    @include('tyro-login::partials.animated-birds')
@elseif($layout === 'aurora-waves')
    @include('tyro-login::partials.aurora-waves')
@elseif($layout === 'particle-network')
    @include('tyro-login::partials.particle-network')
@elseif($layout === 'tidal')
    @include('tyro-login::partials.tidal')
@endif
