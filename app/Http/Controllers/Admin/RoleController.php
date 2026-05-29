<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLog;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.form', [
            'role' => new Role(['permissions' => []]),
            'groups' => Permissions::groups(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $role = Role::create([
            'key' => $this->uniqueKey($data['name']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'permissions' => $data['permissions'],
            'is_system' => false,
        ]);

        AuditLog::record('role.create', $role, $role->name, ['permissions' => $role->permissions]);

        return redirect()->route('admin.roles.index')
            ->with('status', __('app.roles.created', ['name' => $role->name]));
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', [
            'role' => $role,
            'groups' => Permissions::groups(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validateData($request);

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ];

        // Super Admin always keeps every permission — its set is locked.
        if (! $role->isSuper()) {
            $payload['permissions'] = $data['permissions'];
        }

        $role->update($payload);

        AuditLog::record('role.update', $role, $role->name, ['permissions' => $role->permissions]);

        return redirect()->route('admin.roles.index')
            ->with('status', __('app.roles.updated', ['name' => $role->name]));
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', __('app.roles.cannot_delete_system'));
        }

        $name = $role->name;

        // Holders fall back to regular users (role_id is also nulled by the FK).
        User::where('role_id', $role->id)->update(['role_id' => null, 'is_admin' => false]);
        $role->delete();

        AuditLog::record('role.delete', null, $name);

        return redirect()->route('admin.roles.index')
            ->with('status', __('app.roles.deleted', ['name' => $name]));
    }

    /** @return array{name:string, description:?string, permissions:array<int,string>} */
    private function validateData(Request $request): array
    {
        $allowed = Permissions::keys();

        $data = $request->validate([
            'name' => 'required|string|max:80',
            'description' => 'nullable|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'string|in:' . implode(',', $allowed),
        ]);

        // Keep only known keys, in catalog order.
        $data['permissions'] = array_values(array_intersect($allowed, $data['permissions'] ?? []));

        return $data;
    }

    private function uniqueKey(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $key = $base;
        $i = 1;
        while (Role::where('key', $key)->exists()) {
            $key = $base . '-' . (++$i);
        }

        return $key;
    }
}
