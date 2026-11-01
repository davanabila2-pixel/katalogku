<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Store $store, Request $request)
    {
        $this->authorize('update', $store);

        $statusCounts = [
            'all' => Order::where('store_id', $store->id)->count(),
            'pending' => Order::where('store_id', $store->id)->where('status', 'pending')->count(),
            'paid' => Order::where('store_id', $store->id)->where('status', 'paid')->count(),
            'completed' => Order::where('store_id', $store->id)->where('status', 'completed')->count(),
            'cancelled' => Order::where('store_id', $store->id)->where('status', 'cancelled')->count(),
        ];

        $orders = Order::where('store_id', $store->id)
            ->with(['product'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->q, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('order_code', 'like', "%{$term}%")
                        ->orWhere('buyer_name', 'like', "%{$term}%")
                        ->orWhere('buyer_phone', 'like', "%{$term}%")
                        ->orWhere('product_name', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('store', 'orders', 'statusCounts'));
    }

    public function updateStatus(Store $store, Order $order, Request $request)
    {
        $this->authorize('update', $store);

        if ($order->store_id !== $store->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'paid', 'processing', 'completed', 'cancelled'])],
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        AuditLogger::log(
            'order.status_updated',
            $order,
            "Status pesanan {$order->order_code} diubah dari {$oldStatus} ke {$order->status}.",
            ['order_code' => $order->order_code, 'old_status' => $oldStatus, 'new_status' => $order->status],
            $request->user(),
            $store->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'message' => "Status pesanan {$order->order_code} berhasil diperbarui.",
            ]);
        }

        return back()->with('status', "Status pesanan {$order->order_code} berhasil diperbarui.");
    }
}
