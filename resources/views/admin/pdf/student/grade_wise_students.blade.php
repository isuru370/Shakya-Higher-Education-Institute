<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>Grade Wise Student Report</title>

    <style>
        @page {
            margin: 20px;
        }

        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1e293b;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2563eb;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 9px;
            color: #64748b;
        }

        .company {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .grade-section {
            margin-bottom: 20px;
        }

        .grade-header {
            background: #1e293b;
            color: white;
            padding: 8px 10px;
        }

        .grade-title {
            font-size: 13px;
            font-weight: bold;
        }

        .grade-stats {
            margin-top: 3px;
            font-size: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #334155;
            color: white;
            padding: 6px;
            font-size: 8px;
            border: 1px solid #475569;
        }

        td {
            padding: 6px;
            border: 1px solid #cbd5e1;
            font-size: 8px;
        }

        .center {
            text-align: center;
        }

        .active {
            color: #15803d;
            font-weight: bold;
        }

        .inactive {
            color: #dc2626;
            font-weight: bold;
        }

        .qr {
            font-family: DejaVu Sans Mono, monospace;
        }

        .separator {
            margin-bottom: 15px;
            border-bottom: 1px dashed #94a3b8;
        }

        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            text-align: center;
            font-size: 7px;
            color: #64748b;
        }
    </style>
</head>

<body>

    {{-- Report Header --}}
    <div class="header">
        <h1>Grade Wise Student Report</h1>

        <div class="subtitle">
            Complete Student List Grouped by Grade
        </div>
    </div>

    {{-- Company --}}
    <div class="company">
        {{ config('app.name', 'EDU NEXORA') }}
    </div>


    {{-- Grades --}}
    @forelse($grades as $gradeId => $students)

        @php
            $firstStudent = $students->first();

            $gradeName = optional($firstStudent->grade)->grade_name
                ?? 'Grade ' . $gradeId;

            $total = $students->count();

            $activeCount = $students
                ->where('is_active', true)
                ->count();

            $inactiveCount = $students
                ->where('is_active', false)
                ->count();
        @endphp


        <div class="grade-section">

            {{-- Grade Header --}}
            <div class="grade-header">

                <div class="grade-title">
                    {{ $gradeName }}
                </div>

                <div class="grade-stats">

                    Total: {{ $total }}

                    &nbsp;&nbsp;&nbsp;

                    Active: {{ $activeCount }}

                    &nbsp;&nbsp;&nbsp;

                    Inactive: {{ $inactiveCount }}

                </div>

            </div>


            {{-- Student Table --}}
            <table>

                <thead>
                    <tr>

                        <th width="5%">
                            #
                        </th>

                        <th width="25%">
                            FULL NAME
                        </th>

                        <th width="20%">
                            INITIAL NAME
                        </th>

                        <th width="15%">
                            CUSTOM ID
                        </th>

                        <th width="20%">
                            TEMPORARY QR CODE
                        </th>

                        <th width="15%">
                            STATUS
                        </th>

                    </tr>
                </thead>


                <tbody>

                    @foreach($students as $index => $student)

                        <tr>

                            <td class="center">
                                {{ $index + 1 }}
                            </td>

                            <td>
                                {{ $student->full_name ?? '-' }}
                            </td>

                            <td>
                                {{ $student->initial_name ?? '-' }}
                            </td>

                            <td class="center">
                                {{ $student->custom_id ?? '-' }}
                            </td>

                            <td class="center qr">
                                {{ $student->temporary_qr_code ?? '-' }}
                            </td>

                            <td class="center">

                                @if($student->is_active)

                                    <span class="active">
                                        Active
                                    </span>

                                @else

                                    <span class="inactive">
                                        Inactive
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        {{-- Separator --}}
        @if(!$loop->last)
            <div class="separator"></div>
        @endif


    @empty

        <div style="text-align:center; margin-top:50px;">
            No student records found.
        </div>

    @endforelse


    {{-- Footer --}}
    <div class="footer">

        Generated by {{ config('app.name', 'EDU NEXORA') }}

        on {{ now()->format('d M Y, h:i A') }}

    </div>

</body>

</html>