<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function basicInfo(Request $request)
    {
        try {


            $data = [
                'total_users' => User::all()->count(),
                'pending_orders' => User::all()->count(),
                'completed_order' => User::all()->count(),
                'total_revenue' => User::all()->count(),
                'users' => User::latest()->paginate($request->per_page ?? 10),
            ];

            return $this->sendResponse($data, 'Get basic info.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
