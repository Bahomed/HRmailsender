@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        <h2 class="text-2xl font-bold mb-6">Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-100 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-blue-800">Total Users</h3>
                <p class="text-3xl font-bold text-blue-600">{{ $stats['total_users'] }}</p>
            </div>

            <div class="bg-green-100 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-green-800">Total Orders</h3>
                <p class="text-3xl font-bold text-green-600">{{ $stats['total_orders'] }}</p>
            </div>

            <div class="bg-yellow-100 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-yellow-800">Pending Orders</h3>
                <p class="text-3xl font-bold text-yellow-600">{{ $stats['pending_orders'] }}</p>
            </div>

            <div class="bg-purple-100 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-purple-800">Completed Orders</h3>
                <p class="text-3xl font-bold text-purple-600">{{ $stats['completed_orders'] }}</p>
            </div>
        </div>

        <div class="mt-8">
            <h3 class="text-xl font-bold mb-4">Quick Actions</h3>
            <div class="flex space-x-4">
                <a href="{{ route('admin.orders.scan-step1') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Scan New Label
                </a>
                <a href="{{ route('admin.orders.scan-print') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Scan & Print
                </a>
                <a href="{{ route('admin.users.create') }}" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                    Add New User
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
