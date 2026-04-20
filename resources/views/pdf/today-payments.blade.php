<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Today's Payments Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            margin: 0;
            color: #2c3e50;
        }
        
        .header p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .summary-item {
            text-align: center;
            flex: 1;
        }
        
        .summary-item .label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .summary-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        th {
            background-color: #34495e;
            color: white;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #7f8c8d;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 11px;
            border-radius: 3px;
        }
        
        .badge-free {
            background-color: #27ae60;
            color: white;
        }
        
        .badge-custom {
            background-color: #3498db;
            color: white;
        }
        
        .badge-discount {
            background-color: #e67e22;
            color: white;
        }
        
        .badge-normal {
            background-color: #95a5a6;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Today's Payments Report</h1>
        <p>Date: {{ $formattedDate }}</p>
        <p>Generated: {{ now()->format('F j, Y h:i A') }}</p>
    </div>
    
    <div class="summary">
        <div class="summary-item">
            <div class="label">Total Payments</div>
            <div class="value">{{ $totalPayments }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Amount</div>
            <div class="value">${{ number_format($totalAmount, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Average Payment</div>
            <div class="value">${{ $totalPayments > 0 ? number_format($totalAmount / $totalPayments, 2) : '0.00' }}</div>
        </div>
    </div>
    
    @if($result->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Payment ID</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Grade</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Payment For</th>
                    <th>Fee Type</th>
                    <th class="amount">Amount ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($result as $index => $payment)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $payment->payment_id }}</td>
                        <td>{{ $payment->student_name }}</td>
                        <td>{{ $payment->class_name }}</td>
                        <td>{{ $payment->grade_name }}</td>
                        <td>{{ $payment->subject_name }}</td>
                        <td>{{ $payment->teacher_name }}</td>
                        <td>{{ $payment->payment_for_month ?? 'N/A' }}</td>
                        <td>
                            @php
                                $badgeClass = 'badge-normal';
                                if(str_contains($payment->fee_type, 'Free')) $badgeClass = 'badge-free';
                                elseif(str_contains($payment->fee_type, 'Custom')) $badgeClass = 'badge-custom';
                                elseif(str_contains($payment->fee_type, 'Discount')) $badgeClass = 'badge-discount';
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $payment->fee_type }}</span>
                        </td>
                        <td class="amount">${{ number_format($payment->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="9" style="text-align: right;">Total:</td>
                    <td class="amount">${{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <p style="text-align: center; padding: 50px;">No payments found for today.</p>
    @endif
    
    <div class="footer">
        <p>This is a system-generated report. For any queries, please contact the administration.</p>
        <p>&copy; {{ date('Y') }} Shakya Higher Education Institute. All rights reserved.</p>
    </div>
</body>
</html>