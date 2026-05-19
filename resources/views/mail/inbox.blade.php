@section('title', $title)
<div>
    @include('components.layouts.sidenav')
    <style>
        .mail-inbox-layout {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            flex-wrap: nowrap;
        }
        .mail-inbox-list { flex: 1 1 auto; min-width: 0; }
        .mail-inbox-layout--detail .mail-inbox-list {
            flex: 1 1 0;
            max-width: calc(100% - 22rem - 1.25rem);
        }
        .mail-inbox-detail {
            flex: 0 0 22rem;
            width: 22rem;
            max-width: 100%;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 1rem 1.25rem;
            box-sizing: border-box;
            position: sticky;
            top: 0.75rem;
            max-height: calc(100vh - 12rem);
            overflow-y: auto;
            overflow-x: hidden;
        }
        .mail-inbox-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1.25rem;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #666;
        }
        .mail-inbox-legend-item { display: inline-flex; align-items: center; gap: 0.35rem; }
        .mail-inbox-table-wrap {
            overflow-x: auto;
            border: 1px solid #eee;
            border-radius: 4px;
            background: #fff;
        }
        .mail-inbox-table { width: 100%; margin-bottom: 0; min-width: 36rem; }
        .mail-inbox-table tbody tr.is-selected { background: #f0f7ff !important; }
        .mail-inbox-detail h2 {
            margin: 0 0 0.75rem;
            font-size: 1.1rem;
            line-height: 1.35;
            word-break: break-word;
        }
        .mail-inbox-detail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-bottom: 0.75rem;
        }
        @media (max-width: 991px) {
            .mail-inbox-layout { flex-direction: column; }
            .mail-inbox-layout--detail .mail-inbox-list { max-width: 100%; }
            .mail-inbox-detail {
                flex: 1 1 auto;
                width: 100%;
                position: static;
                max-height: none;
            }
        }
    </style>
    <div id="main">
        @include('components.layouts.header')
        <div class="content">
            <div class="col-12 recipe">
                <h1>{{ $title }}</h1>
                <p class="text-muted">
                    Archive · messages to <strong>{{ $recipientEmail }}</strong> (To/Cc)
                </p>

                @if (session('success'))
                    <div class="alert alert-success" role="alert">{!! session('success') !!}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                @endif

                @if ($error !== '')
                    <div class="alert alert-danger" role="alert">{{ $error }}</div>
                @else
                    <div @class([
                        'mail-inbox-layout',
                        'mail-inbox-layout--detail' => $selectedMessage !== null,
                    ])>
                        <div class="mail-inbox-list">
                            <p class="text-muted" wire:loading.remove wire:target="mount,loadMore">
                                Showing {{ count($emails) }} of {{ $total }} message(s)
                            </p>
                            <p class="text-muted" wire:loading wire:target="mount,loadMore">Loading…</p>

                            @if ($emails === [] && !$loading)
                                <p>No messages in archive.</p>
                            @endif

                            <div class="mail-inbox-legend">
                                <span class="mail-inbox-legend-item">
                                    <span class="label label-success">Created</span> imported
                                </span>
                                <span class="mail-inbox-legend-item">
                                    <span class="label label-warning">Not created</span> pending import
                                </span>
                            </div>

                            <div class="mail-inbox-table-wrap">
                            <table class="table table-striped mail-inbox-table">
                                <thead>
                                    <tr>
                                        <th>Received</th>
                                        <th>From</th>
                                        <th>Subject</th>
                                        <th>Type</th>
                                        <th>Receipt</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($emails as $email)
                                        <tr
                                            wire:key="email-{{ $email['id'] }}"
                                            wire:click="selectEmail('{{ $email['id'] }}')"
                                            @class(['is-selected' => $selectedEmailId === $email['id']])
                                            style="cursor: pointer;"
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
                                            <td style="white-space: nowrap;" wire:click.stop>
                                                @if ($email['receiptImportStatus'] === 'created' && $email['receiptId'] !== null)
                                                    <a
                                                        class="label label-success"
                                                        href="{{ route('receipts.show', ['receipt' => $email['receiptId']]) }}"
                                                        title="Open receipt"
                                                    >Created</a>
                                                @elseif ($email['receiptImportStatus'] === 'pending')
                                                    <span class="label label-warning" title="Marked as receipt, not imported yet">{{ $email['receiptImportLabel'] }}</span>
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
                            </div>

                            @if ($hasMore)
                                <button type="button" class="btn btn-default" wire:click="loadMore" wire:loading.attr="disabled">
                                    Load more
                                </button>
                            @endif
                        </div>

                        @if ($selectedMessage !== null)
                            <aside class="mail-inbox-detail">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
                                    <h2>{{ $selectedMessage->summary->subject ?: '(no subject)' }}</h2>
                                    <button type="button" class="btn btn-link btn-sm" wire:click="clearSelection" style="flex-shrink: 0;">Close</button>
                                </div>
                                <p><strong>From:</strong> {{ $selectedMessage->summary->fromDisplay() }}</p>
                                <p><strong>Received:</strong> {{ $selectedMessage->summary->receivedAt?->format('Y-m-d H:i:s') ?? '—' }}</p>

                                @if ($selectedClassification !== null)
                                    <p><strong>Type:</strong> {{ $selectedClassification->document_type->label() }}</p>
                                    <div class="mail-inbox-detail-actions">
                                        <button type="button" class="btn btn-default btn-sm" wire:click="setDocumentType('{{ $selectedMessage->summary->id }}', 'receipt')">Mark as receipt</button>
                                        <button type="button" class="btn btn-default btn-sm" wire:click="setDocumentType('{{ $selectedMessage->summary->id }}', 'payslip')">Mark as payslip</button>
                                        <button type="button" class="btn btn-default btn-sm" wire:click="setDocumentType('{{ $selectedMessage->summary->id }}', 'unknown')">Mark as unknown</button>
                                    </div>

                                    @if ($selectedClassification->receipt_id !== null)
                                        <p style="margin-bottom: 0.75rem;">
                                            <a class="btn btn-primary btn-sm" href="{{ route('receipts.show', ['receipt' => $selectedClassification->receipt_id]) }}">View receipt</a>
                                        </p>
                                    @elseif ($selectedClassification->document_type === \App\Enums\MailDocumentTypeEnum::Receipt)
                                        <p style="margin-bottom: 0.75rem;">
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm"
                                                wire:click="processReceipt('{{ $selectedMessage->summary->id }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="processReceipt"
                                                @if ($processingReceipt) disabled @endif
                                            >
                                                <span wire:loading.remove wire:target="processReceipt">Create receipt</span>
                                                <span wire:loading wire:target="processReceipt">Processing…</span>
                                            </button>
                                        </p>
                                    @endif
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
                            </aside>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
