@extends('layouts.app')
@section('page-title', 'Messagerie')

@section('content')

<div class="card" style="height:calc(100vh - 140px);min-height:500px">
    <div class="row g-0 h-100">

        {{-- LISTE DES CONVERSATIONS --}}
        <div class="col-md-4 border-end h-100 d-flex flex-column">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Conversations</span>
                <button class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#newMsgModal"
                        aria-label="Nouveau message">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                </button>
            </div>

            <div style="overflow-y:auto;flex:1">
                @forelse($conversations as $conv)
                    @php
                        $authId      = auth()->id();
                        $interlocutor = $conv->sender_id === $authId ? $conv->receiver : $conv->sender;
                        $isActive     = $activeUser?->id === $interlocutor->id;
                        $isUnread     = $conv->receiver_id === $authId && !$conv->read_at;
                    @endphp
                    <a href="{{ route('messages.show', $interlocutor) }}"
                       class="d-flex align-items-center gap-2 p-3 text-decoration-none border-bottom"
                       style="background:{{ $isActive ? '#E6F1FB' : 'transparent' }};transition:background .15s"
                       onmouseover="this.style.background='{{ $isActive ? '#E6F1FB' : '#f8f9ff' }}'"
                       onmouseout="this.style.background='{{ $isActive ? '#E6F1FB' : 'transparent' }}'">
                        <x-avatar :initials="$interlocutor->initials" />
                        <div style="flex:1;min-width:0">
                            <div class="d-flex justify-content-between">
                                <span class="fw-{{ $isUnread ? 'bold' : 'medium' }} text-dark" style="font-size:13px">
                                    {{ $interlocutor->name }}
                                </span>
                                <small class="text-muted" style="font-size:10px;white-space:nowrap">
                                    {{ $conv->created_at->diffForHumans(null, true) }}
                                </small>
                            </div>
                            <div class="text-muted text-truncate" style="font-size:12px">
                                {{ $conv->sender_id === $authId ? 'Vous : ' : '' }}{{ $conv->body }}
                            </div>
                        </div>
                        @if($isUnread)
                            <div style="width:8px;height:8px;background:#185FA5;border-radius:50%;flex-shrink:0"></div>
                        @endif
                    </a>
                @empty
                    <div class="text-center text-muted py-5 px-3" style="font-size:13px">
                        <i class="bi bi-chat-dots fs-1 d-block mb-2 opacity-25"></i>
                        Aucune conversation.<br>Commencez à écrire !
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ZONE DE CONVERSATION --}}
        <div class="col-md-8 h-100 d-flex flex-column">
            @if($activeUser)
                {{-- HEADER CONVERSATION --}}
                <div class="p-3 border-bottom d-flex align-items-center gap-2">
                    <x-avatar :initials="$activeUser->initials" />
                    <div>
                        <div class="fw-semibold" style="font-size:14px">{{ $activeUser->name }}</div>
                        <div class="text-muted" style="font-size:11px">{{ $activeUser->primary_role_label }}</div>
                    </div>
                </div>

                {{-- MESSAGES --}}
                <div id="chat-body" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px">
                    @foreach($messages as $msg)
                        @if($msg->sender_id === auth()->id())
                            <div style="align-self:flex-end;max-width:70%">
                                <div style="background:#185FA5;color:#fff;border-radius:12px 12px 0 12px;padding:10px 14px;font-size:13px">
                                    {{ $msg->body }}
                                </div>
                                <div class="text-muted text-end mt-1" style="font-size:10px">
                                    {{ $msg->created_at->format('H:i') }}
                                    @if($msg->read_at) <i class="bi bi-check2-all text-primary"></i> @endif
                                </div>
                            </div>
                        @else
                            <div style="align-self:flex-start;max-width:70%">
                                <div style="background:#f0f2f5;border-radius:0 12px 12px 12px;padding:10px 14px;font-size:13px">
                                    {{ $msg->body }}
                                </div>
                                <div class="text-muted mt-1" style="font-size:10px">
                                    {{ $msg->created_at->format('H:i') }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- INPUT MESSAGE --}}
                <div class="p-3 border-top">
                    <form method="POST" action="{{ route('messages.send', $activeUser) }}" id="msg-form">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="body" id="msg-input"
                                   class="form-control" placeholder="Écrire un message..."
                                   autocomplete="off" required>
                            <button type="submit" class="btn btn-primary px-3">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </form>
                </div>

            @else
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                    <i class="bi bi-chat-dots fs-1 mb-3 opacity-25"></i>
                    <div class="fw-medium mb-1">Sélectionnez une conversation</div>
                    <div class="small">ou démarrez-en une nouvelle</div>
                    <button class="btn btn-outline-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#newMsgModal">
                        <i class="bi bi-pencil-square me-1"></i>Nouveau message
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- MODAL NOUVEAU MESSAGE --}}
<div class="modal fade" id="newMsgModal" tabindex="-1"
     aria-labelledby="newMsgModalTitle" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title small" id="newMsgModalTitle">Nouveau message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-medium">Destinataire</label>
                <select class="form-select" id="new-conv-user">
                    <option value="">— Choisir —</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" data-url="{{ route('messages.show', $u) }}">
                            {{ $u->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary btn-sm" onclick="startConv()">Démarrer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Scroll bas au chargement
const chatBody = document.getElementById('chat-body');
if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;

// Envoyer avec Entrée
document.getElementById('msg-input')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('msg-form').submit();
    }
});

// Démarrer nouvelle conversation
function startConv() {
    const sel = document.getElementById('new-conv-user');
    const url = sel.options[sel.selectedIndex]?.dataset?.url;
    if (url) window.location.href = url;
}
</script>
@endpush
