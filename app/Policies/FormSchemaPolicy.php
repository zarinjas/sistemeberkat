<?php

namespace App\Policies;

use App\Models\FormSchema;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FormSchemaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Applicants can view published forms, superadmins can view all
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FormSchema $formSchema): bool
    {
        // Applicants can only view published forms
        if ($user->isApplicant()) {
            return $formSchema->lifecycle_status === FormSchema::STATUS_PUBLISHED;
        }

        // Superadmins can view all forms
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only superadmins can create forms
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FormSchema $formSchema): bool
    {
        // Only superadmins can update forms
        // Cannot update published forms (must archive and create new)
        return $user->isSuperAdmin() 
            && $formSchema->lifecycle_status !== FormSchema::STATUS_PUBLISHED;
    }

    /**
     * Determine whether the user can publish the model.
     */
    public function publish(User $user, FormSchema $formSchema): bool
    {
        // Only superadmins can publish forms
        return $user->isSuperAdmin()
            && $formSchema->lifecycle_status !== FormSchema::STATUS_PUBLISHED;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FormSchema $formSchema): bool
    {
        // Only superadmins can delete
        // Cannot delete published forms
        return $user->isSuperAdmin()
            && $formSchema->lifecycle_status !== FormSchema::STATUS_PUBLISHED;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FormSchema $formSchema): bool
    {
        return false; // Forms are not soft-deleted
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FormSchema $formSchema): bool
    {
        return false; // Forms are not soft-deleted
    }

    /**
     * Determine whether the user can archive/unpublish the model.
     */
    public function archive(User $user, FormSchema $formSchema): bool
    {
        // Only superadmins can archive forms
        return $user->isSuperAdmin()
            && $formSchema->lifecycle_status === FormSchema::STATUS_PUBLISHED;
    }

    /**
     * Determine whether the user can activate (set active) the model.
     */
    public function activate(User $user, FormSchema $formSchema): bool
    {
        // Only superadmins can activate published forms
        return $user->isSuperAdmin()
            && $formSchema->lifecycle_status === FormSchema::STATUS_PUBLISHED;
    }

    /**
     * Determine whether the user can duplicate the model.
     */
    public function duplicate(User $user, FormSchema $formSchema): bool
    {
        // Only superadmins can duplicate forms
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can save as draft.
     */
    public function saveDraft(User $user): bool
    {
        // Only superadmins can create/edit form drafts
        return $user->isSuperAdmin();
    }
}
