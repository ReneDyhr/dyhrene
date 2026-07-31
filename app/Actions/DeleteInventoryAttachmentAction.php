<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryAttachment;

class DeleteInventoryAttachmentAction
{
    public function handle(InventoryAttachment $attachment): void
    {
        \Storage::disk('wasabi')->delete($attachment->file_path);
        $attachment->delete();
    }
}
