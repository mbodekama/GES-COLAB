{{--
    Partial réutilisable : historique des modifications d'une entité.
    Variables attendues :
      $activityLogs  — Collection d'ActivityLog (avec user chargé)
      $title         — (optionnel) Titre du bloc, défaut "Historique"
--}}
@php $title = $title ?? 'Historique des modifications'; @endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>{{ $title }}</span>
        @if($activityLogs->count())
        <span class="badge bg-secondary rounded-pill">{{ $activityLogs->count() }}</span>
        @endif
    </div>

    @forelse($activityLogs as $log)
    <div class="list-group-item list-group-item-action border-0 border-bottom py-2 px-3">
        <div class="d-flex align-items-start gap-2">

            {{-- Icône action --}}
            <div class="mt-1 flex-shrink-0">
                <i class="bi {{ $log->action_icon }}" style="font-size:13px"></i>
            </div>

            {{-- Contenu --}}
            <div class="flex-grow-1 min-w-0" style="font-size:13px">
                <div class="fw-medium text-truncate">{{ $log->description }}</div>

                <div class="text-muted small mt-1">
                    <i class="bi bi-person me-1"></i>{{ $log->user?->name ?? 'Système' }}
                    &nbsp;·&nbsp;
                    <i class="bi bi-calendar3 me-1"></i>{{ $log->created_at->isoFormat('D MMM YYYY à H:mm') }}
                </div>

                {{-- Diff old → new --}}
                @if($log->new_values)
                <div class="mt-1 d-flex flex-wrap gap-1">
                    @foreach($log->new_values as $field => $newVal)
                    <span class="badge bg-light text-dark border" style="font-size:11px;font-weight:400">
                        <span class="text-muted">{{ $field }}</span>:&nbsp;
                        @if(isset($log->old_values[$field]) && $log->old_values[$field] !== null)
                            <span class="text-danger" style="text-decoration:line-through">{{ $log->old_values[$field] }}</span>
                            &nbsp;→&nbsp;
                        @endif
                        <span class="text-success fw-medium">{{ $newVal }}</span>
                    </span>
                    @endforeach
                </div>
                @endif

                {{-- Commentaire --}}
                @if($log->comment)
                <div class="text-muted fst-italic small mt-1">
                    <i class="bi bi-chat me-1"></i>{{ $log->comment }}
                </div>
                @endif
            </div>

            {{-- Temps relatif --}}
            <div class="text-muted small text-nowrap flex-shrink-0 mt-1">
                {{ $log->created_at->diffForHumans() }}
            </div>
        </div>
    </div>
    @empty
    <div class="list-group-item border-0 text-center text-muted small py-4">
        <i class="bi bi-inbox me-1"></i>Aucune activité enregistrée
    </div>
    @endforelse
</div>
