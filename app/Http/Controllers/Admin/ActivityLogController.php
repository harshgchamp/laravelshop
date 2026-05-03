<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = ActivityLog::query()
            ->with('user:id,name,email')   // eager-load performer — name shown in the table
            ->latest()                      // newest first — most useful for debugging
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/ActivityLog/Index', [
            'logs' => $logs,
        ]);
    }
}
