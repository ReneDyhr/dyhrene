<div>
    @section('title', 'Mass Edit Receipt Items')
    @include('components.layouts.sidenav')
    <style>
        .receipt-link {
            display: inline-flex;
            align-items: center;
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9em;
            padding: 2px 6px;
            border-radius: 3px;
            transition: all 0.15s ease;
            background-color: transparent;
            border: none;
        }

        .receipt-link:hover {
            background-color: #e8f5e9;
            color: #1e7e34;
            text-decoration: underline;
        }

        .receipt-link:hover i {
            opacity: 1;
        }

        .receipt-link i {
            font-size: 0.7em;
            opacity: 0.6;
            transition: opacity 0.15s ease;
            margin-left: 4px;
        }
    </style>
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="margin: 0;">Mass Edit Receipt Items</h2>
                            <button wire:click="save" class="btn btn-success" style="color: #fff;">
                                <i class="fa fa-save"></i> Save All Changes
                            </button>
                        </div>

                        @if (session()->has('success'))
                            <div class="alert alert-success" style="padding: 15px; margin-bottom: 20px;">
                                {{ session('success') }}
                            </div>
                        @endif

                        <p style="margin-bottom: 20px; color: #666;">
                            Showing the latest 1000 receipt items. Edit the name and category for each item, then click
                            "Save All Changes" to update them.
                        </p>

                        <div style="overflow-x: auto; max-height: 70vh; overflow-y: auto;">
                            <table class="table" style="table-layout: fixed; width: 100%; margin-bottom: 0;">
                                <colgroup>
                                    <col style="width: 5%;">
                                    <col style="width: 35%;">
                                    <col style="width: 25%;">
                                    <col style="width: 20%;">
                                    <col style="width: 15%;">
                                </colgroup>
                                <thead style="position: sticky; top: 0; background: #fff; z-index: 10;">
                                    <tr>
                                        <th
                                            style="padding: 8px; font-size: 0.9em; font-weight: 600; border-bottom: 2px solid #ddd; background: #fff;">
                                            ID</th>
                                        <th
                                            style="padding: 8px; font-size: 0.9em; font-weight: 600; border-bottom: 2px solid #ddd; background: #fff;">
                                            Item Name</th>
                                        <th
                                            style="padding: 8px; font-size: 0.9em; font-weight: 600; border-bottom: 2px solid #ddd; background: #fff;">
                                            Category</th>
                                        <th
                                            style="padding: 8px; font-size: 0.9em; font-weight: 600; border-bottom: 2px solid #ddd; background: #fff;">
                                            Receipt</th>
                                        <th
                                            style="padding: 8px; font-size: 0.9em; font-weight: 600; border-bottom: 2px solid #ddd; background: #fff;">
                                            Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                        <tr>
                                            <td style="padding: 6px 8px; font-size: 0.9em; color: #666;">{{ $item['id'] }}
                                            </td>
                                            <td style="padding: 6px 8px;">
                                                <input type="text" class="form-control form-control-sm"
                                                    wire:model="items.{{ $index }}.name"
                                                    style="width: 100%; font-size: 0.9em; padding: 4px 8px;">
                                            </td>
                                            <td style="padding: 6px 8px;">
                                                <select class="form-control form-control-sm"
                                                    wire:model="items.{{ $index }}.category_id"
                                                    style="width: 100%; font-size: 0.9em; padding: 4px 8px;">
                                                    @foreach($categories as $cat)
                                                        <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="padding: 6px 8px; font-size: 0.9em; word-wrap: break-word;">
                                                <a href="{{ route('receipts.show', $item['receipt_id']) }}"
                                                    class="receipt-link" target="_blank">
                                                    {{ $item['receipt_name'] }}
                                                    <i class="fa fa-external-link"></i>
                                                </a>
                                            </td>
                                            <td style="padding: 6px 8px; font-size: 0.9em; color: #666;">
                                                {{ $item['receipt_date'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <p style="margin: 0; color: #666; font-size: 0.9em;">
                                Showing {{ count($items) }} receipt items. Make your changes and click "Save All
                                Changes" to update them.
                            </p>
                        </div>

                        @if(empty($items))
                            <div class="alert alert-info" style="padding: 15px; margin-top: 20px;">
                                No receipt items found.
                            </div>
                        @endif
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>