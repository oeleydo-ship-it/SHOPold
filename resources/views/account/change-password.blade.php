@extends('layouts.account')

@section('title', 'Change Password')

@section('account_heading', 'Change Password')

@section('account_content')
            <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 max-w-lg">
                <form action="{{ route('account.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                            <input type="password" name="current_password" required class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                            @error('current_password')<p class="text-red-500 text-xs md:text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="password" required class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                            @error('password')<p class="text-red-500 text-xs md:text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="password_confirmation" required class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                        </div>
                        <button type="submit" class="w-full md:w-auto px-6 py-2.5 md:py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm md:text-base">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
@endsection
