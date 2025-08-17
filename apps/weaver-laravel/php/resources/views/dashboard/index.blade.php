@extends('layouts.app')

@section('content')
<h1 class="text-xl font-bold mb-4">Dashboard</h1>
<div class="mb-6">Welcome, {{ $user->name ?? $user->email }}!</div>
@if(!$project)
    <div class="border-2 border-dashed rounded p-6 bg-white text-center">
        <p class="mb-4 text-gray-700">You haven't created a project yet.</p>
        <a href="{{ route('project.setup') }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded">Finish Setup</a>
    </div>
@else
    <div class="border rounded p-4 bg-white space-y-2">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold">Project Summary</h2>
                <a href="{{ route('project.edit') }}" class="text-sm text-blue-600 underline">Edit</a>
            </div>
            <div><strong>Name:</strong> {{ $project->name }}</div>
            <div><strong>Owner/Repo:</strong> {{ $project->owner }}/{{ $project->repo }}</div>
            <div><strong>GitHub App ID:</strong> {{ $project->github_app_id }}</div>
            <div><strong>Client ID:</strong> {{ $project->github_app_client_id }}</div>
    </div>
@endif
@endsection
