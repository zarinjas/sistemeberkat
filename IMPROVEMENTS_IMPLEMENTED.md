# Authorization & Access Control Improvements

## Summary

All 4 major security and access control improvements have been successfully implemented in the e-BERKAT system. This document outlines the changes made.

---

## 1. Audit Dashboard - Role Change History ✅

### What Was Built
- **New Controller**: `AuditLogController` with complete audit trail management
- **New Route**: `/admin/audit-logs` (superadmin only)
- **Features**:
  - ✅ View all role changes with pagination
  - ✅ Filter by user, changed_by superadmin, old/new role, date range
  - ✅ Search by user name or email
  - ✅ Real-time statistics (total changes, today's changes, promotions, demotions)
  - ✅ Export to CSV
  - ✅ Export to PDF
  
### Usage

**Route structure:**
```
GET /admin/audit-logs - View audit logs
GET /admin/audit-logs/export-csv - Export as CSV
GET /admin/audit-logs/export-pdf - Export as PDF
GET /admin/audit-logs/statistics - Get statistics (JSON)
```

**Query filters supported:**
- `user_id` - Filter by affected user
- `changed_by_id` - Filter by superadmin who made change
- `old_role` - Filter by previous role
- `new_role` - Filter by new role
- `date_from` / `date_to` - Date range filtering
- `search` - Search by name or email

**Example:**
```
/admin/audit-logs?user_id=5&new_role=admin&date_from=2026-04-01
```

### Database
- **Table**: `role_change_audits` (created via migration)
- **Columns**: user_id, changed_by_user_id, old_role, new_role, reason, changed_at, timestamps
- **Indexed**: user_id, changed_by_user_id, changed_at (for performance)

### Dependencies
- `league/csv` (v9.28.0) - CSV export
- `barryvdh/laravel-dompdf` (v3.1.2) - PDF generation

---

## 2. Branch-Based Filtering for Admins ✅

### What Was Built
Branch-aware access control: Admins can only see applicants/applications from their assigned branch.

### Implementation Details

**Controllers Updated:**
1. **AdminApprovalController** - Approvals are filtered by admin's branch
2. **PaymentController** - Payment processing filtered by branch
3. **ReportingController** - Reports filtered by branch

**Query Logic:**
```php
->when(
    $request->user()->branch,
    fn ($query) => $query->whereHas('user', fn ($userQuery) => 
        $userQuery->where('branch', $request->user()->branch)
    )
)
```

### How It Works

- **Superadmins**: See all branches (unrestricted)
- **Admins with branch assignment**: See only their branch
- **Admins without branch assignment**: See all (backward compatible)

### Example Scenario
```
Admin "Zainab" assigned to branch "KL"
└─ Can ONLY see applications from applicants in KL branch
└─ Cannot see applications from applicants in other branches
└─ Cannot view payments from other branches
└─ Reports filtered to KL only
```

### Database Column
- Already exists: `users.branch` (string, nullable)

---

## 3. Fine-Tuned Permission System ✅

### What Was Built
Granular authorization policies with specific actions instead of broad role checks.

### Enhanced Policies

#### **AidApplicationPolicy** - New Methods
```php
approve()      // Can approve application (admin only, specific statuses)
reject()       // Can reject application (admin only)
preparePay()   // Can prepare/initiate payment (admin, specific status, no duplicate)
disbursePay()  // Can approve payment (maker-checker enforcement)
review()       // Can review applications
managePay()    // Can manage payments
```

#### **FormSchemaPolicy** - New Methods
```php
archive()      // Can archive/unpublish forms (superadmin, published only)
activate()     // Can activate forms (superadmin, published only)
duplicate()    // Can duplicate form (superadmin)
saveDraft()    // Can create/edit form drafts (superadmin)
publish()      // Can publish forms (superadmin, draft only)
```

#### **UserPolicy** - New Methods
```php
changeRole()           // Can change user role
promoteToAdmin()       // Can promote to admin
promoteToSuperAdmin()  // Can promote to superadmin (not self)
demoteFromAdmin()      // Can demote admin (not self)
importMembers()        // Can import members
resetPassword()        // Can reset password (not self)
```

### Usage in Controllers

**Before (Bad - Inline checks):**
```php
if ($user->isAdmin()) {
    // Large permission block
}
```

**After (Good - Policy-based):**
```php
$this->authorize('approve', $application);
$this->authorize('preparePay', $application);
if ($request->user()->can('archive', $form)) {
    // Show archive button
}
```

### Benefits
- ✅ Testable authorization logic
- ✅ Reusable across controllers
- ✅ Easier to audit and maintain
- ✅ Centralized permission rules
- ✅ Self-documenting code

---

## 4. Export Audit Logs to CSV/PDF ✅

### What Was Built
Multi-format export functionality for audit logs with professional formatting.

### CSV Export
- **Endpoint**: `GET /admin/audit-logs/export-csv?[filters]`
- **Format**: Standard CSV with headers
- **Columns**: Date Changed, User Name, User Email, Old Role, New Role, Changed By, Email, Reason
- **Filename**: `role-change-audit-YYYY-MM-DD-HIS.csv`
- **Filter Support**: All filters apply to export (date range, user, role, etc.)

**Example:**
```
Date Changed,User Name,User Email,Old Role,New Role,Changed By (Name),Changed By (Email),Reason
2026-04-12 10:30:45,Ahmad Hassan,ahmad@berkat.com,applicant,admin,Sarah Admin,admin@berkat.com,-
```

### PDF Export
- **Endpoint**: `GET /admin/audit-logs/export-pdf?[filters]`
- **Format**: Professional PDF with:
  - Header with title and generation date
  - Summary statistics (total records, report period)
  - Styled table with alternating row colors
  - Role badges (color-coded by role)
  - Footer with generation info
- **Paper**: A4 Landscape
- **Filename**: `role-change-audit-YYYY-MM-DD-HIS.pdf`
- **Filter Support**: All filters apply to export

### Implementation

**Libraries Used:**
- `league/csv` - CSV generation
- `barryvdh/laravel-dompdf` - PDF generation via DOMPDF

**PDF Template**: `resources/views/exports/audit-pdf.blade.php`
```
Header (Title, date, system name)
    ↓
Summary (Total records, status)
    ↓
Data Table (Styled with role badges)
    ↓
Footer (Generation note, pagination)
```

**Styling Features:**
- Color-coded role badges
  - Applicant: Blue
  - Admin: Orange  
  - Superadmin: Red
- Professional table with borders
- Responsive to landscape orientation
- Supports large datasets with pagination

### Query Filters

Both CSV and PDF export support filtering:

```bash
# Example: Export admin promotions from last month
/admin/audit-logs/export-csv?new_role=admin&date_from=2026-03-12&date_to=2026-04-12

# Example: Export changes made by specific superadmin
/admin/audit-logs/export-pdf?changed_by_id=1

# Example: Export all changes for one user
/admin/audit-logs/export-csv?user_id=42
```

---

## Security Improvements Summary

| Feature | Before | After |
|---------|--------|-------|
| **Audit Trail** | Manual tracking only | Automatic logging + dashboard |
| **Access Control** | Role-based only | Granular action-based + role-based |
| **Admin Scope** | See all branches | See only assigned branch |
| **Exports** | Manual monitoring | CSV/PDF with filtering |
| **Policy Framework** | Inline middleware checks | Reusable Laravel Policies |
| **Compliance** | Limited visibility | Full audit trail searchable |

---

## API Reference

### Audit Log Endpoints

#### GET /admin/audit-logs
View paginated role change history with filters.

**Query Parameters:**
```
user_id=5              // Filter by affected user
changed_by_id=1        // Filter by superadmin
old_role=applicant     // Previous role
new_role=admin         // New role
date_from=2026-04-01   // Start date
date_to=2026-04-12     // End date
search=ahmad           // Search name/email
page=1                 // Pagination
```

**Response:**
```javascript
{
  audits: [
    {
      id: 1,
      user: { id: 5, name: "Ahmad", email: "ahmad@..." },
      old_role: "applicant",
      new_role: "admin",
      changedBy: { id: 1, name: "Admin" },
      changed_at: "2026-04-12T10:30:45Z"
    }
  ],
  filters: { ... },
  stats: { total_changes: 42, ... }
}
```

#### GET /admin/audit-logs/export-csv
Export filtered audit logs as CSV file.

**Query Parameters:** Same as above
**Response:** CSV file download

#### GET /admin/audit-logs/export-pdf  
Export filtered audit logs as PDF report.

**Query Parameters:** Same as above
**Response:** PDF file download

#### GET /admin/audit-logs/statistics
Get audit statistics as JSON (last 30 days).

**Response:**
```javascript
{
  total_changes: 5,
  by_role: {
    promoted_to_admin: 2,
    demoted_from_admin: 1
  },
  by_superadmin: {
    "Sarah Admin": 3,
    "Omar Root": 2
  }
}
```

---

## Testing the Implementation

### 1. Test Audit Logging
```php
// In your test:
$superadmin = User::where('role', 'superadmin')->first();
$user = User::factory()->create(['role' => 'applicant']);

$response = $this->actingAs($superadmin)
    ->patch('/admin/system/users/'.$user->id.'/role', ['role' => 'admin']);

// Verify audit was created
$this->assertDatabaseHas('role_change_audits', [
    'user_id' => $user->id,
    'old_role' => 'applicant',
    'new_role' => 'admin',
]);
```

### 2. Test Branch Filtering
```php
$admin = User::factory()->create(['role' => 'admin', 'branch' => 'KL']);
$applicant_kl = User::factory()->create(['branch' => 'KL']);
$applicant_other = User::factory()->create(['branch' => 'JB']);

AidApplication::factory()->create(['user_id' => $applicant_kl->id]);
AidApplication::factory()->create(['user_id' => $applicant_other->id]);

$response = $this->actingAs($admin)->get('/admin/approvals');
// Should only show 1 application (from KL branch)
```

### 3. Test Policy Authorization
```php
$applicant = User::factory()->create(['role' => 'applicant']);
$admin = User::factory()->create(['role' => 'admin']);
$app = AidApplication::factory()->create(['user_id' => $applicant->id]);

// Applicant cannot approve
$this->assertFalse($applicant->can('approve', $app));

// Admin can approve
$this->assertTrue($admin->can('approve', $app));
```

---

## Checklist for Deployment

- [ ] Run migrations: `php artisan migrate`
- [ ] Test audit dashboard: Visit `/admin/audit-logs`
- [ ] Test CSV export: Download from audit dashboard
- [ ] Test PDF export: Download from audit dashboard
- [ ] Verify branch filtering working for admins
- [ ] Test policy authorization in controllers
- [ ] Review AUTHORIZATION.md documentation
- [ ] Train superadmins on new audit dashboard
- [ ] Set up audit log retention policy (optional)

---

## Future Enhancements

1. **Audit Log Retention**: Auto-delete logs older than N days
2. **Real-time Alerts**: Notify on suspicious role changes
3. **Audit Log Search**: Full-text search across changes
4. **Weekly Reports**: Automated email reports to superadmins
5. **2FA for Superadmins**: Protect critical role changes
6. **Advanced Analytics**: Charts/graphs of role change trends

---

## Troubleshooting

### PDF Export Shows Blank Page
- Check that `config/dompdf.php` exists
- Verify fonts are readable in `storage/fonts/`
- Test HTML rendering in `/resources/views/exports/audit-pdf.blade.php`

### CSV Export Missing Data
- Verify filters are correctly applied
- Check that relationships are loaded: `with(['user', 'changedBy'])`
- Ensure no null values breaking CSV format

### Branch Filtering Not Working
- Verify admin has `branch` value set in users table
- Check middleware order (auth before branch check)
- Superadmins should bypass branch filtering

---

## References

- [Authorization.md](AUTHORIZATION.md) - Comprehensive guide
- [Laravel Policies](https://laravel.com/docs/authorization)
- [League CSV](https://csv.thephpleague.com/)
- [DOMPDF](https://dompdf.github.io/)
