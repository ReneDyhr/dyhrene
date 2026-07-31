<div>
    @section('title', $title)
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>Edit Item: {{ $item->name }}</h1>

                        @if (session()->has('success'))
                            <div class="alert alert-success" style="padding: 10px; margin-bottom: 15px;">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session()->has('error'))
                            <div class="alert alert-danger" style="padding: 10px; margin-bottom: 15px;">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form wire:submit.prevent="save">
                            @include('livewire.inventory.partials.form')
                            <div style="margin-top: 15px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Save Changes
                                </button>
                                <a href="{{ route('inventory.show', $item) }}" class="btn btn-default">Cancel</a>
                            </div>
                        </form>

                        @if($item->attachments->isNotEmpty())
                            <div style="margin-top: 30px;">
                                <h2>Existing Attachments</h2>
                                <table class="table" style="max-width: 600px;">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($item->attachments as $attachment)
                                            <tr>
                                                <td>{{ $attachment->file_name }}</td>
                                                <td>{{ $attachment->mime_type ?? '-' }}</td>
                                                <td>{{ $attachment->size !== null ? \number_format($attachment->size / 1024, 2) . ' KB' : '-' }}</td>
                                                <td>
                                                    <a href="{{ route('inventory.attachment', $attachment) }}" class="btn btn-info btn-sm"
                                                        style="color: #fff; padding: 4px 10px; font-size: 0.9em;" target="_blank">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <button wire:confirm="Are you sure you want to remove this attachment?"
                                                        wire:click="removeAttachment({{ $attachment->id }})" class="btn btn-danger btn-sm"
                                                        style="color: #fff; padding: 4px 10px; font-size: 0.9em;">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>
