@extends('tyro-login::layouts.auth')

@section('content')
<div class="auth-container {{ $layout }}" @if($layout==='fullscreen' ) style="background-image: url('{{ $backgroundImage }}');" @endif @if($layout==='youtube-video') id="tyro-youtube-container" @endif>
    @if(in_array($layout, ['split-left', 'split-right']))
    <div class="background-panel" style="background-image: url('{{ $backgroundImage }}');">
        <div class="background-panel-content">
            <h1>Recovery Codes</h1>
            <p>Store these recovery codes in a secure place.</p>
        </div>
    </div>
    @endif

    <div class="form-panel">
        <div class="form-card" style="max-width: 500px">
            <div class="form-header">
                <h2>Recovery Codes</h2>
                <p>Store these codes in a secure place. You can use them to access your account if you lose your authentication device.</p>
            </div>

            <div class="recovery-codes-container">
                @foreach($recoveryCodes as $code)
                <div class="recovery-code">{{ $code }}</div>
                @endforeach
            </div>

            <div class="actions mt-6 flex gap-4">
                 <button type="button" onclick="copyCodes()" class="btn btn-secondary w-full">
                    Copy Codes
                </button>
                 <button type="button" onclick="downloadCodes()" class="btn btn-secondary w-full">
                    Download
                </button>
            </div>

            <div class="mt-6">
                <form method="POST" action="{{ route('tyro-login.two-factor.skip') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-full text-center">
                        Finish
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@include('tyro-login::partials.backgrounds')

<style>
    .recovery-codes-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        background: var(--muted);
        padding: 1rem;
        border-radius: 0.5rem;
        margin: 1.5rem 0;
        border: 1px solid var(--border);
    }
    .recovery-code {
        background: var(--background);
        padding: 0.5rem;
        text-align: center;
        border-radius: 0.375rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.875rem;
        border: 1px solid var(--border);
        color: var(--foreground);
    }
    .flex { display: flex; }
    .gap-4 { gap: 1rem; }
    .w-full { width: 100%; }
    .mt-6 { margin-top: 1.5rem; }
</style>

<script>
    const codes = @json($recoveryCodes);

    function copyCodes() {
        const text = codes.join('\n');
        navigator.clipboard.writeText(text).then(() => {
            alert('Codes copied to clipboard');
        });
    }

    function downloadCodes() {
        const text = codes.join('\n');
        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', 'recovery-codes.txt');
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }
</script>
@endsection
