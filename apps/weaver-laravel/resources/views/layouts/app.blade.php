<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dynamic Static</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" />
</head>
<body class="bg-gray-100 text-gray-900">
<nav class="bg-white shadow mb-6">
  <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center">
    <div class="flex items-center space-x-6">
      <a href="{{ route('dashboard') }}" class="font-bold text-lg">Dynamic Static</a>
      @auth
        <a href="{{ route('dashboard') }}" class="text-sm {{ request()->routeIs('dashboard') ? 'font-semibold' : '' }}">Dashboard</a>
        @php($hasProject = (bool) auth()->user()->project)
        <a href="{{ $hasProject ? route('project.edit') : route('project.setup') }}" class="text-sm {{ request()->routeIs('project.*') ? 'font-semibold' : '' }}">Project</a>
      @endauth
    </div>
    <div class="flex items-center space-x-4">
      @auth
        <span class="text-sm text-gray-600 hidden sm:inline">{{ auth()->user()->email }}</span>
        <form method="POST" action="{{ route('logout') }}" class="inline">
          @csrf
          <button class="text-sm text-red-600" onclick="return confirm('Log out?')">Logout</button>
        </form>
      @else
        <a href="{{ route('login') }}" class="text-sm">Login</a>
      @endauth
    </div>
  </div>
</nav>
<main class="max-w-3xl mx-auto px-4 mb-12">
    @if(session('success'))
      <div class="mb-4 p-3 border border-green-300 bg-green-50 text-green-800 rounded">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
      <div class="mb-4 p-3 border border-red-300 bg-red-50 text-red-700 rounded">
        <strong class="block mb-1">There were some problems with your submission:</strong>
        <ul class="list-disc pl-5 space-y-0.5 text-sm">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif
    @yield('content')
</main>
</body>
</html>
