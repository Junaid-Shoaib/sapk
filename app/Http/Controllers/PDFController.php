<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use PDF;

class PDFController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Welcome to Tutsmake.com',
            'date' => date('m/d/Y')
        ];
        $pdf = PDF::loadView('testPDF', $data);
        return $pdf->stream('tutsmake.pdf');
    }

    public function ledger($id)
    {
        $entries = Entry::where('account_id',$id)->get;
        $pdf = PDF::loadView('led', $entries);
        return $pdf->stream('ledger.pdf');
    }        
}
