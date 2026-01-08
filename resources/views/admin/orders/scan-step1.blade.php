@extends('admin.layout')

@section('title', 'Scan SKU Label - Step 1')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <h2 class="text-2xl font-bold mb-6">Scan SKU Label - Step 1</h2>

        <div class="max-w-2xl mx-auto">
            <form id="scanForm" class="space-y-6">
                @csrf

                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                        Scan or Enter SKU
                    </label>
                    <input type="text" name="sku" id="sku" autofocus
                           class="mt-1 block w-full text-2xl p-4 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Scan barcode or type SKU">
                    <p class="mt-2 text-sm text-gray-500">The system will automatically check if SKU exists</p>
                </div>

                <div id="skuStatus" class="hidden p-4 rounded-md"></div>

                <div id="fileUploadSection" class="hidden">
                    <label for="upload_file" class="block text-sm font-medium text-gray-700 mb-2">
                        Upload File <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="upload_file" id="upload_file" accept=".pdf,.jpg,.jpeg,.png" required
                           class="mt-1 block w-full">
                    <p class="mt-2 text-sm text-gray-500">Required: PDF, JPG, PNG (Max 10MB)</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.orders.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-6 rounded">
                        Cancel
                    </a>
                    <button type="submit" id="submitBtn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded hidden">
                        Save Order
                    </button>
                </div>
            </form>

            <div id="successMessage" class="hidden mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                Order saved successfully! Scanning next...
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const skuInput = document.getElementById('sku');
    const skuStatus = document.getElementById('skuStatus');
    const fileUploadSection = document.getElementById('fileUploadSection');
    const submitBtn = document.getElementById('submitBtn');
    const scanForm = document.getElementById('scanForm');
    const successMessage = document.getElementById('successMessage');
    let checkTimeout;

    skuInput.addEventListener('input', function() {
        clearTimeout(checkTimeout);
        const sku = this.value.trim();

        if (sku.length > 0) {
            checkTimeout = setTimeout(() => checkSku(sku), 500);
        } else {
            hideStatus();
        }
    });

    async function checkSku(sku) {
        try {
            const response = await fetch('{{ route('admin.orders.check-sku') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ sku: sku })
            });

            const data = await response.json();

            if (data.exists) {
                showStatus('error', 'SKU already exists in the system!');
                fileUploadSection.classList.add('hidden');
                submitBtn.classList.add('hidden');
            } else {
                showStatus('success', 'SKU is available');
                fileUploadSection.classList.remove('hidden');
                submitBtn.classList.remove('hidden');
            }
        } catch (error) {
            showStatus('error', 'Error checking SKU');
        }
    }

    function showStatus(type, message) {
        skuStatus.classList.remove('hidden', 'bg-red-100', 'border-red-400', 'text-red-700', 'bg-green-100', 'border-green-400', 'text-green-700');

        if (type === 'error') {
            skuStatus.classList.add('bg-red-100', 'border-red-400', 'text-red-700', 'border');
        } else {
            skuStatus.classList.add('bg-green-100', 'border-green-400', 'text-green-700', 'border');
        }

        skuStatus.textContent = message;
    }

    function hideStatus() {
        skuStatus.classList.add('hidden');
        fileUploadSection.classList.add('hidden');
        submitBtn.classList.add('hidden');
    }

    scanForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(scanForm);

        try {
            const response = await fetch('{{ route('admin.orders.store-scan') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                successMessage.classList.remove('hidden');
                scanForm.reset();
                hideStatus();

                setTimeout(() => {
                    successMessage.classList.add('hidden');
                    skuInput.focus();
                }, 2000);
            } else {
                // Show error message for duplicate order_id
                showStatus('error', data.message);
            }
        } catch (error) {
            alert('Error saving order');
        }
    });
</script>
@endpush
