@section('title', $title)
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content">
            <div class="col-12 recipe">
                <h1>{{ $title }}</h1>
                <p class="text-muted">
                    Reading mail for <strong>{{ $recipientEmail }}</strong> (To/Cc).
                    @if ($accountAddresses !== [])
                        Other addresses on this account:
                        {{ implode(', ', $accountAddresses) }}.
                    @endif
                </p>

                @if ($error !== '')
                    <div class="alert alert-danger" role="alert">{{ $error }}</div>
                @endif

                @if ($mailboxes === [] && $error === '')
                    <p class="text-muted">No mailboxes available.</p>
                @else
                    <form wire:submit="applyFilters" style="margin-bottom: 1.5rem; max-width: 56rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr)); gap: 0.75rem; align-items: end;">
                            <div>
                                <label for="mailboxId" class="control-label">Mailbox</label>
                                <select id="mailboxId" wire:model="mailboxId" class="form-control">
                                    @foreach ($mailboxes as $mailbox)
                                        <option value="{{ $mailbox['id'] }}">
                                            {{ $mailbox['name'] }}
                                            @if ($mailbox['unreadEmails'] > 0)
                                                ({{ $mailbox['unreadEmails'] }} unread)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="from" class="control-label">From</label>
                                <input type="text" id="from" wire:model="from" class="form-control" placeholder="sender@example.com">
                            </div>
                            <div>
                                <label for="subject" class="control-label">Subject</label>
                                <input type="text" id="subject" wire:model="subject" class="form-control" placeholder="Contains…">
                            </div>
                            <div>
                                <label for="since" class="control-label">Received after</label>
                                <input type="date" id="since" wire:model="since" class="form-control">
                            </div>
                            <div>
                                <label for="searchText" class="control-label">Search text</label>
                                <input type="text" id="searchText" wire:model="searchText" class="form-control" placeholder="Full-text search">
                            </div>
                            <div>
                                <label class="control-label" style="display: block;">
                                    <input type="checkbox" wire:model.boolean="hasAttachment"> Has attachment
                                </label>
                            </div>
                            <div>
                                <label class="control-label" style="display: block;">
                                    <input type="checkbox" wire:model.boolean="showAllAccountMail"> All account mail
                                </label>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="applyFilters,mount">Apply filters</span>
                                    <span wire:loading wire:target="applyFilters,mount">Loading…</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 20rem;">
                            <p class="text-muted">
                                Showing {{ count($emails) }} of {{ $total }} message(s)
                            </p>

                            @if ($emails === [] && !$loading)
                                <p>No messages match your filters.</p>
                            @endif

                            <table class="table table-striped receipts-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Received</th>
                                        <th>From</th>
                                        <th>Subject</th>
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

                                @if ($selectedMessage->attachments->isNotEmpty())
                                    <h3 style="font-size: 1rem;">Attachments</h3>
                                    <ul>
                                        @foreach ($selectedMessage->attachments as $attachment)
                                            <li>
                                                {{ $attachment->name }}
                                                ({{ number_format($attachment->size / 1024, 1) }} KB)
                                                <button
                                                    type="button"
                                                    class="btn btn-link btn-sm"
                                                    wire:click="downloadAttachment(@js($attachment->blobId), @js($attachment->name), @js($attachment->type))"
                                                >
                                                    Download
                                                </button>
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
