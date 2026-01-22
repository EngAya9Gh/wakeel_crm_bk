<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'user_id', 'type_id', 'content', 'subject', 'outcome'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function type()
    {
        return $this->belongsTo(CommentType::class, 'type_id');
    }

    public function mentions()
    {
        return $this->belongsToMany(User::class, 'comment_mentions')->withTimestamps()->withPivot('read_at');
    }

    public function attachments()
    {
        return $this->hasMany(ClientFile::class);
    }
}
