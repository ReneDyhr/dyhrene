@section('title', $title)
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content">
            <div class="col-12 recipe">
                <h1>{{ $title }}</h1>
                <p class="text-muted">
                    Archive · messages to <strong>{{ $recipientEmail }}</strong> (To/Cc)
                </p>

                @if ($error !== '')
                    <div class="alert alert-danger" role="alert">{{ $error }}</div>
                @else
                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 20rem;">
                            <p class="text-muted" wire:loading.remove wire:target="mount,loadMore">
                                Showing {{ count($emails) }} of {{ $total }} message(s)
                            </p>
                            <p class="text-muted" wire:loading wire:target="mount,loadMore">Loading…</p>

                            @if ($emails === [] && !$loading)
                                <p>No messages in archive.</p>
                            @endif

                            <table class="table table-striped receipts-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Received</th>
                                        <th>From</th>
                                        <th>Subject</th>
                                        <th>Type</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($emails as $email)
                                        <tr
                                            wire:key="email-{{ $email['id'] }}"
                                            wire:click="selectEmail('{{ $email['id'] }}')"
                                            style="cursor: pointer; {{ $selectedEmailId === $email['id'] ? 'background: #f0f7ff;' : '' }}"
                                        >
                                            <td style="white-space: nowrap;">
                                                {{ $email['receivedAt'] ?? '—' }}
                                            </td>
                                            <td>{{ \Illuminate\Support\Str::limit($email['fromDisplay'], 40) }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($email['subject'] ?: '(no subject)', 60) }}</td>
                                            <td style="white-space: nowrap;">
                                                @if ($email['documentTypeLabel'] !== null)
                                                    <span class="label label-default">{{ $email['documentTypeLabel'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($email['hasAttachment'])
                                                    <span title="Has attachment">📎</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if ($hasMore)
                                <button type="button" class="btn btn-default" wire:click="loadMore" wire:loading.attr="disabled">
                                    Load more
                                </button>
                            @endif
                        </div>

                        @if ($selectedMessage !== null)
                            <div style="flex: 1; min-width: 20rem; border-left: 1px solid #ddd; padding-left: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <h2 style="margin-top: 0; font-size: 1.25rem;">{{ $selectedMessage->summary->subject ?: '(no subject)' }}</h2>
                                    <button type="button" class="btn btn-link btn-sm" wire:click="clearSelection">Close</button>
                                </div>
                                <p><strong>From:</strong> {{ $selectedMessage->summary->fromDisplay() }}</p>
                                <p><strong>Received:</strong> {{ $selectedMessage->summary->receivedAt?->format('Y-m-d H:i:s') ?? '—' }}</p>

                                @if ($selectedClassification !== null)
                                    <p><strong>Type:</strong> {{ $selectedClassification->document_type->label() }}</p>
                                    <p style="margin-bottom: 0.75rem;">
                                        <button type="button" class="btn btn-default btn-sm" wire:click="setDocumentType('{{ $selectedMessage->summary->id }}', 'receipt')">Mark as receipt</button>
                                        <button type="button" class="btn btn-default btn-sm" wire:click="setDocumentType('{{ $selectedMessage->summary->id }}', 'payslip')">Mark as payslip</button>
                                        <button type="button" class="btn btn-default btn-sm" wire:click="setDocumentType('{{ $selectedMessage->summary->id }}', 'unknown')">Mark as unknown</button>
                                    </p>
                                @endif

                                @if ($selectedMessage->attachments->isNotEmpty())
                                    <h3 style="font-size: 1rem;">Attachments</h3>
                                    <ul>
                                        @foreach ($selectedMessage->attachments as $attachment)
                                            <li>
                                                {{ $attachment->name }} ({{ number_format($attachment->size / 1024, 1) }} KB)
                                                <button type="button" class="btn btn-link btn-sm" wire:click="downloadAttachment(@js($attachment->blobId), @js($attachment->name), @js($attachment->type))">Download</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <h3 style="font-size: 1rem;">Body</h3>
                                <pre style="white-space: pre-wrap; word-break: break-word; max-height: 24rem; overflow: auto; background: #f8f8f8; padding: 0.75rem; border-radius: 4px;">{{ $selectedMessage->textBody ?? $selectedMessage->summary->preview ?? '(no text body)' }}</pre>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
