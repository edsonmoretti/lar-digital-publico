<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryItem extends Model
{
    protected $fillable = ['trip_id', 'title', 'description', 'start_date', 'start_time', 'end_date', 'end_time', 'location', 'cost', 'category', 'is_completed'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_completed' => 'boolean',
        'cost' => 'decimal:2',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
