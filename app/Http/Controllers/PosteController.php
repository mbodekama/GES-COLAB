<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Poste;
use Illuminate\Http\Request;

class PosteController extends Controller
{
    public function index(Request $request)
    {
        $allowed = ['title', 'department', 'level'];
        $sortBy  = in_array($request->get('sort_by'), $allowed) ? $request->get('sort_by') : 'level';
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';

        $postes = Poste::withCount('employees')
                       ->orderBy($sortBy, $sortDir)
                       ->paginate(20);

        return view('postes.index', compact('postes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:100',
            'code'        => 'required|string|max:20|unique:postes,code',
            'department'  => 'nullable|string|max:100',
            'level'       => 'required|integer|min:1|max:10',
            'can_be_n1'   => 'nullable|boolean',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['code']      = strtoupper($validated['code']);
        $validated['can_be_n1'] = $request->boolean('can_be_n1');
        $validated['is_active'] = $request->boolean('is_active', true);

        Poste::create($validated);

        return redirect()->route('postes.index')
                         ->with('success', 'Poste créé avec succès.');
    }

    public function update(Request $request, Poste $poste)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:100',
            'code'        => 'required|string|max:20|unique:postes,code,'.$poste->id,
            'department'  => 'nullable|string|max:100',
            'level'       => 'required|integer|min:1|max:10',
            'can_be_n1'   => 'nullable|boolean',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['code']      = strtoupper($validated['code']);
        $validated['can_be_n1'] = $request->boolean('can_be_n1');
        $validated['is_active'] = $request->boolean('is_active', true);

        $poste->update($validated);

        return redirect()->route('postes.index')
                         ->with('success', 'Poste mis à jour.');
    }

    public function destroy(Poste $poste)
    {
        if ($poste->employees()->exists()) {
            return back()->with('error',
                "Impossible de supprimer le poste « {$poste->title} » "
                ."car il est attribué à des employés."
            );
        }

        $poste->delete();

        return redirect()->route('postes.index')
                         ->with('success', 'Poste supprimé.');
    }

    // ── API : N+1 disponibles pour un poste donné ─────────────
    public function getN1ForPoste(Poste $poste)
    {
        $n1List = Employee::active()
            ->whereHas('poste', function ($q) use ($poste) {
                $q->where('can_be_n1', true)
                  ->where('level', '>', $poste->level);
            })
            ->with('poste')
            ->orderBy('last_name')
            ->get();

        return response()->json(
            $n1List->map(fn($e) => [
                'id'         => $e->id,
                'name'       => $e->full_name,
                'poste'      => $e->poste?->title ?? $e->position,
                'level'      => $e->poste?->level ?? 0,
                'department' => $e->department,
            ])
        );
    }
}
