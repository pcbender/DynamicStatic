<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectSetupController extends Controller
{
    public function setupForm()
    {
        $project = Auth::user()->project;
        if ($project) {
            return redirect()->route('project.edit');
        }
        return view('project.setup');
    }

    public function setupStore(Request $request)
    {
        $user = Auth::user();
        if ($user->project) {
            return redirect()->route('dashboard');
        }
        $data = $this->validateData($request);
        $data['user_id'] = $user->id;
    Project::create($data);
    return redirect()->route('dashboard')->with('success', 'Project created');
    }

    public function editForm()
    {
        $project = Auth::user()->project;
        if (!$project) {
            return redirect()->route('project.setup');
        }
        return view('project.edit', ['project' => $project]);
    }

    public function update(Request $request)
    {
        $project = Auth::user()->project;
        if (!$project) {
            return redirect()->route('project.setup');
        }
        $data = $this->validateData($request, $project->id);
    $project->update($data);
    return redirect()->route('dashboard')->with('success', 'Project updated');
    }

    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:150'],
            'owner' => ['required','string','max:100','regex:/^[A-Za-z0-9_.-]+$/'],
            'repo' => ['required','string','max:100','regex:/^[A-Za-z0-9_.-]+$/'],
            'github_app_id' => ['required','string','max:50','regex:/^[0-9]+$/'],
            'github_app_client_id' => ['required','string','max:100'],
        ], [
            'owner.regex' => 'Owner may contain letters, numbers, dash, underscore, dot only.',
            'repo.regex' => 'Repository may contain letters, numbers, dash, underscore, dot only.',
            'github_app_id.regex' => 'GitHub App ID must be digits only.'
        ]);
    }
}
