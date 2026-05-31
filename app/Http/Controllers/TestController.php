<?php

namespace App\Http\Controllers;

use App\Data\TestScenarios;
use App\Pdf\TestRapport;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        $this->logEntry();

        $modules  = TestScenarios::modules();
        $total    = count(TestScenarios::all());
        $allIds   = TestScenarios::ids();

        return view('tests.index', compact('modules', 'total', 'allIds'));
    }

    public function show(string $id)
    {
        $this->logEntry(['scenario' => $id]);

        $scenario = TestScenarios::find($id);
        abort_unless($scenario !== null, 404);

        $allIds  = TestScenarios::ids();
        $pos     = array_search($id, $allIds);
        $prevId  = $pos > 0 ? $allIds[$pos - 1] : null;
        $nextId  = $pos < count($allIds) - 1 ? $allIds[$pos + 1] : null;

        return view('tests.show', compact('scenario', 'prevId', 'nextId', 'allIds'));
    }

    public function rapport(Request $request)
    {
        $this->logEntry();

        $request->validate([
            'results' => ['required', 'string'],
        ]);

        $results = json_decode($request->input('results'), true) ?? [];
        $tester  = auth()->user()->name;
        $date    = now()->isoFormat('D MMMM YYYY');

        $scenarios = TestScenarios::all();

        $data = [
            'company_name'     => setting('company_name', 'GES-COLAB'),
            'company_initials' => setting('company_initials', ''),
            'company_address'  => setting('company_address', ''),
            'company_phone'    => setting('company_phone', ''),
            'company_website'  => setting('company_website', ''),
            'generated_date'   => $date,
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