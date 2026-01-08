<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order List - Print</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header .meta {
            font-size: 9pt;
            color: #666;
            margin-top: 5px;
        }

        .info-section {
            text-align: center;
            margin-bottom: 15px;
            font-size: 9pt;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th {
            background-color: #e5e7eb;
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        td {
            border: 1px solid #666;
            padding: 5px 4px;
            font-size: 9pt;
            vertical-align: top;
        }

        /* Column widths */
        th:nth-child(1), td:nth-child(1) { width: 5%; }  /* ID */
        th:nth-child(2), td:nth-child(2) { width: 20%; } /* Order ID */
        th:nth-child(3), td:nth-child(3) { width: 25%; } /* SKU */
        th:nth-child(4), td:nth-child(4) { width: 15%; } /* Status */
        th:nth-child(5), td:nth-child(5) { width: 20%; } /* Scanned At */
        th:nth-child(6), td:nth-child(6) { width: 15%; } /* File */

        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
            display: inline-block;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .sku-code {
            font-family: "Courier New", monospace;
            font-size: 9pt;
        }

        .order-id {
            font-weight: 600;
            color: #2563eb;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }

        .no-records {
            text-align: center;
            padding: 40px;
            font-size: 11pt;
            color: #666;
        }
    </style>
</head>
<body onload="window.print();">
    <div class="header">
        <h1>Orders Management</h1>
        <div class="meta">
            Printed on: {{ now()->format('F d, Y \a\t H:i') }}
        </div>
    </div>

    <div class="info-section">
        <strong>Total Orders: {{ $orders->count() }}</strong>
    </div>

    @if($orders->count() > 0)
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Order ID</th>
                <th>SKU</th>
                <th>Status</th>
                <th>Scanned At</th>
                <th>File</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td class="order-id">{{ $order->order_id ?? 'N/A' }}</td>
                <td class="sku-code">{{ $order->sku }}</td>
                <td>
                    <span class="status-badge {{ $order->status === 'completed' ? 'status-completed' : 'status-pending' }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </td>
                <td>{{ $order->scanned_at?->format('Y-m-d H:i') }}</td>
                <td>
                    @if($order->upload_file)
                        <span>Available</span>
                    @else
                        <span style="color: #999;">No file</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-records">
        No orders found to display.
    </div>
    @endif

    <div class="footer">
        <p>Order Management System</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>
