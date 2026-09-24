<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedTaskFilter extends Model
{
    protected $fillable = [
        'name',
        'filters',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }
}