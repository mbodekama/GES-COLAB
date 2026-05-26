<?php

namespace App\Http\Controllers;

use App\Models\SalaryGrid;
use Illuminate\Http\Request;

class SalaryGridController extends Controller
{
    public function index()
    {
        $grids = SalaryGrid::orderByDesc('level')->paginate(20);
        return view('paie.grilles', compact('grids'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'category'            => 'nullable|string|max:60',
            'level'               => 'required|integer|min:1|max:10',
            'min_salary'          => 'required|numeric|min:0',
            'max_salary'          => 'required|numeric|min:0|gte:min_salary',
            'base_salary'         => 'required|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'housing_allowance'   => 'nullable|numeric|min:0',
            'meal_allowance'      => 'nullable|numeric|min:0',
            'description'         => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        SalaryGrid::create($validated);

        return redirect()->route('salary-grids.index')
                         ->with('success', 'Grille salariale créée.');
    }

    public function update(Request $request, SalaryGrid $salaryGrid)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'category'            => 'nullable|string|max:60',
            'level'               => 'required|integer|min:1|max:10',
            'min_salary'          => 'required|numeric|min:0',
            'max_salary'          => 'required|numeric|min:0|gte:min_salary',
            'base_salary'         => 'required|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'housing_allowance'   => 'nullable|numeric|min:0',
            'meal_allowance'      => 'nullable|numeric|min:0',
            'description'         => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        $salaryGrid->update($validated);

        return redirect()->route('salary-grids.index')
                         ->with('success', 'Grille mise à jour.');
    }

    public function destroy(SalaryGrid $salaryGrid)
    {
        if ($salaryGrid->contracts()->exists()) {
            return back()->with('error', 'Impossible de supprimer une grille utilisée par des contrats.');
        }

        $salaryGrid->delete();

        return redirect()->route('salary-grids.index')
                         ->with('success', 'Grille supprimée.');
    }
}
