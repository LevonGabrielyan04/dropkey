@if (config('broadcasting.default') === 'reverb' && filled(config('broadcasting.connections.reverb.key')))
    @php
        $client = config('broadcasting.connections.reverb.client');

        $reverbClientConfig = filled($client['host'] ?? null) ? [
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => $client['host'],
            'port' => (int) ($client['port'] ?? 443),
            'scheme' => $client['scheme'] ?? 'https',
        ] : null;
    @endphp

    @if ($reverbClientConfig !== null)
        <script nonce="{{ Illuminate\Support\Facades\Vite::cspNonce() }}">
            window.__reverbConfig = @json($reverbClientConfig);
        </script>
    @endif
@endif
