@extends('layouts.app')
@section('content')
<h1 class="text-xl font-bold mb-4">Page Not Found (404)</h1>
<p class="mb-4">The page you requested could not be found.</p>
@auth
<a href="{{ route('dashboard') }}" class="text-blue-600 underline">Back to Dashboard</a>
@else
<a href="{{ route('login') }}" class="text-blue-600 underline">Login</a>
@endauth
@endsection
