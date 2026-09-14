<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/font-awesome.css">
    <link rel="stylesheet" href="/css/jquery-ui.css">
    <link rel="stylesheet" href="/css/datetimepicker.min.css">
    <script>
        window.userId = @json(auth()->id());
        window.__wildEdiblesGoogleMapsPending = false;
        window.__wildEdiblesGoogleMapsReady = function () {
            window.__wildEdiblesGoogleMapsPending = true;
        };
        window.Laravel = {
            reverbKey: '{{ env('REVERB_APP_KEY') }}',
            reverbHost: '{{ env('REVERB_HOST') }}',
            reverbScheme: '{{ env('REVERB_SCHEME') }}',
            reverbPort: '{{ env('REVERB_PORT') }}',
        };
    </script>
    <script src="/js/jquery.js"></script>
    <script src="/js/jquery-ui.js"></script>
    <script src="/js/datetimepicker.js"></script>
    <script src="/js/bootstrap-tagsinput.js"></script>
    <script src="/js/jquery.multi-select.js"></script>
    <script src="/js/jquery.select.js"></script>
    <script src="/js/jquery.mobile-1.4.5.min.js"></script>
    <script src="/js/navigation.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    @vite('resources/scss/app.scss')
    @vite('resources/js/app.js')
    <title>@yield('title', 'Default') - Dyhrene</title>
</head>

<body>
    {{ $slot }}
    @stack('scripts')
</body>

</html>