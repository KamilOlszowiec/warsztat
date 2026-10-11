<?php
// app/Models/DzienWolny.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DzienWolny extends Model
{
    protected $table = 'dni_wolne_warsztatu';
    protected $primaryKey = 'id_dnia_wolnego';

    protected $fillable = ['data', 'opis'];

    protected $casts = [
        'data' => 'date',
    ];
}