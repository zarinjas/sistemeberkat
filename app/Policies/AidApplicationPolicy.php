<?php

namespace App\Policies;

use App\Models\AidApplication;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AidApplicationPolicy
{
    /**
     * Determine whether the user can view any models.
     * Applicants can view own applications, admins can view all.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AidApplication $application): bool
    {
        // Applicants can only view their own applications
        if ($user->isApplicant()) {
            return $application->user_id === $user->id;
        }

        // Admins and superadmins can view all applications
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only applicants can create applications
        return $user->isApplicant();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AidApplication $application): bool
    {
        // Applicants can only update their own draft applications
        if ($user->isApplicant()) {
            return $application->user_id === $user->id 
                && $application->status === AidApplication::STATUS_DRAFT;
        }

        // Admins can update status of non-draft applications
        if ($user->isAdmin()) {
            return $application->status !== AidApplication::STATUS_DRAFT;
        }

        // Superadmins have full access
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AidApplication $application): bool
    {
        // Applicants can only delete their own draft applications
        if ($user->isApplicant()) {
            return $application->user_id === $user->id 
                && $application->status === AidApplication::STATUS_DRAFT;
        }

        // Superadmins can delete
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AidApplication $application): bool
    {
        return false; // Applications are not soft-deleted
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AidApplication $application): bool
    {
        return false; // Applications are not soft-deleted
    }

    /**
     * Determine whether the user can review/approve the application.
     */
    public function review(User $user, AidApplication $application): bool
    {
        // Only admins can review applications
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can manage payments.
     */
    public function managePay(User $user, AidApplication $application): bool
    {
        // Only admins can manage payments
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can approve the application.
     */
    public function approve(User $user, AidApplication $application): bool
    {
        // Only admins can approve (not in draft/submitted status)
        return ($user->isAdmin() || $user->isSuperAdmin())
            && in_array($application->status, [
                AidApplication::STATUS_UNDER_REVIEW,
                AidApplication::STATUS_KUIRI,
            ], true);
    }

    /**
     * Determine whether the user can reject the application.
     */
    public function reject(User $user, AidApplication $application): bool
    {
        // Only admins can reject (not already decided)
        return ($user->isAdmin() || $user->isSuperAdmin())
            && !in_array($application->status, [
                AidApplication::STATUS_APPROVED,
                AidApplication::STATUS_DISBURSED,
                AidApplication::STATUS_REJECTED,
            ], true);
    }

    /**
     * Determine whether the user can prepare a payment.
     */
    public function preparePay(User $user, AidApplication $application): bool
    {
        // Only admins can prepare payments (application must be approved)
        return ($user->isAdmin() || $user->isSuperAdmin())
            && $application->status === AidApplication::STATUS_APPROVED
            && !$application->payment_prepared_at;
    }

    /**
     * Determine whether the user can disburse/approve a payment.
     */
    public function disbursePay(User $user, AidApplication $application): bool
    {
        // Only admins can disburse
        // Must be prepared by someone else (separation of duties)
        return ($user->isAdmin() || $user->isSuperAdmin())
            && $application->payment_prepared_at
            && $application->payment_prepared_by_user_id !== $user->id
            && in_array($application->status, [
                AidApplication::STATUS_APPROVED,
                AidApplication::STATUS_DISBURSED,
            ], true);
    }
}
