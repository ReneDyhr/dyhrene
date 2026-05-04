<table class="table table-striped" style="max-width: 56rem;">
    <thead>
    <tr>
        <th style="width: 28%;">Field</th>
        <th>Value</th>
        <th style="width: 90px;">Copy</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($fields as $label => $value)
        <tr>
            <td>{{ $label }}</td>
            <td><code style="word-break: break-all;">{{ $value }}</code></td>
            <td>
                <button type="button" class="btn btn-default btn-sm"
                        onclick="navigator.clipboard.writeText(this.getAttribute('data-copy')); this.textContent='Copied'; setTimeout(() => this.textContent='Copy', 2000);"
                        data-copy="{{ htmlspecialchars($value, ENT_QUOTES, 'UTF-8') }}">Copy</button>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
