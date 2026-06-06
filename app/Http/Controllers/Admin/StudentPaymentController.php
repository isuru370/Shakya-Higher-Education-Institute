<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\PaymentsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StudentPaymentController extends Controller
{
    public function importPayments(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        Excel::import(
            new PaymentsImport(),
            $request->file('file')
        );

        return back()->with(
            'success',
            'Payments imported successfully.'
        );
    }
}
