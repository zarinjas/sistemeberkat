# Authorization & Access Control

This document explains the authorization system used in e-BERKAT.

## Overview

e-BERKAT uses **Laravel Policies** for model-level authorization combined with **route middleware** for access control.

### Authorization Hierarchy

```
Routes (middleware) → Controller (can use policies) → Policies (model rules)
```

## User Roles

Three roles exist in the system:

| Role | Responsibilities |
|------|------------------|
| **applicant** | Submit aid applications, manage own documents |
| **admin** | Review/approve applications, manage payments |
| **superadmin** | Manage forms, settings, users, marketing content |

## Policies

### AidApplicationPolicy

Controls access to aid applications.

```php
// In controller:
$this->authorize('view', $application);      // Can user view this application?
$this->authorize('update', $application);    // Can user update this application?
$this->authorize('delete', $application);    // Can user delete this application?
$this->authorize('review', $application);    // Can user review/change status?
$this->authorize('managePay', $application); // Can user manage payments?
```

**Rules:**
- **Applicants**: Create apps, view/update/delete own drafts only
- **Admins**: Review applications, update status, manage payments
- **Superadmins**: Full access

### UserPolicy

Controls access to user management.

```php
// In controller:
$this->authorize('viewAny', User::class);       // Can view user list?
$this->authorize('view', $user);                // Can view this user?
$this->authorize('changeRole', $user);          // Can change role?
$this->authorize('create', User::class);        // Can create/import users?
$this->authorize('delete', $user);              // Can delete this user?
```

**Rules:**
- **Applicants**: Can only view themselves
- **Admins**: Restricted access
- **Superadmins**: Full user management access (cannot demote other superadmins)

### FormSchemaPolicy

Controls access to form management.

```php
// In controller:
$this->authorize('view', $form);      // Can view this form?
$this->authorize('create', FormSchema::class); // Can create form?
$this->authorize('update', $form);    // Can edit this form?
$this->authorize('publish', $form);   // Can publish this form?
$this->authorize('delete', $form);    // Can delete this form?
```

**Rules:**
- **Applicants**: Can view published forms only
- **Superadmins**: Can create, edit (drafts only), publish, delete forms

## Using Policies in Controllers

### Check Authorization

```php
// In controller action:
public function show(Request $request, AidApplication $application)
{
    // Check permission - throws 403 if unauthorized
    $this->authorize('view', $application);
    
    // If we reach here, user is authorized
    return view('application.show', ['application' => $application]);
}
```

### Conditional Authorization

```php
// Check without throwing exception:
if ($request->user()->can('edit', $application)) {
    // Show edit button
}

// Or inverse:
if ($request->user()->cannot('edit', $application)) {
    // Show read-only version
}
```

### Middleware Authorization

Routes can use middleware for broad checks:

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    // These routes require admin role
});

Route::middleware(['auth', 'superadmin'])->group(function () {
    // These routes require superadmin role
});
```

## Audit Logging

### Role Change Audits

Every time a superadmin changes a user's role, an entry is created in `role_change_audits`:

```php
// Automatically logged when via SystemManagementController::updateRole()
RoleChangeAudit::create([
    'user_id' => $user->id,
    'changed_by_user_id' => $superadmin->id,
    'old_role' => 'applicant',
    'new_role' => 'admin',
    'changed_at' => now(),
]);
```

Query audit history:

```php
// Get role changes for a user
$user->roleChangeAudits;

// Get changes made by a superadmin
$superadmin->roleChangesInitiated;

// Get recent changes
RoleChangeAudit::latest('changed_at')->limit(20)->get();
```

## Migration Guide

### For Controllers Using Direct Checks

**Before (using inline checks):**
```php
if ($application->user_id !== $request->user()->id) {
    abort(403);
}
```

**After (using policy):**
```php
$this->authorize('view', $application);
```

### For Views Using Role Checks

**Before:**
```php
@if ($user->isSuperAdmin())
    <a href="/admin/form-builder">Manage Forms</a>
@endif
```

**After:**
```php
@can('create', formSchema)
    <a href="/admin/form-builder">Manage Forms</a>
@endcan
```

## Security Best Practices

1. **Always authorize before accessing resources** - Use `$this->authorize()` at the start of controller actions
2. **Use policies instead of inline role checks** - More maintainable and testable
3. **Prevent privilege escalation** - Superadmins cannot demote other superadmins (enforced in UserPolicy)
4. **Audit critical operations** - Role changes are automatically logged
5. **Test authorization** - Write tests for policy rules

## Testing Authorization

```php
// Test that applicant cannot view other's application
$this->actingAs($applicant)
    ->get("/applications/{$otherApplicant->id}")
    ->assertStatus(403);

// Test that admin can view any application
$this->actingAs($admin)
    ->get("/applications/{$applicant->id}")
    ->assertStatus(200);
```

## Troubleshooting

### "Access Denied" errors

1. Check if user has correct role in `users.role` column
2. Verify policy method exists for the action
3. Check route middleware - does it restrict access?
4. Review AppServiceProvider - are policies registered?

### Policies not being checked

- Ensure `$this->authorize()` is called in controller
- Check that User model uses Laravel's base User class
- Verify policy class names match conventions: `{Model}Policy`

## See Also

- [Laravel Authorization Docs](https://laravel.com/docs/authorization)
- [Database Schema](database/migrations/)
- [Role Management](app/Http/Controllers/SystemManagementController.php)
