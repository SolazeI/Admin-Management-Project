<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a paginated, filterable list of activity logs.
     * Accepts optional query params: subject_type, action, date_from, date_to
     */
    public function index(Request $request)
    {
        try {
            $query = ActivityLog::orderByDesc('logged_at');

            if ($subjectType = $request->get('subject_type')) {
                $query->where('subject_type', $subjectType);
            }
            if ($action = $request->get('action')) {
                $query->where('action', $action);
            }
            if ($dateFrom = $request->get('date_from')) {
                $query->whereDate('logged_at', '>=', $dateFrom);
            }
            if ($dateTo = $request->get('date_to')) {
                $query->whereDate('logged_at', '<=', $dateTo);
            }

            $logs = $query->paginate(50)->withQueryString();
            $subjectTypes = ActivityLog::distinct()->pluck('subject_type')->filter()->sort()->values();
            $actions      = ActivityLog::distinct()->pluck('action')->filter()->sort()->values();

            return view('logs', compact('logs', 'subjectTypes', 'actions'));

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], 500);
        }
    }

    /**
     * Return the full detail of a single log entry (JSON, for a modal/drawer).
     */
    public function show($id)
    {
        $log = ActivityLog::findOrFail($id);
        return response()->json($log);
    }
}