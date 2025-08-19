@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-4">Login</h1>
<form method="POST" action="/auth/self/login" class="space-y-4">
    @csrf
    <div>
        <label class="block font-semibold">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="border p-2 w-full" required autofocus>
        @error('email')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block font-semibold">Password</label>
        <input type="password" name="password" class="border p-2 w-full" required>
        @error('password')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div class="flex items-center justify-between">
        <label class="inline-flex items-center space-x-2 text-sm"><input type="checkbox" name="remember"> <span>Remember me</span></label>
        <a href="/forgot-password" class="text-sm text-blue-600">Forgot?</a>
    </div>
    <button class="bg-blue-600 text-white px-4 py-2 rounded">Login</button>
</form>
<div class="mt-6 text-sm">
    <a href="/register" class="text-blue-600 underline">Create an account</a>
</div>
<div class="mt-8 text-gray-500 text-sm">
    <p class="opacity-70">Social login (Google / Microsoft) coming soon (disabled).</p>
</div>
@endsection
