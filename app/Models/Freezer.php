<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Freezer extends Model
{
    protected $fillable = ['name'];

    public function items()
    {
        return $this->hasMany(FreezerItem::class)->orderBy('sort_order');
    }
}
