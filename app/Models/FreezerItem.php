<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreezerItem extends Model
{
    protected $fillable = ['freezer_id', 'name', 'quantity', 'sort_order'];

    public function freezer()
    {
        return $this->belongsTo(Freezer::class);
    }
}
