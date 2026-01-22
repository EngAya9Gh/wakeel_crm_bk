<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'color'];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'type_id');
    }
}
