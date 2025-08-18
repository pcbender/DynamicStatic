@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-4">Project Setup</h1>
<p class="text-sm text-gray-600 mb-6">Provide GitHub repository metadata so Dynamic Static can operate on your project later. Secrets (private keys) are managed separately.</p>
<form method="POST" action="{{ route('project.setup.store') }}" class="space-y-4">
    @csrf
    <div>
        <label class="block font-semibold">Project Name</label>
        <input name="name" value="{{ old('name') }}" placeholder="Dynamic Static: Relational Design" class="border p-2 w-full rounded" required>
        @error('name')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block font-semibold">Owner <span class="text-gray-400 text-xs">Org or username</span></label>
        <input name="owner" value="{{ old('owner') }}" placeholder="pcbender" class="border p-2 w-full rounded" required>
        @error('owner')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block font-semibold">Repository</label>
        <input name="repo" value="{{ old('repo') }}" placeholder="RelationalDesign" class="border p-2 w-full rounded" required>
        @error('repo')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block font-semibold">GitHub App ID <span class="text-gray-400 text-xs">Numeric</span></label>
        <input name="github_app_id" value="{{ old('github_app_id') }}" placeholder="1783044" class="border p-2 w-full rounded" required>
        @error('github_app_id')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block font-semibold">GitHub App Client ID</label>
        <input name="github_app_client_id" value="{{ old('github_app_client_id') }}" placeholder="Iv23abcd..." class="border p-2 w-full rounded" required>
        @error('github_app_client_id')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <button class="bg-blue-600 text-white px-4 py-2 rounded">Save Project</button>
</form>
@endsection
