<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->when($request->string('aksi')->toString(), fn ($q, $aksi) => $q->where('action', 'like', "%{$aksi}%"))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
