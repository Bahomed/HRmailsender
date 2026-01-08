@extends('admin.layout')

@section('title', 'Scan & Print')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <h2 class="text-2xl font-bold mb-6">Scan Barcode & Print</h2>

        <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <label for="barcodeScan" class="block text-sm font-medium text-gray-700 mb-2">
                    Scan Barcode
                </label>
                <input type="text" id="barcodeScan" autofocus
                       class="mt-1 block w-full text-2xl p-4 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Scan barcode to find order">
                <p class="mt-2 text-sm text-gray-500">Scan the barcode or type SKU to find and print the order</p>
            </div>

            <div id="orderNotFound" class="hidden p-4 bg-red-100 border border-red-400 text-red-700 rounded mb-4">
                Order not found!
            </div>

            <div id="orderDetails" class="hidden">
                <!-- Print Button at Top -->
                <div class="flex justify-between items-center mb-4">
                    <button id="printBtn" onclick="printUploadedPdf()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-8 rounded text-lg">
                        🖨️ Print PDF
                    </button>
                    <button onclick="resetScan()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-6 rounded">
                        Scan Another
                    </button>
                </div>

                <div class="border rounded-lg p-6 mb-4" id="orderContent">
                    <h3 class="text-xl font-bold mb-4">Order Details</h3>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Order ID</p>
                            <p class="font-semibold" id="orderId"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">SKU</p>
                            <p class="font-semibold font-mono" id="orderSku"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <p class="font-semibold" id="orderStatus"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Scanned At</p>
                            <p class="font-semibold" id="orderScannedAt"></p>
                        </div>
                    </div>

                    <!-- PDF Viewer -->
                    <div id="pdfViewerSection" class="hidden">
                        <div class="mb-4">
                            <iframe id="pdfViewer" style="width: 100%; height: 600px; border: 1px solid #ddd;"></iframe>
                        </div>
                    </div>

                    <!-- No File Message -->
                    <div id="noFileMessage" class="hidden p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded">
                        No file uploaded for this order.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const barcodeInput = document.getElementById('barcodeScan');
    const orderDetails = document.getElementById('orderDetails');
    const orderNotFound = document.getElementById('orderNotFound');
    let currentOrder = null;

    barcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const sku = this.value.trim();
            if (sku) {
                findOrder(sku);
            }
        }
    });

    async function findOrder(sku) {
        try {
            const response = await fetch('{{ route('admin.orders.find-by-sku') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ sku: sku })
            });

            const data = await response.json();

            if (data.success) {
                currentOrder = data.order;
                displayOrder(data.order);
                orderNotFound.classList.add('hidden');
            } else {
                orderDetails.classList.add('hidden');
                orderNotFound.classList.remove('hidden');
                setTimeout(() => {
                    orderNotFound.classList.add('hidden');
                    barcodeInput.value = '';
                    barcodeInput.focus();
                }, 2000);
            }
        } catch (error) {
            console.error('Error finding order:', error);
            orderNotFound.classList.remove('hidden');
        }
    }

    function displayOrder(order) {
        document.getElementById('orderId').textContent = order.id;
        document.getElementById('orderSku').textContent = order.sku;
        document.getElementById('orderStatus').textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
        document.getElementById('orderScannedAt').textContent = order.scanned_at ? new Date(order.scanned_at).toLocaleString() : 'N/A';

        const pdfViewerSection = document.getElementById('pdfViewerSection');
        const noFileMessage = document.getElementById('noFileMessage');
        const pdfViewer = document.getElementById('pdfViewer');
        const printBtn = document.getElementById('printBtn');

        orderDetails.classList.remove('hidden');

        if (order.upload_file) {
            const fileUrl = '/storage/' + order.upload_file;
            const fileExt = order.upload_file.split('.').pop().toLowerCase();

            if (fileExt === 'pdf') {
                // Display PDF in iframe
                pdfViewer.src = fileUrl;
                pdfViewerSection.classList.remove('hidden');
                noFileMessage.classList.add('hidden');
                printBtn.classList.remove('hidden');

                // Auto-print after PDF loads
                pdfViewer.onload = function() {
                    setTimeout(() => {
                        autoPrint();
                    }, 1000); // Wait 1 second for PDF to fully load
                };
            } else if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
                // Display image in iframe
                pdfViewer.src = fileUrl;
                pdfViewerSection.classList.remove('hidden');
                noFileMessage.classList.add('hidden');
                printBtn.classList.remove('hidden');

                // Auto-print after image loads
                pdfViewer.onload = function() {
                    setTimeout(() => {
                        autoPrint();
                    }, 500);
                };
            } else {
                pdfViewerSection.classList.add('hidden');
                noFileMessage.classList.remove('hidden');
                printBtn.classList.add('hidden');
            }
        } else {
            pdfViewerSection.classList.add('hidden');
            noFileMessage.classList.remove('hidden');
            printBtn.classList.add('hidden');
        }

        barcodeInput.value = '';
    }

    async function autoPrint() {
        // Automatically trigger print
        await printUploadedPdf();
    }

    async function printUploadedPdf() {
        if (currentOrder && currentOrder.upload_file) {
            const pdfViewer = document.getElementById('pdfViewer');

            try {
                // Print the iframe content directly
                pdfViewer.contentWindow.focus();
                pdfViewer.contentWindow.print();

                // Mark order as printed and update status to completed
                await markAsPrinted(currentOrder.id);
            } catch (e) {
                // Fallback: if iframe print fails, open in new tab
                const fileUrl = '/storage/' + currentOrder.upload_file;
                window.open(fileUrl, '_blank');

                // Mark order as printed
                await markAsPrinted(currentOrder.id);
            }
        }
    }

    async function markAsPrinted(orderId) {
        try {
            const response = await fetch('{{ route('admin.orders.mark-as-printed') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ order_id: orderId })
            });

            const data = await response.json();

            if (data.success) {
                // Update the order status in the UI
                document.getElementById('orderStatus').textContent = 'Completed';
                currentOrder.status = 'completed';

                // Show success message
                console.log('Order marked as completed');

                // After a short delay, reset and focus back to scan input
                setTimeout(() => {
                    resetScan();
                }, 2000); // Wait 2 seconds before resetting
            }
        } catch (error) {
            console.error('Error marking order as printed:', error);
        }
    }

    function resetScan() {
        orderDetails.classList.add('hidden');
        currentOrder = null;
        barcodeInput.value = '';
        barcodeInput.focus();
    }
</script>
@endpush
