<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Models\Collaborator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CollaboratorController extends Controller
{
    public function index(): View
    {
        return view('collaborators.index', [
            'collaborators' => Collaborator::query()->orderByDesc('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('collaborators.create');
    }

    public function store(StoreCollaboratorRequest $request): RedirectResponse
    {
        Collaborator::create($request->validated());

        return redirect()->route('collaborators.index')->with('success', 'Thêm collaborator thành công!');
    }

    public function edit(Collaborator $collaborator): View
    {
        return view('collaborators.edit', ['collaborator' => $collaborator]);
    }

    public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator): RedirectResponse
    {
        $collaborator->update($request->validated());

        return redirect()->route('collaborators.index')->with('success', 'Cập nhật collaborator thành công!');
    }

    public function destroy(Collaborator $collaborator): RedirectResponse
    {
        $collaborator->delete();

        return redirect()->route('collaborators.index')->with('success', 'Xóa collaborator thành công!');
    }
}
