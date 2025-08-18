@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-4">Register</h1>
<form method="POST" action="/auth/self/register" class="space-y-4">
    @csrf
    <div>
        <label class="block font-semibold">Name (optional)</label>
        <input type="text" name="name" value="{{ old('name') }}" class="border p-2 w-full">
        @error('name')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block font-semibold">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="border p-2 w-full" required>
        @error('email')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block font-semibold">Password</label>
        <input type="password" name="password" class="border p-2 w-full" required>
        @error('password')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block font-semibold">Confirm Password</label>
        <input type="password" name="password_confirmation" class="border p-2 w-full" required>
    </div>
    <button class="bg-blue-600 text-white px-4 py-2 rounded">Create Account</button>
</form>
<div class="mt-6 text-sm">
    <a href="/login" class="text-blue-600 underline">Already have an account? Login</a>
</div>
<div class="mt-8 text-gray-500 text-sm">
    <p class="opacity-70">Social login (Google / Microsoft) coming soon (disabled).</p>
</div>
@endsection
