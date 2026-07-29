<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(): View
    {
        return view('guide.index', [
            'activeCollaborators' => Collaborator::where('status', 'active')->orderByDesc('id')->get(),
        ]);
    }
}
