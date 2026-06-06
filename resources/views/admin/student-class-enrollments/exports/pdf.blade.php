<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 6px;
        }

        table th {
            background: #eee;
        }
    </style>
</head>

<body>

    <h2>{{ $studentClass->class_name }}</h2>

    <p>
        <strong>Grade:</strong> {{ $studentClass->grade?->grade_name ?? '-' }} <br>
        <strong>Subject:</strong> {{ $studentClass->subject?->subject_name ?? '-' }} <br>
        <strong>Teacher:</strong> {{ $studentClass->teacher?->initials ?? $studentClass->teacher?->full_name ?? '-' }}
        <br>
        <strong>Category:</strong> {{ $classCategory->category_name }}
    </p>

    <table>

        <thead>
            <tr>
                <th>#</th>
                <th>Student ID / QR</th>
                <th>Initial Name</th>
                <th>Full Name</th>
                <th>Mobile</th>
                <th>Final Fee</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

            @foreach($enrollments as $key => $enrollment)

                @php
                    $student = $enrollment->student;

                    $studentCode = '-';

                    if (
                        $student &&
                        $student->permanent_qr_active &&
                        !empty($student->custom_id) &&
                        $student->custom_id != '0'
                    ) {
                        $studentCode = $student->custom_id;
                    } else {
                        $studentCode = $student->temporary_qr_code ?? '-';
                    }
                @endphp

                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $studentCode }}</td>
                    <td>{{ $student->initial_name ?? '-' }}</td>
                    <td>{{ $student->full_name ?? '-' }}</td>
                    <td>{{ $student->mobile ?? '-' }}</td>
                    <td>{{ number_format($enrollment->final_fee, 2) }}</td>
                    <td>
                        {{ $enrollment->is_active ? 'Active' : 'Inactive' }}
                    </td>
                </tr>

            @endforeach

        </tbody>

    </table>

</body>

</html>