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

        $countsByStatus = Order::where('store_id', $store->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusCounts = [
            'all' => (int) $countsByStatus->sum(),
            'pending' => (int) ($countsByStatus['pending'] ?? 0),
            'paid' => (int) ($countsByStatus['paid'] ?? 0),
            'processing' => (int) ($countsByStatus['processing'] ?? 0),
            'completed' => (int) ($countsByStatus['completed'] ?? 0),
            'cancelled' => (int) ($countsByStatus['cancelled'] ?? 0),
        ];

        $orders = Order::where('store_id', $store->id)
            ->with(['product', 'items'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->q, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('order_code', 'like', "%{$term}%")
                        ->orWhere('buyer_name', 'like', "%{$term}%")
                        ->orWhere('buyer_phone', 'like', "%{$term}%")
                        ->orWhere('product_name', 'like', "%{$term}%")
                        ->orWhere('tracking_number', 'like', "%{$term}%");
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
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $oldStatus = $order->status;
        $updates = ['status' => $validated['status']];

        if (array_key_exists('tracking_number', $validated)) {
            $tracking = trim((string) ($validated['tracking_number'] ?? ''));
            $updates['tracking_number'] = $tracking !== '' ? $tracking : null;
            if ($tracking !== '' && ! $order->shipped_at) {
                $updates['shipped_at'] = now();
            }
        }

        $order->update($updates);

        AuditLogger::log(
            'order.status_updated',
            $order,
            "Status pesanan {$order->order_code} diubah dari {$oldStatus} ke {$order->status}.",
            [
                'order_code' => $order->order_code,
                'old_status' => $oldStatus,
                'new_status' => $order->status,
                'tracking_number' => $order->tracking_number,
            ],
            $request->user(),
            $store->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'tracking_number' => $order->tracking_number,
                'message' => "Status pesanan {$order->order_code} berhasil diperbarui.",
            ]);
        }

        return back()->with('status', "Status pesanan {$order->order_code} berhasil diperbarui.");
    }

    public function updateTracking(Store $store, Order $order, Request $request)
    {
        $this->authorize('update', $store);

        if ($order->store_id !== $store->id) {
            abort(404);
        }

        $validated = $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'mark_processing' => ['nullable', 'boolean'],
        ]);

        $tracking = trim((string) ($validated['tracking_number'] ?? ''));
        $tracking = $tracking !== '' ? $tracking : null;

        $oldTracking = $order->tracking_number;
        $oldStatus = $order->status;

        $updates = [
            'tracking_number' => $tracking,
        ];

        if ($tracking !== null) {
            if (! $order->shipped_at) {
                $updates['shipped_at'] = now();
            }
            if ($request->boolean('mark_processing') && in_array($order->status, ['pending', 'paid'], true)) {
                $updates['status'] = 'processing';
            }
        } else {
            $updates['shipped_at'] = null;
        }

        $order->update($updates);

        AuditLogger::log(
            'order.tracking_updated',
            $order,
            $tracking !== null
                ? "Nomor resi pesanan {$order->order_code} diperbarui menjadi {$tracking}."
                : "Nomor resi pesanan {$order->order_code} dihapus.",
            [
                'order_code' => $order->order_code,
                'old_tracking_number' => $oldTracking,
                'new_tracking_number' => $order->tracking_number,
                'old_status' => $oldStatus,
                'new_status' => $order->status,
            ],
            $request->user(),
            $store->id
        );

        $msg = $tracking !== null
            ? "Nomor resi pesanan {$order->order_code} ({$tracking}) berhasil disimpan."
            : "Nomor resi pesanan {$order->order_code} dihapus.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'tracking_number' => $order->tracking_number,
                'message' => $msg,
            ]);
        }

        return back()->with('status', $msg);
    }
}
