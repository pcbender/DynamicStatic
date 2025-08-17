<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $project = $user->project;
        if (!$project) {
            return redirect()->route('project.setup');
        }
        return view('dashboard.index', [
            'project' => $project,
            'user' => $user,
        ]);
    }
}
