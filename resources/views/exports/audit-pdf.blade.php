<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Role Change Audit Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        
        .container {
            padding: 20px;
        }
        
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 9px;
        }
        
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #ecf0f1;
            border-radius: 3px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-item label {
            display: block;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        
        .summary-item value {
            display: block;
            font-size: 12px;
            font-weight: bold;
            color: #27ae60;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table thead {
            background-color: #34495e;
            color: white;
        }
        
        table thead th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #2c3e50;
        }
        
        table tbody td {
            padding: 6px 8px;
            border: 1px solid #bdc3c7;
            font-size: 8px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f8f9f9;
        }
        
        table tbody tr:hover {
            background-color: #ecf0f1;
        }
        
        .role-badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
            min-width: 50px;
        }
        
        .role-applicant {
            background-color: #3498db;
            color: white;
        }
        
        .role-admin {
            background-color: #e67e22;
            color: white;
        }
        
        .role-superadmin {
            background-color: #c0392b;
            color: white;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #bdc3c7;
            text-align: right;
            color: #7f8c8d;
            font-size: 8px;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Role Change Audit Report</h1>
            <p>e-BERKAT Smart Concierge System</p>
            <p>Generated on {{ $generated_at }}</p>
        </div>
        
        <div class="summary">
            <div class="summary-item">
                <label>Total Records</label>
                <value>{{ $total_records }}</value>
            </div>
            <div class="summary-item">
                <label>Report Period</label>
                <value>All Time</value>
            </div>
            <div class="summary-item">
                <label>Status</label>
                <value>Complete</value>
            </div>
        </div>
        
        @if (count($audits) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date Changed</th>
                        <th>User Name</th>
                        <th>User Email</th>
                        <th>Old Role</th>
                        <th>New Role</th>
                        <th>Changed By</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($audits as $audit)
                        <tr>
                            <td>{{ $audit['date_changed'] }}</td>
                            <td>{{ $audit['user_name'] }}</td>
                            <td style="font-size: 7px;">{{ $audit['user_email'] }}</td>
                            <td>
                                <span class="role-badge role-{{ strtolower($audit['old_role']) }}">
                                    {{ ucfirst($audit['old_role']) }}
                                </span>
                            </td>
                            <td>
                                <span class="role-badge role-{{ strtolower($audit['new_role']) }}">
                                    {{ ucfirst($audit['new_role']) }}
                                </span>
                            </td>
                            <td>{{ $audit['changed_by_name'] }}</td>
                            <td style="font-size: 7px;">{{ $audit['changed_by_email'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>No role change records found matching the criteria.</p>
            </div>
        @endif
        
        <div class="footer">
            <p>This is an automatically generated report. For questions, contact your system administrator.</p>
            <p>Page {{ $page ?? 1 }} of {{ $pages ?? 1 }}</p>
        </div>
    </div>
</body>
</html>