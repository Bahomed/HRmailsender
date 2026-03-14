<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order {{ $order->sku }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .section-content {
            font-size: 18px;
            font-weight: bold;
        }
        .sku-box {
            text-align: center;
            background: #f0f0f0;
            padding: 20px;
            margin: 30px 0;
            border: 2px solid #333;
        }
        .sku-box .sku {
            font-size: 36px;
            font-weight: bold;
            font-family: monospace;
            letter-spacing: 2px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body {
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ORDER DETAILS</h1>
        </div>

        <div class="section">
            <div class="section-title">Order ID</div>
            <div class="section-content">#{{ $order->id }}</div>
        </div>

        <div class="sku-box">
            <div style="margin-bottom: 10px;">SKU</div>
            <div class="sku">{{ $order->sku }}</div>
        </div>

        <div class="section">
            <div class="section-title">Status</div>
            <div class="section-content">{{ ucfirst($order->status) }}</div>
        </div>

        <div class="section">
            <div class="section-title">Scanned At</div>
            <div class="section-content">{{ $order->scanned_at?->format('Y-m-d H:i:s') }}</div>
        </div>

        @if($order->upload_file)
        <div class="section">
            <div class="section-title">File Attached</div>
            <div class="section-content">Yes</div>
        </div>
        @endif

        <div class="footer">
            <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #4CAF50; color: white; border: none; border-radius: 5px;">
            Print This Page
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #666; color: white; border: none; border-radius: 5px; margin-left: 10px;">
            Close
        </button>
    </div>

    <script>
        // Auto print when page loads (optional - remove if not wanted)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>
