<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Storage extends Model
{
    protected $table = 'storage';
    protected $fillable = ['name'];

    public function items()
    {
        return $this->hasMany(StorageItem::class)->orderBy('sort_order');
    }
}
