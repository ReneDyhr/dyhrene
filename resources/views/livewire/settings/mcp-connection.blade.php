@section('title', $title)
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content">
            <div class="col-12 recipe">
                <h1>{{ $title }}</h1>
                <p class="text-muted">Connect <strong>Claude Web</strong> or other MCP clients using OAuth against this app. Each <strong>server</strong> below is a separate MCP HTTP endpoint (different tools); OAuth settings are shared.</p>
                <ol style="max-width: 48rem; line-height: 1.6;">
                    <li>In your MCP client, add a remote server using that server’s <strong>MCP HTTP endpoint</strong> (or rely on discovery from a <code>401</code> response <code>WWW-Authenticate</code> header).</li>
                    <li>Complete the OAuth flow in the browser when prompted; approve access for this app.</li>
                    <li>Ensure the access token includes the <strong>mcp:use</strong> scope.</li>
                </ol>

                <h2 style="margin-top: 1.75rem;">Shared OAuth (all MCP servers)</h2>
                <p class="text-muted">Same authorization server and token endpoint for every MCP server listed below.</p>
                @include('livewire.settings.partials.mcp-field-table', ['fields' => $sharedFields])

                @foreach ($servers as $server)
                    <h2 style="margin-top: 2rem;" id="mcp-server-{{ $server['id'] }}">{{ $server['title'] }}</h2>
                    <p class="text-muted">{{ $server['description'] }}</p>
                    <p><code style="word-break: break-all;">{{ $server['path'] }}</code></p>
                    @include('livewire.settings.partials.mcp-field-table', ['fields' => $server['fields']])
                @endforeach

                <p style="max-width: 48rem; margin-top: 1.5rem;"><strong>Redirect URIs:</strong> OAuth clients must use redirect URIs allowed by <code>config/mcp.php</code> (<code>redirect_domains</code> / env <code>MCP_OAUTH_REDIRECT_DOMAINS</code>). Tighten this in production.</p>
            </div>
        </div>
    </div>
</div>
