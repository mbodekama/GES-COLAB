<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles       = Role::with(['permissions', 'users'])->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $users       = User::orderBy('name')->get();

        return view('roles.index', compact('roles', 'permissions', 'users'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get()
            ->groupBy(fn($p) => explode(' ', $p->name)[1] ?? 'autre');

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:60|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')
                         ->with('success', "Rôle « {$role->name} » créé.");
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get()
            ->groupBy(fn($p) => explode(' ', $p->name)[1] ?? 'autre');

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:60|unique:roles,name,'.$role->id,
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')
                         ->with('success', "Rôle « {$role->name} » mis à jour.");
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['superadmin', 'admin', 'user'])) {
            return back()->with('error', 'Ce rôle système ne peut pas être supprimé.');
        }

        $role->delete();

        return redirect()->route('roles.index')
                         ->with('success', 'Rôle supprimé.');
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return back()->with('success', 'Permissions mises à jour.');
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$request->role]);

        return back()->with('success', "Rôle « {$request->role} » assigné à {$user->name}.");
    }
}
