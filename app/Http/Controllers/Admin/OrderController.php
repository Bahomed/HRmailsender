<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('sku', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('scanned_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scanned_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $orders = $query->paginate($perPage)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function scanStep1()
    {
        return view('admin.orders.scan-step1');
    }

    public function checkSku(Request $request)
    {
        $request->validate([
            'sku' => 'required|string',
        ]);

        $exists = Order::where('sku', $request->sku)->exists();

        if ($exists) {
            return response()->json([
                'exists' => true,
                'message' => 'SKU already exists in the system!',
            ], 409);
        }

        return response()->json([
            'exists' => false,
            'message' => 'SKU is available',
        ]);
    }

    public function storeScan(Request $request)
    {
        $request->validate([
            'sku' => 'required|string|unique:orders,sku',
            'upload_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $data = [
            'sku' => $request->sku,
            'status' => 'pending',
            'scanned_at' => now(),
        ];

        if ($request->hasFile('upload_file')) {
            $file = $request->file('upload_file');
            $filename = $request->sku . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('orders', $filename, 'public');
            $data['upload_file'] = $path;
        }

        $order = Order::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Order scanned successfully',
            'order' => $order,
        ]);
    }

    public function scanPrint()
    {
        return view('admin.orders.scan-print');
    }

    public function findBySku(Request $request)
    {
        $request->validate([
            'sku' => 'required|string',
        ]);

        $order = Order::where('sku', $request->sku)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    public function generatePdf(Order $order)
    {
        return view('admin.orders.pdf', compact('order'));
    }

    public function markAsPrinted(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($order->status !== 'completed') {
            $order->update([
                'status' => 'completed',
                'printed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order marked as completed',
            'order' => $order,
        ]);
    }

    public function destroy(Order $order)
    {
        if ($order->upload_file) {
            Storage::disk('public')->delete($order->upload_file);
        }

        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully');
    }
}
