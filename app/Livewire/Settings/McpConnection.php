<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Mcp\McpServerRegistry;
use Illuminate\View\View;
use Livewire\Component;

class McpConnection extends Component
{
    /**
     * @return list<array{id: string, title: string, description: string, path: string, fields: array<string, string>}>
     */
    public function serversWithFields(): array
    {
        $out = [];

        foreach (McpServerRegistry::servers() as $server) {
            $out[] = [
                'id' => $server['id'],
                'title' => $server['title'],
                'description' => $server['description'],
                'path' => $server['path'],
                'fields' => McpServerRegistry::serverResourceFields($server['path']),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public function sharedOAuthFields(): array
    {
        return McpServerRegistry::sharedOAuthFields();
    }

    public function render(): View
    {
        return \view('livewire.settings.mcp-connection', [
            'title' => 'MCP & OAuth (AI assistants)',
            'sharedFields' => $this->sharedOAuthFields(),
            'servers' => $this->serversWithFields(),
        ]);
    }
}
