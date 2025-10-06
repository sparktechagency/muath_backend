<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Metadata;
use App\Models\Transaction;
use Illuminate\Http\Request;

use function Laravel\Prompts\select;

class TransactionController extends Controller
{
    public function getTransactions(Request $request)
    {
        $search = $request->input('search', '');

        $transactions = Transaction::with(['user' => function ($q) {
            $q->select('id', 'full_name', 'role', 'avatar'); },'metadata'])  // Ensure user relationship is loaded
            ->when($search, function ($query) use ($search) {
                return $query->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Get transactions',
            'orders' => $transactions
        ]);
    }
}
