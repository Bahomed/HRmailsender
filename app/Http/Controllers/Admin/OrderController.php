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

        // SKU duplicates are now allowed, always return available
        return response()->json([
            'exists' => false,
            'message' => 'SKU is available',
        ]);
    }

    public function storeScan(Request $request)
    {
        $request->validate([
            'sku' => 'required|string',
            'upload_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $data = [
            'sku' => $request->sku,
            'status' => 'pending',
            'scanned_at' => now(),
        ];

        if ($request->hasFile('upload_file')) {
            $file = $request->file('upload_file');
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Check if order_id already exists
            if (Order::where('order_id', $originalFilename)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID (filename) already exists! Please rename the file.',
                ], 409);
            }

            // Store the original filename (without extension) as order_id
            $data['order_id'] = $originalFilename;

            // Use order_id as the filename
            $filename = $originalFilename . '_' . time() . '.' . $file->getClientOriginalExtension();
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

        // Find the first PENDING order with this SKU
        $order = Order::where('sku', $request->sku)
                      ->where('status', 'pending')
                      ->orderBy('created_at', 'asc')
                      ->first();

        if (!$order) {
            // Check if any order exists with this SKU
            $completedOrder = Order::where('sku', $request->sku)->first();

            if ($completedOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'All orders with this SKU have been printed',
                ], 404);
            }

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

    public function printOrderList(Request $request)
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

        // Get all orders (no pagination for print)
        $orders = $query->get();

        return view('admin.orders.print-list', compact('orders'));
    }
}
