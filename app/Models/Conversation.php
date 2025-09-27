<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['session_id','customer_id','state'];
    public function messages()
    {
        return $this->hasMany(ConversationMessage::class);
    }
}


