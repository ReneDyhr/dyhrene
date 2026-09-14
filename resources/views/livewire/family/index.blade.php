<x-layouts.app-shell :area="\App\Enums\AppArea::Family">
    <section class="landing" aria-labelledby="family-heading">
        <h1 id="family-heading" class="landing__heading">Familien</h1>

        <ul class="landing-links">
            <li><a class="landing-links__link" href="{{ route('mail.inbox') }}" wire:navigate>Mail</a></li>
            <li><a class="landing-links__link" href="{{ route('settings.mcp') }}" wire:navigate>MCP-forbindelse</a></li>
        </ul>
    </section>
</x-layouts.app-shell>
