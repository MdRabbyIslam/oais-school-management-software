<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Section;
use App\Models\SmsLog;
use App\Services\Sms\SmsServiceInterface;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    protected $sms;
    public function __construct(SmsServiceInterface $sms)
    {
        $this->sms = $sms;
    }

    /** Show the custom‐SMS form */
    public function showForm()
    {
        return view('pages.sms.custom');
    }

    /** Handle form post */
    public function sendCustom(Request $req)
    {
        $req->validate([
            'recipient_type' => 'required|in:all,student,section,custom',
            'student_id'     => 'required_if:recipient_type,student|nullable|exists:students,id',
            'section_id'     => 'required_if:recipient_type,section|nullable|exists:sections,id',
            'custom_numbers' => 'required_if:recipient_type,custom|string|nullable',
            'message'        => 'required|string|max:320',
        ]);

        // determine numbers
        switch($req->recipient_type) {
            case 'all':
                $numbers = Student::pluck('primary_guardian_contact')->unique()->toArray();
                $student_ids = Student::pluck('id')->toArray();
                break;
            case 'student':
                $s = Student::findOrFail($req->student_id);
                $numbers = [$s->primary_guardian_contact];
                $student_ids = [$s->id];
                break;
            case 'section':
                $students = Student::where('section_id', $req->section_id)->get();
                $numbers = $students->pluck('primary_guardian_contact')->unique()->toArray();
                $student_ids = $students->pluck('id')->toArray();
                break;
            case 'custom':
                // comma‐separated
                $numbers = array_filter(array_map('trim', explode(',', $req->custom_numbers)));
                $student_ids = [];
                break;
        }

        $message = $req->message;
        $sentCount = 0;
        foreach($numbers as $i => $num) {
            try {
                $response = $this->sms->send($num, $message);
                $status   = data_get($response, 'response_code') === 202 ? 'success' : 'error';
                $sentCount += ($status==='success');
            } catch(\Exception $e) {
                $response = ['error' => $e->getMessage()];
                $status   = 'error';
            }

            // Log it
            SmsLog::create([
                'student_id' => $student_ids[$i] ?? null,
                'to'         => $num,
                'message'    => $message,
                'response'   => json_encode($response),
                'status'     => $status,
            ]);
        }

        return back()->with('success', "Tried sending to ".count($numbers)." number(s), {$sentCount} succeeded.");
    }

    /** Logs index */
    public function indexLogs(Request $req)
    {
        $query = SmsLog::with('student')->latest();

        // apply status filter
        if ($status = $req->query('status')) {
            $query->where('status', $status);
        }

        // apply date range filter
        if ($from = $req->query('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $req->query('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(25)->appends($req->query());

        return view('pages.sms.logs', compact('logs'));
    }

    public function resendLogs(Request $req)
    {
        $req->validate([
            'selected' => 'required|array',
            'selected.*' => 'exists:sms_logs,id'
        ]);

        $logs = SmsLog::whereIn('id', $req->input('selected'))->get();
        $sms  = app(\App\Services\Sms\SmsServiceInterface::class);

        foreach ($logs as $log) {
            try {
                $response   = $sms->send($log->to, $log->message);
                $code       = data_get($response, 'response_code', null);
                $status     = $code === 202 ? 'success' : 'error';
            } catch (\Exception $e) {
                $response = ['error' => $e->getMessage()];
                $status   = 'error';
            }

            $log->update([
                'status'        => $status,
                'response'      => json_encode($response),
                'response_code' => $code ?? null,
            ]);
        }

        return back()->with('success', 'Resent '.count($logs).' SMS.');
    }

    /** AJAX for Select2 student list */
    public function studentsAjax(Request $req)
    {
        $q = $req->input('q', '');
        $students = Student::where('name','like',"%{$q}%")
            ->limit(30)
            ->get(['id','name','primary_guardian_contact']);

        $results = $students->map(fn($s) => [
            'id'   => $s->id,
            'text' => "{$s->name} ({$s->primary_guardian_contact})"
        ]);

        return response()->json(['results' => $results]);
    }

    /** AJAX for Select2 section list */
    public function sectionsAjax(Request $req)
    {
        $q = $req->input('q', '');
        $sections = Section::with('schoolClass')
            ->whereHas('schoolClass', fn($q2)=>$q2->where('name','like',"%{$q}%"))
            ->orWhere('section_name','like',"%{$q}%")
            ->limit(30)
            ->get();

        $results = $sections->map(fn($sec) => [
            'id'   => $sec->id,
            'text' => "{$sec->schoolClass->name} – {$sec->section_name}"
        ]);

        return response()->json(['results' => $results]);
    }

    /**
     * Send a single SMS with total due fees for this student.
     */
    public function sendDueSms(Student $student)
    {
        // 1) Compute total outstanding due
        //    Sum over every invoice-item: amount minus all allocations
        $student->load('invoices.items.paymentAllocations');
        $totalDue = $student->invoices
            ->flatMap->items
            ->reduce(function($carry, $item) {
                $paid = $item->paymentAllocations->sum('amount');
                $due  = $item->amount - $paid;
                return $carry + max($due, 0);
            }, 0.0);

        if ($totalDue <= 0) {
            return back()->with('error', 'No outstanding fees for this student.');
        }

        // 2) Build and send the SMS
        $number  = $student->primary_guardian_contact;
        $message = "Dear Parent, \nPlease, are being informed that {$student->name} has "
                 . number_format($totalDue,2)
                 . ". TK Due.\n\nOMS.";


        // dd([$message, $number]);
        try {
            $response = $this->sms->send($number, $message);
            $code     = data_get($response, 'response_code');
            $status   = $code === 202 ? 'success' : 'error';
        } catch (\Exception $e) {
            $response = ['error' => $e->getMessage()];
            $status   = 'error';
            $code     = null;
        }


        // 3) Log the attempt
        SmsLog::create([
            'student_id'    => $student->id,
            'to'            => $number,
            'message'       => $message,
            'response'      => json_encode($response),
            'status'        => $status,
            'response_code' => $code,
        ]);

        // 4) Redirect back with a flash
        if ($status === 'success') {
            return back()->with('success', "Due‐fee SMS sent successfully.");
        }
        return back()->with('error', "Failed to send SMS. Response: ".json_encode($response));
    }


    public function sendDueSmsToAllStudent(Student $student)
    {

        

        // 1) Compute total outstanding due
        //    Sum over every invoice-item: amount minus all allocations
        $student->load('invoices.items.paymentAllocations');
        $totalDue = $student->invoices
            ->flatMap->items
            ->reduce(function($carry, $item) {
                $paid = $item->paymentAllocations->sum('amount');
                $due  = $item->amount - $paid;
                return $carry + max($due, 0);
            }, 0.0);

        if ($totalDue <= 0) {
            return back()->with('error', 'No outstanding fees for this student.');
        }

        // 2) Build and send the SMS
        $number  = $student->primary_guardian_contact;
        $message = "Dear Parent, \nPlease, are being informed that {$student->name} has "
                 . number_format($totalDue,2)
                 . ". TK Due.\n\nOMS.";


        // dd([$message, $number]);
        try {
            $response = $this->sms->send($number, $message);
            $code     = data_get($response, 'response_code');
            $status   = $code === 202 ? 'success' : 'error';
        } catch (\Exception $e) {
            $response = ['error' => $e->getMessage()];
            $status   = 'error';
            $code     = null;
        }


        // 3) Log the attempt
        SmsLog::create([
            'student_id'    => $student->id,
            'to'            => $number,
            'message'       => $message,
            'response'      => json_encode($response),
            'status'        => $status,
            'response_code' => $code,
        ]);

        // 4) Redirect back with a flash
        if ($status === 'success') {
            return back()->with('success', "Due‐fee SMS sent successfully.");
        }
        return back()->with('error', "Failed to send SMS. Response: ".json_encode($response));
    }
}
