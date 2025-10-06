<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function getFeedbacks(Request $request)
    {
        $query = $request->input('search');

        $reports = Report::when($query, function ($queryBuilder) use ($query) {
            return $queryBuilder->where('title', 'like', "%{$query}%")
                ->orWhere('body', 'like', "%{$query}%");
        })
            ->paginate($request->per_page ?? 10);

        return response()->json(['status' => true, 'data' => $reports]);
    }
    public function viewFeedback($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['status' => false, 'message' => 'Report not found']);
        }

        return response()->json(['status' => true, 'data' => $report]);
    }
    public function deleteFeedback($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['status' => false, 'message' => 'Report not found']);
        }

        $report->delete();
        return response()->json(['status' => true, 'message' => 'Report deleted successfully']);
    }
}
