<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models (user listing).
     */
    public function viewAny(User $user): bool
    {
        // Only superadmins can view user list
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view themselves, superadmins can view anyone
        return $user->id === $model->id || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create models (import users).
     */
    public function create(User $user): bool
    {
        // Only superadmins can import/create users
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile, superadmins can update anyone
        return $user->id === $model->id || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can change another user's role.
     */
    public function changeRole(User $user, User $model): bool
    {
        // Only superadmins can change roles
        // And cannot change superadmin role (only superadmins themselves)
        if (!$user->isSuperAdmin()) {
            return false;
        }

        // Superadmins cannot demote other superadmins
        if ($model->isSuperAdmin() && $user->id !== $model->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only superadmins can delete users
        // Cannot delete themselves
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false; // Users are not soft-deleted
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false; // Users are not soft-deleted
    }

    /**
     * Determine whether the user can promote to admin.
     */
    public function promoteToAdmin(User $user, User $model): bool
    {
        // Only superadmins can promote
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can promote to superadmin.
     */
    public function promoteToSuperAdmin(User $user, User $model): bool
    {
        // Only superadmins can promote others to superadmin
        // And only if they're not demoting themselves
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can demote from admin.
     */
    public function demoteFromAdmin(User $user, User $model): bool
    {
        // Only superadmins can demote admins
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can import members.
     */
    public function importMembers(User $user): bool
    {
        // Only superadmins can import users
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can reset another user's password.
     */
    public function resetPassword(User $user, User $model): bool
    {
        // Only superadmins can reset passwords
        // Cannot reset their own
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }
}
