<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class UserOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $orders = Order::where('user_id', $user->id)
            ->with(['store', 'product'])
            ->latest()
            ->paginate(10);

        return view('user.orders', compact('orders', 'user'));
    }
}

