<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        $query = User::query();

        // Filter by search (name atau email)
        if ($request->has('q') && $request->get('q')) {
            $q = $request->get('q');
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('name', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->get('role')) {
            $query->where('role', $request->get('role'));
        }

        $users = $query->orderByDesc('created_at')->paginate(15);

        return view('admin.users.index', [
            'users' => $users,
            'q' => $request->get('q'),
            'selectedRole' => $request->get('role'),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Akun berhasil dibuat.');
    }

    /**
     * Update user role.
     * Aturan: admin tidak boleh mengubah role dirinya sendiri,
     * dan tidak boleh menurunkan admin terakhir.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        // Admin tidak boleh mengubah role dirinya sendiri
        if ($request->user()->id === $user->id) {
            return redirect()->back()
                ->with('error', 'Anda tidak bisa mengubah role diri sendiri.');
        }

        $validated = $request->validated();

        // Cek: jika menurunkan user dari admin, pastikan masih ada admin lain
        if ($user->isAdmin() && $validated['role'] !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return redirect()->back()
                    ->with('error', 'Harus tetap ada minimal 1 admin.');
            }
        }

        $user->update(['role' => $validated['role']]);

        return redirect()->back()
            ->with('success', 'Role berhasil diubah.');
    }
}
