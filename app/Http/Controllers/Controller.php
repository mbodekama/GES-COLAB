<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Trace l'entrée dans une action de contrôleur.
     * À appeler en première ligne de toute action à surveiller.
     *
     * @param  array<string, mixed>  $extra  Données métier supplémentaires à joindre au log.
     */
    protected function logEntry(array $extra = []): void
    {
        $frame  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        $caller = class_basename($frame['class'] ?? static::class).'@'.($frame['function'] ?? '?');

        Log::debug("[TRACE] {$caller}", array_merge([
            'http_method' => request()->method(),
//            'url'         => request()->fullUrl(),
//            'user_id'     => auth()->id(),
//            'ip'          => request()->ip(),
        ], $extra));
    }
}
