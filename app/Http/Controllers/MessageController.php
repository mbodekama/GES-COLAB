<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index()
    {
        $authId = auth()->id();

        // Récupérer les conversations uniques (dernier message de chaque interlocuteur)
        $conversations = Message::with(['sender', 'receiver'])
            ->where('sender_id', $authId)
            ->orWhere('receiver_id', $authId)
            ->latest()
            ->get()
            ->groupBy(function ($msg) use ($authId) {
                return $msg->sender_id === $authId
                    ? $msg->receiver_id
                    : $msg->sender_id;
            })
            ->map(fn($messages) => $messages->first())
            ->values();

        // Liste des utilisateurs disponibles pour une nouvelle conversation
        $users = User::where('id', '!=', $authId)->orderBy('name')->get();

        // Conversation active (premier interlocuteur)
        $activeUser = null;
        $messages   = collect();

        if ($conversations->isNotEmpty()) {
            $firstMsg   = $conversations->first();
            $activeUser = $firstMsg->sender_id === $authId
                ? $firstMsg->receiver
                : $firstMsg->sender;

            $messages = Message::conversation($authId, $activeUser->id)
                ->with(['sender', 'receiver'])
                ->oldest()
                ->get();

            // Marquer comme lus
            $messages->where('receiver_id', $authId)
                     ->whereNull('read_at')
                     ->each->markAsRead();
        }

        return view('messages.index', compact('conversations', 'users', 'activeUser', 'messages'));
    }

    public function show(User $user)
    {
        $authId = auth()->id();

        $messages = Message::conversation($authId, $user->id)
            ->with(['sender', 'receiver'])
            ->oldest()
            ->get();

        // Marquer les messages reçus comme lus
        $messages->where('receiver_id', $authId)
                 ->whereNull('read_at')
                 ->each->markAsRead();

        $conversations = Message::with(['sender', 'receiver'])
            ->where('sender_id', $authId)
            ->orWhere('receiver_id', $authId)
            ->latest()
            ->get()
            ->groupBy(function ($msg) use ($authId) {
                return $msg->sender_id === $authId
                    ? $msg->receiver_id
                    : $msg->sender_id;
            })
            ->map(fn($msgs) => $msgs->first())
            ->values();

        $users      = User::where('id', '!=', $authId)->orderBy('name')->get();
        $activeUser = $user;

        return view('messages.index', compact('conversations', 'users', 'activeUser', 'messages'));
    }

    public function send(Request $request, User $user)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $user->id,
            'body'        => $request->body,
        ]);

        // Réponse JSON pour les appels AJAX
        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function destroy(Message $message)
    {
        abort_if($message->sender_id !== auth()->id(), 403);
        $message->delete();

        return back()->with('success', 'Message supprimé.');
    }

    public function unread()
    {
        return response()->json([
            'count' => auth()->user()->unreadMessagesCount(),
        ]);
    }
}
