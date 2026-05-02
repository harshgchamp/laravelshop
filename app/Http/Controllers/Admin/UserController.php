<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(UserIndexRequest $request): Response
    {
        $validated = $request->validated();

        $field = $validated['field'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';
        $perPage = (int) ($validated['perPage'] ?? 10);

        return Inertia::render('Admin/Users/Index', [
            'users' => UserResource::collection($this->userService->list($field, $order, $perPage)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->userService->store($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => (new UserResource($user))->resolve(),
            'roles' => Role::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->destroy($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
