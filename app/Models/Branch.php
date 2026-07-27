<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'opening_time',
        'closing_time',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
        ];
    }

    public function barbers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'barber');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
