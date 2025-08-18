@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-4">Edit Project</h1>
<p class="text-sm text-gray-600 mb-6">Update repository metadata. GitHub app private keys are managed outside the database.</p>
<form method="POST" action="{{ route('project.update') }}" class="space-y-4">
    @csrf
    @method('PATCH')
    <div>
    <label class="block font-semibold">Project Name</label>
    <input name="name" value="{{ old('name', $project->name) }}" placeholder="Dynamic Static: Relational Design" class="border p-2 w-full rounded" required>
        @error('name')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
    <label class="block font-semibold">Owner</label>
    <input name="owner" value="{{ old('owner', $project->owner) }}" placeholder="pcbender" class="border p-2 w-full rounded" required>
        @error('owner')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
    <label class="block font-semibold">Repository</label>
    <input name="repo" value="{{ old('repo', $project->repo) }}" placeholder="RelationalDesign" class="border p-2 w-full rounded" required>
        @error('repo')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
    <label class="block font-semibold">GitHub App ID <span class="text-gray-400 text-xs">Numeric</span></label>
    <input name="github_app_id" value="{{ old('github_app_id', $project->github_app_id) }}" placeholder="1783044" class="border p-2 w-full rounded" required>
        @error('github_app_id')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
    <label class="block font-semibold">GitHub App Client ID</label>
    <input name="github_app_client_id" value="{{ old('github_app_client_id', $project->github_app_client_id) }}" placeholder="Iv23abcd..." class="border p-2 w-full rounded" required>
        @error('github_app_client_id')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <button class="bg-blue-600 text-white px-4 py-2 rounded">Update Project</button>
</form>
@endsection
