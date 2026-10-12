@php($status = $status ?? 500)
<!doctype html>
<html lang="da">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status }} · Dyhrene</title>
</head>
<body>
    <main>
        <h1>{{ $status === 419 ? 'Din session er udløbet' : 'Siden kunne ikke vises' }}</h1>
        @if ($status === 419)
            <p>Din session kan være udløbet. Hvis du genindlæser siden, kan ikke-gemt arbejde gå tabt.</p>
            <p>Gem eller kopiér eventuelle ændringer, før du prøver igen.</p>
        @else
            <p>Der opstod en fejl. Du kan gå til forsiden, logge ind eller prøve igen.</p>
        @endif
        <nav aria-label="Hjælp">
            <a href="{{ route('index') }}">Gå til forsiden</a>
            <a href="{{ route('login') }}">Log ind</a>
            <a href="{{ url()->current() }}">Prøv igen</a>
        </nav>
    </main>
</body>
</html>
