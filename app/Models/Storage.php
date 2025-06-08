<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Storage extends Model
{
    protected $table = 'storage';

    protected $fillable = ['name'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<StorageItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StorageItem::class)->orderBy('sort_order');
    }
}
