<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model
{
    protected $table = 'conversation_messages';
    protected $fillable = ['conversation_id','role','text','metadata'];
    protected $casts = ['metadata' => 'array'];
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}