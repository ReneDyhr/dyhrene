<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageItem extends Model
{
    protected $fillable = ['storage_id', 'name', 'quantity', 'sort_order'];

    public function storage()
    {
        return $this->belongsTo(Storage::class);
    }
}
