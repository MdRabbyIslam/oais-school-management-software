<?php

namespace App\Http\Controllers;

use App\Helpers\ConsoleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Student;
use App\Jobs\GenerateInvoicesJob;

class InvoiceGenerationController extends Controller
{
    public function index()
    {
        $students = Student::with(['schoolClass', 'section'])->orderBy('name')->get();

        // dd($students[0]);
        return view('pages.invoices.generate', compact('students'));
    }


   public function generate(Request $request)
    {
        // 1) Validate inputs, same as before
        $data = $request->validate([
            'student' => 'nullable|exists:students,id',
            'period'  => 'nullable|in:monthly,termly,annual',
            'month'   => 'nullable|date_format:Y-m',
            // 'force'   => 'nullable|boolean',
        ]);

        // dd($data);

        // 2) Build the subcommand string (everything after "php artisan")
        $subcommandParts = ['invoices:generate'];

        if (! empty($data['student'])) {
            $subcommandParts[] = '--student=' . escapeshellarg($data['student']);
        }
        if (! empty($data['period'])) {
            $subcommandParts[] = '--period=' . escapeshellarg($data['period']);
        }
        if (! empty($data['month'])) {
            $subcommandParts[] = '--month=' . escapeshellarg($data['month']);
        }
        // if (! empty($data['force'])) {
        //     $subcommandParts[] = '--force';
        // }

        $subcommand = implode(' ', $subcommandParts);

        try {
            // 3) Use our helper to run it in background, prefixing with “artisan” path
            ConsoleHelper::foundPhpAndExecInBackground($subcommand, true);

            Session::flash('success', 'Invoice generation has been started in the background.');
        } catch (\Throwable $th) {
            // If something goes wrong (e.g. PHP binary not found), show an error
            Session::flash('error', 'Failed to start invoice generation: ' . $th->getMessage());
        }

        return redirect()->route('invoices-generate.form');
    }


    // public function generate(Request $request)
    // {
    //     $data = $request->validate([
    //         'student' => 'nullable|exists:students,id',
    //         'period'  => 'nullable|in:monthly,termly,annual',
    //         'month'   => 'nullable|date_format:Y-m',
    //         'force'   => 'nullable|boolean',
    //     ]);

    //     // Dispatch the job into whatever queue you have configured:
    //     GenerateInvoicesJob::dispatch(
    //         $data['student'] ?? null,
    //         $data['period'] ?? null,
    //         $data['month'] ?? null,
    //         $data['force'] ?? false
    //     );

    //     // Flash a message—“We’ve queued it”:
    //     Session::flash('success', 'Invoice generation has been queued in the background. Check logs later for details.');

    //     return redirect()->route('invoices.generate.form');
    // }
}
