<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\StudentAttendancesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentAttendanceController extends Controller
{
    public function importAttendances(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        Excel::import(
            new StudentAttendancesImport(),
            $request->file('file')
        );

        return back()->with(
            'success',
            'Attendances imported successfully.'
        );
    }
}
