<?php

namespace App\Http\Controllers;

use App\Services\PddiktiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PddiktiController extends Controller
{
    public function index(): View
    {
        return view('pddikti.index');
    }

    public function export(string $jenis, PddiktiService $feeder): StreamedResponse
    {
        abort_unless(in_array($jenis, ['mahasiswa', 'nilai', 'kelulusan'], true), 404);

        $path = $feeder->exportCsv($jenis);

        return Storage::download($path);
    }

    public function push(Request $request, PddiktiService $feeder): JsonResponse
    {
        $request->validate(['act' => 'required|string|max:60', 'record' => 'required|array']);

        return response()->json($feeder->push($request->act, $request->record));
    }
}
