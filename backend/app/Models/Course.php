<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// A course, e.g. CS330 Database Systems.
class Course extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'max_absences'];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
