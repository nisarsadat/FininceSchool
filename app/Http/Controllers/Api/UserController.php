<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with('role:id,name,slug')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'code' => $user->code,
                'is_active' => $user->is_active,
                'role' => $user->role,
            ]);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $role = Role::query()->findOrFail($data['role_id']);

        $user = User::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'email' => $this->deskEmail($data['code']),
            'password' => $data['password'],
            'role_id' => $role->id,
            'is_active' => $data['is_active'],
        ]);

        return response()->json($user->accessPayload(), 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user);
        $this->guardLastAdmin($user, (int) $data['role_id'], (bool) $data['is_active']);

        $user->name = $data['name'];
        $user->code = $data['code'];
        $user->role_id = $data['role_id'];
        $user->is_active = $data['is_active'];

        if (str_ends_with((string) $user->email, '@desk.local')) {
            $user->email = $this->deskEmail($data['code']);
        }

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return response()->json($user->accessPayload());
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot remove your own account.',
            ]);
        }

        $this->guardLastAdmin($user, null, false);
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User removed.']);
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('users', 'code')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = (bool) $data['is_active'];

        if ($request->user()->id === $user?->id && ! $data['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => 'You cannot turn off your own account.',
            ]);
        }

        return $data;
    }

    private function guardLastAdmin(User $user, ?int $nextRoleId, bool $nextActive): void
    {
        $adminId = Role::query()->where('slug', 'admin')->value('id');

        if (! $adminId || (int) $user->role_id !== (int) $adminId) {
            return;
        }

        $staysAdmin = $nextActive && $nextRoleId === (int) $adminId;

        if ($staysAdmin) {
            return;
        }

        $others = User::query()
            ->where('role_id', $adminId)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->count();

        if ($others === 0) {
            throw ValidationException::withMessages([
                'role_id' => 'The last active administrator must stay.',
            ]);
        }
    }

    private function deskEmail(string $code): string
    {
        return strtolower($code).'@desk.local';
    }
}
