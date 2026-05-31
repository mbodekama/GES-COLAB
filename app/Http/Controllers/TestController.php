<?php

namespace App\Http\Controllers;

use App\Data\TestScenarios;
use App\Pdf\TestRapport;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        $modules = TestScenarios::modules();
        $total   = count(TestScenarios::all());
        $allIds  = TestScenarios::ids();

        return view('tests.index', compact('modules', 'total', 'allIds'));
    }

    public function show(string $id)
    {
        $scenario = TestScenarios::find($id);
        abort_unless($scenario !== null, 404);

        $allIds = TestScenarios::ids();
        $pos    = array_search($id, $allIds);
        $prevId = $pos > 0 ? $allIds[$pos - 1] : null;
        $nextId = $pos < count($allIds) - 1 ? $allIds[$pos + 1] : null;

        return view('tests.show', compact('scenario', 'prevId', 'nextId', 'allIds'));
    }

    public function rapport(Request $request)
    {
        $request->validate([
            'results'     => ['required', 'string'],
            'tester_name' => ['nullable', 'string', 'max:100'],
        ]);

        $results   = json_decode($request->input('results'), true) ?? [];
        $tester    = $request->filled('tester_name')
                        ? $request->input('tester_name')
                        : (auth()->check() ? auth()->user()->name : 'Testeur anonyme');
        $scenarios = TestScenarios::all();

        $data = [
            'company_name'     => setting('company_name', 'GES-COLAB'),
            'company_initials' => setting('company_initials', ''),
            'company_address'  => setting('company_address', ''),
            'company_phone'    => setting('company_phone', ''),
            'company_website'  => setting('company_website', ''),
            'generated_date'   => now()->isoFormat('D MMMM YYYY'),
            'tester'           => $tester,
            'scenarios'        => $scenarios,
            'results'          => $results,
        ];

        ob_start();
        $content = (new TestRapport($data))->build()->Output('S', '');
        ob_end_clean();

        $filename = 'rapport-tests-' . now()->format('Ymd-Hi') . '.pdf';

        return response()->make($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
            'Content-Length'      => strlen($content),
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }
}