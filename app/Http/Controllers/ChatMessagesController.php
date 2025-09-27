<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\ConversationMessage;

class ChatMessagesController extends Controller
{
    public function index(Request $req)
    {
        $conversationId = $req->integer('conversation_id') ?: null;
        $afterId        = $req->integer('after_id') ?: 0;
        $limit          = min(max((int)$req->input('limit', 50), 1), 200); // 1..200

        // 1) localizar la conversación por id o por session_id actual
        if (!$conversationId) {
            $sid = $req->session()->getId();
            $conv = Conversation::where('session_id', $sid)
                ->orderByDesc('id')
                ->first();
            if (!$conv) {
                return response()->json([
                    'conversation_id' => null,
                    'messages' => []
                ]);
            }
            $conversationId = (int) $conv->id;
        } else {
            $conv = Conversation::find($conversationId);
            if (!$conv) {
                return response()->json([
                    'conversation_id' => null,
                    'messages' => []
                ]);
            }
        }

        // 2) traer mensajes
        if ($afterId > 0) {
            // incremental: todo lo que sea > after_id
            $msgs = ConversationMessage::where('conversation_id', $conversationId)
                ->where('id', '>', $afterId)
                ->orderBy('id', 'asc')
                ->get();
        } else {
            // historial inicial: últimos N
            $msgs = ConversationMessage::where('conversation_id', $conversationId)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get()
                ->sortBy('id') // para devolver ascendente
                ->values();
        }

        return response()->json([
            'conversation_id' => $conversationId,
            'messages' => $msgs->map(fn($m) => [
                'id'         => $m->id,
                'role'       => $m->role,   // 'user' | 'assistant'
                'text'       => $m->text,
                'created_at' => $m->created_at,
            ])->toArray()
        ]);
    }
}
