@extends('tyro-login::layouts.auth')

@section('content')
<div class="auth-container {{ $layout }}" @if($layout==='fullscreen') style="background-image: url('{{ $backgroundImage }}');" @endif @if($layout==='youtube-video') id="tyro-youtube-container" @endif>
    @if(in_array($layout, ['split-left', 'split-right']))
    <div class="background-panel" style="background-image: url('{{ $backgroundImage }}');">
        <div class="background-panel-content">
            <h1>{{ $title }}</h1>
            <p>{{ $subtitle }}</p>
        </div>
    </div>
    @endif

    <div class="form-panel">
        <div class="form-card">
            <!-- Header -->
            <div class="form-header">
                <h2>{{ $title }}</h2>
                <p>{{ $subtitle }}</p>
            </div>

            @if(session('success'))
            <div class="alert alert-success" style="padding: 0.875rem 1rem; margin-bottom: 1.5rem; background-color: #d1fae5; border: 1px solid #6ee7b7; border-radius: 0.5rem; color: #065f46; font-size: 0.9375rem;">
                {{ session('success') }}
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-error" style="padding: 0.875rem 1rem; margin-bottom: 1.5rem; background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem; color: #991b1b; font-size: 0.9375rem;">
                {{ $errors->first() }}
            </div>
            @endif

            @if($passkeys->isNotEmpty())
            <ul class="passkey-list">
                @foreach($passkeys as $passkey)
                <li class="passkey-item">
                    <div class="passkey-meta">
                        <span class="passkey-icon-sm" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="8" cy="15" r="4"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.85 12.15 19 4"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 5l2 2"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15 8 2 2"></path>
                            </svg>
                        </span>
                        <div class="passkey-text">
                            <span class="passkey-name">{{ $passkey->name ?: 'Unnamed passkey' }}</span>
                            <span class="passkey-sub">
                                @if($passkey->authenticator)
                                    {{ $passkey->authenticator }} ·
                                @endif
                                Added {{ $passkey->created_at?->format('M j, Y') }}
                                @if($passkey->last_used_at)
                                    · Last used {{ $passkey->last_used_at->format('M j, Y') }}
                                @else
                                    · Never used
                                @endif
                            </span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('tyro-login.passkeys.destroy', ['id' => $passkey->getKey()]) }}" onsubmit="return confirm('Remove this passkey? You will not be able to sign in with it afterwards.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="passkey-remove-btn">{{ $removeButtonText }}</button>
                    </form>
                </li>
                @endforeach
            </ul>
            @else
            <div class="passkey-empty">
                <p>{{ $emptyText }}</p>
            </div>
            @endif

            <div class="passkey-actions">
                <a href="{{ route('tyro-login.passkeys.setup') }}" class="btn btn-primary">Add a new passkey</a>
                <a href="{{ $afterLoginUrl }}" class="form-link">Back to app</a>
            </div>
        </div>
    </div>
</div>

@include('tyro-login::partials.backgrounds')

<style>
    .passkey-list {
        list-style: none;
        margin: 0 0 1.5rem;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .passkey-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background-color: var(--muted);
    }
    .passkey-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }
    .passkey-icon-sm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        flex-shrink: 0;
        border-radius: 0.5rem;
        background-color: var(--background);
        border: 1px solid var(--border);
        color: var(--foreground);
    }
    .passkey-icon-sm svg { width: 1.125rem; height: 1.125rem; }
    .passkey-text { display: flex; flex-direction: column; min-width: 0; }
    .passkey-name {
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--foreground);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .passkey-sub {
        font-size: 0.8125rem;
        color: var(--muted-foreground);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .passkey-remove-btn {
        flex-shrink: 0;
        padding: 0.5rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 500;
        font-family: inherit;
        border-radius: 0.5rem;
        border: 1px solid var(--destructive);
        background-color: transparent;
        color: var(--destructive);
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .passkey-remove-btn:hover {
        background-color: color-mix(in srgb, var(--destructive), transparent 90%);
    }
    .passkey-empty {
        text-align: center;
        padding: 1.5rem 1rem;
        color: var(--muted-foreground);
        font-size: 0.9375rem;
    }
    .passkey-actions {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        margin-top: 0.5rem;
    }
    .passkey-actions .btn { width: 100%; }
</style>
@endsection
