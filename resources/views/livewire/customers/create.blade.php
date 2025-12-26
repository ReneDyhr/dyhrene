<div>
    @section('title', 'Create Customer')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>Create Customer</h1>

                        @if (session()->has('success'))
                            <div class="alert alert-success" style="padding: 10px; margin-bottom: 15px;">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form wire:submit.prevent="save">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="name">Name <span style="color: red;">*</span></label>
                                <input type="text" id="name" wire:model="name" class="form-control"
                                    placeholder="Customer name">
                                @error('name')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="email">Email</label>
                                <input type="email" id="email" wire:model="email" class="form-control"
                                    placeholder="customer@example.com">
                                @error('email')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="phone">Phone</label>
                                <input type="text" id="phone" wire:model="phone" class="form-control"
                                    placeholder="Phone number">
                                @error('phone')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="notes">Notes</label>
                                <textarea id="notes" wire:model="notes" class="form-control" rows="4"
                                    placeholder="Additional notes..."></textarea>
                                @error('notes')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="submit" class="btn btn-primary" style="color: #fff;">Save</button>
                                <a href="{{ route('customers.index') }}" class="btn btn-secondary"
                                    style="color: #fff;">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

