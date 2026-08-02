<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryAttachment;
use App\Models\InventoryItem;
use Illuminate\Http\UploadedFile;

class CreateInventoryAttachmentAction
{
    public function handle(InventoryItem $item, UploadedFile $file): InventoryAttachment
    {
        $path = $file->store('inventory', 'wasabi');

        if ($path === false) {
            throw new \RuntimeException('Failed to store attachment.');
        }

        return $item->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
