<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ExamAssessmentController;
use App\Http\Controllers\ExamAssessmentSetupController;
use App\Http\Controllers\ExamMarkEntryController;
use App\Http\Controllers\GradeSchemeController;
use App\Http\Controllers\FeeAssignmentController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\FeeGeneratorController;
use App\Http\Controllers\FeeGroupController;
use App\Http\Controllers\FeeTypeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceGenerationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectAssignmentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\GradingPolicyController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Models\ClassFeeAmount;
use App\Models\Fee;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    $duplicates = DB::table('fee_assignments as fa')
        ->join('student_enrollments as se', 'se.id', '=', 'fa.student_enrollment_id')
        ->join('students as s', 's.id', '=', 'fa.student_id')
        ->join('classes as c', 'c.id', '=', 'se.class_id')
        ->join('fees as f', 'f.id', '=', 'fa.fee_id')
        ->select(
            'fa.student_enrollment_id',
            'fa.student_id',
            's.name as student_name',
            'c.id as class_id',
            'c.name as class_name',
            'fa.fee_id',
            'f.fee_name as fee_name',
            'fa.term_id',
            DB::raw('COUNT(*) as duplicate_count'),
            DB::raw('GROUP_CONCAT(fa.id ORDER BY fa.id SEPARATOR \',\') as duplicate_assignment_ids'),
            DB::raw('GROUP_CONCAT(fa.amount ORDER BY fa.id SEPARATOR \',\') as duplicate_amounts'),
            DB::raw('GROUP_CONCAT(DATE_FORMAT(fa.due_date, \'%Y-%m-%d\') ORDER BY fa.id SEPARATOR \',\') as duplicate_due_dates')
        )
        ->where('se.status', 'active') // current enrollment
        ->where('fa.status', 'active')
        ->whereNotNull('fa.student_enrollment_id')
        // For non-term fees, term_id is null and grouped together; for term-based fees, duplicates must be in same term.
        ->groupBy('fa.student_enrollment_id', 'fa.student_id', 's.name', 'c.id', 'c.name', 'fa.fee_id', 'f.fee_name', 'fa.term_id')
        ->havingRaw('COUNT(*) > 1')
        ->orderByDesc('duplicate_count')
        ->get();

    return response()->json($duplicates);
});

Route::get('/test2', function (Request $request) {
    $dryRun = $request->boolean('dry_run', false);

    // Oldest assignment in each duplicate group:
    // same enrollment + same fee + same term (or both null term), active only.
    $oldestDuplicateAssignmentIds = DB::table('fee_assignments as fa')
        ->join('student_enrollments as se', 'se.id', '=', 'fa.student_enrollment_id')
        ->where('se.status', 'active')
        ->where('fa.status', 'active')
        ->whereNotNull('fa.student_enrollment_id')
        ->groupBy('fa.student_enrollment_id', 'fa.student_id', 'fa.fee_id', 'fa.term_id')
        ->havingRaw('COUNT(*) > 1')
        ->selectRaw('MIN(fa.id) as oldest_fee_assignment_id')
        ->pluck('oldest_fee_assignment_id')
        ->map(fn ($id) => (int) $id)
        ->values();

    if ($oldestDuplicateAssignmentIds->isEmpty()) {
        return response()->json([
            'dry_run' => $dryRun,
            'message' => 'No duplicate groups found.',
            'oldest_fee_assignment_ids' => [],
            'stats' => [
                'duplicate_groups' => 0,
                'invoice_items_targeted' => 0,
                'allocations_deleted' => 0,
                'invoice_items_deleted' => 0,
                'invoices_deleted' => 0,
                'payments_deleted' => 0,
            ],
        ]);
    }

    $affectedInvoiceItemIds = DB::table('invoice_items')
        ->whereIn('fee_assignment_id', $oldestDuplicateAssignmentIds)
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->values();

    $affectedInvoiceIds = DB::table('invoice_items')
        ->whereIn('fee_assignment_id', $oldestDuplicateAssignmentIds)
        ->pluck('invoice_id')
        ->unique()
        ->map(fn ($id) => (int) $id)
        ->values();

    $allocationsFromTargetItems = $affectedInvoiceItemIds->isNotEmpty()
        ? DB::table('payment_allocations')->whereIn('invoice_item_id', $affectedInvoiceItemIds)->count()
        : 0;

    $emptyInvoiceIdsAfterItemDelete = collect();
    if ($affectedInvoiceIds->isNotEmpty()) {
        $emptyInvoiceIdsAfterItemDelete = DB::table('invoices as i')
            ->leftJoin('invoice_items as ii', 'ii.invoice_id', '=', 'i.id')
            ->whereIn('i.id', $affectedInvoiceIds)
            ->groupBy('i.id')
            ->havingRaw(
                'SUM(CASE WHEN ii.fee_assignment_id NOT IN (' . $oldestDuplicateAssignmentIds->implode(',') . ') THEN 1 ELSE 0 END) = 0'
            )
            ->pluck('i.id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    $allocationsOnEmptyInvoices = $emptyInvoiceIdsAfterItemDelete->isNotEmpty()
        ? DB::table('payment_allocations')->whereIn('invoice_id', $emptyInvoiceIdsAfterItemDelete)->count()
        : 0;

    $paymentIdsPotentiallyOrphaned = collect();
    if ($affectedInvoiceItemIds->isNotEmpty() || $emptyInvoiceIdsAfterItemDelete->isNotEmpty()) {
        $paymentIdsPotentiallyOrphaned = DB::table('payment_allocations')
            ->where(function ($q) use ($affectedInvoiceItemIds, $emptyInvoiceIdsAfterItemDelete) {
                if ($affectedInvoiceItemIds->isNotEmpty()) {
                    $q->whereIn('invoice_item_id', $affectedInvoiceItemIds);
                }
                if ($emptyInvoiceIdsAfterItemDelete->isNotEmpty()) {
                    $q->orWhereIn('invoice_id', $emptyInvoiceIdsAfterItemDelete);
                }
            })
            ->pluck('payment_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    $preview = [
        'dry_run' => $dryRun,
        'oldest_fee_assignment_ids' => $oldestDuplicateAssignmentIds,
        'stats' => [
            'duplicate_groups' => $oldestDuplicateAssignmentIds->count(),
            'invoice_items_targeted' => $affectedInvoiceItemIds->count(),
            'allocations_from_target_items' => $allocationsFromTargetItems,
            'invoices_that_become_empty' => $emptyInvoiceIdsAfterItemDelete->count(),
            'allocations_on_empty_invoices' => $allocationsOnEmptyInvoices,
        ],
    ];

    if ($dryRun) {
        return response()->json($preview);
    }

    $result = DB::transaction(function () use (
        $affectedInvoiceItemIds,
        $emptyInvoiceIdsAfterItemDelete,
        $paymentIdsPotentiallyOrphaned
    ) {
        $allocationsDeletedFromItems = 0;
        if ($affectedInvoiceItemIds->isNotEmpty()) {
            $allocationsDeletedFromItems = DB::table('payment_allocations')
                ->whereIn('invoice_item_id', $affectedInvoiceItemIds)
                ->delete();
        }

        $invoiceItemsDeleted = 0;
        if ($affectedInvoiceItemIds->isNotEmpty()) {
            $invoiceItemsDeleted = DB::table('invoice_items')
                ->whereIn('id', $affectedInvoiceItemIds)
                ->delete();
        }

        $allocationsDeletedFromInvoices = 0;
        $invoicesDeleted = 0;
        if ($emptyInvoiceIdsAfterItemDelete->isNotEmpty()) {
            $allocationsDeletedFromInvoices = DB::table('payment_allocations')
                ->whereIn('invoice_id', $emptyInvoiceIdsAfterItemDelete)
                ->delete();

            $invoicesDeleted = DB::table('invoices')
                ->whereIn('id', $emptyInvoiceIdsAfterItemDelete)
                ->delete();
        }

        $paymentsDeleted = 0;
        if ($paymentIdsPotentiallyOrphaned->isNotEmpty()) {
            $orphanPaymentIds = DB::table('payments as p')
                ->leftJoin('payment_allocations as pa', 'pa.payment_id', '=', 'p.id')
                ->whereIn('p.id', $paymentIdsPotentiallyOrphaned)
                ->whereNull('pa.id')
                ->pluck('p.id');

            if ($orphanPaymentIds->isNotEmpty()) {
                $paymentsDeleted = DB::table('payments')
                    ->whereIn('id', $orphanPaymentIds)
                    ->delete();
            }
        }

        return [
            'allocations_deleted' => $allocationsDeletedFromItems + $allocationsDeletedFromInvoices,
            'invoice_items_deleted' => $invoiceItemsDeleted,
            'invoices_deleted' => $invoicesDeleted,
            'payments_deleted' => $paymentsDeleted,
        ];
    });

    return response()->json(array_merge($preview, ['deleted' => $result]));
});

Route::get('/test3', function (Request $request) {
    $dryRun = $request->boolean('dry_run', false);

    // Same duplicate-group definition as /test2, but now we only remove oldest
    // assignments that no longer have any invoice item linked.
    $oldestDuplicateAssignmentIds = DB::table('fee_assignments as fa')
        ->join('student_enrollments as se', 'se.id', '=', 'fa.student_enrollment_id')
        ->where('se.status', 'active')
        ->where('fa.status', 'active')
        ->whereNotNull('fa.student_enrollment_id')
        ->groupBy('fa.student_enrollment_id', 'fa.student_id', 'fa.fee_id', 'fa.term_id')
        ->havingRaw('COUNT(*) > 1')
        ->selectRaw('MIN(fa.id) as oldest_fee_assignment_id')
        ->pluck('oldest_fee_assignment_id')
        ->map(fn ($id) => (int) $id)
        ->values();

    $deletableOldestIds = collect();
    if ($oldestDuplicateAssignmentIds->isNotEmpty()) {
        $deletableOldestIds = DB::table('fee_assignments as fa')
            ->whereIn('fa.id', $oldestDuplicateAssignmentIds)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('invoice_items as ii')
                    ->whereColumn('ii.fee_assignment_id', 'fa.id');
            })
            ->pluck('fa.id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    $response = [
        'dry_run' => $dryRun,
        'oldest_duplicate_ids_checked' => $oldestDuplicateAssignmentIds,
        'deletable_oldest_ids' => $deletableOldestIds,
        'stats' => [
            'oldest_duplicate_ids_checked_count' => $oldestDuplicateAssignmentIds->count(),
            'deletable_oldest_ids_count' => $deletableOldestIds->count(),
        ],
    ];

    if ($dryRun) {
        return response()->json($response);
    }

    $deletedCount = 0;
    if ($deletableOldestIds->isNotEmpty()) {
        $deletedCount = DB::table('fee_assignments')
            ->whereIn('id', $deletableOldestIds)
            ->delete();
    }

    return response()->json(array_merge($response, [
        'deleted_fee_assignments_count' => $deletedCount,
    ]));
});


// Auth::routes();


Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post("/login", [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post("/logout", [App\Http\Controllers\Auth\LoginController::class, 'logout']);

// Admin Dashboard (Only for authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('dashboard');


    // Route::post('students/{student}/sms/send-due', [StudentSmsController::class, 'sendDue'])
    //  ->name('students.sms.sendDue');
    Route::get('sms/custom',    [SmsController::class,'showForm'])->name('sms.custom');
    Route::post('sms/custom',   [SmsController::class,'sendCustom']);
    Route::get('sms/logs',      [SmsController::class,'indexLogs'])->name('sms.logs');
    Route::post('sms/logs/resend',[SmsController::class,'resendLogs'])->name('sms.resend');
    Route::post('students/{student}/sms/due', [SmsController::class, 'sendDueSms'])
        ->name('sms.due');
    Route::post('students/send-due', [SmsController::class, 'sendDueSmsToAllStudent'])
        ->name('sms.due_to_all_student');


    Route::get('api/students',      [SmsController::class,'studentsAjax']);
    Route::get('api/sections',      [SmsController::class,'sectionsAjax']);

    Route::get('/students/ajax', [StudentController::class,'ajaxSelect2'])
     ->name('students.ajax');


    Route::middleware(['role:superadmin,admin'])->group(function () {
        Route::resource('academic_years', AcademicYearController::class);
        Route::resource('terms', TermController::class);
        Route::resource('exam-assessments', ExamAssessmentController::class);
        Route::resource('grade-schemes', GradeSchemeController::class);
        Route::resource('grading-policies', GradingPolicyController::class);
        Route::get('exam-assessment-classes/{examAssessmentClass}/setup', [ExamAssessmentSetupController::class, 'edit'])
            ->name('exam-assessment-classes.setup.edit');
        Route::post('exam-assessment-classes/{examAssessmentClass}/setup/subjects', [ExamAssessmentSetupController::class, 'storeSubject'])
            ->name('exam-assessment-classes.setup.store-subject');
        Route::delete('exam-assessment-subjects/{examAssessmentSubject}', [ExamAssessmentSetupController::class, 'destroySubject'])
            ->name('exam-assessment-subjects.destroy');
        Route::get('exam-assessment-classes/{examAssessmentClass}/marks', [ExamMarkEntryController::class, 'create'])
            ->name('exam-assessment-classes.marks.create');
        Route::post('exam-assessment-classes/{examAssessmentClass}/marks', [ExamMarkEntryController::class, 'store'])
            ->name('exam-assessment-classes.marks.store');
    });
    // Only allow access based on roles
    Route::middleware(['role:superadmin,admin'])->group(function () {
        Route::resource('classes', ClassController::class);
        Route::resource('sections', SectionController::class);
        Route::resource('subjects', SubjectController::class);
        Route::resource('students', StudentController::class);
        Route::resource('teachers', TeacherController::class);

        // Admissions (admin-created applications)
        Route::get('admissions', [\App\Http\Controllers\AdmissionApplicationController::class, 'index'])->name('admissions.index');
        Route::get('admissions/create', [\App\Http\Controllers\AdmissionApplicationController::class, 'create'])->name('admissions.create');
        Route::post('admissions', [\App\Http\Controllers\AdmissionApplicationController::class, 'store'])->name('admissions.store');
        Route::get('admissions/{admission}', [\App\Http\Controllers\AdmissionApplicationController::class, 'show'])->name('admissions.show');
        // Edit admission application
        Route::get('admissions/{admission}/edit', [\App\Http\Controllers\AdmissionApplicationController::class, 'edit'])->name('admissions.edit');
        Route::put('admissions/{admission}', [\App\Http\Controllers\AdmissionApplicationController::class, 'update'])->name('admissions.update');
        // Approve / Reject admission applications
        Route::post('admissions/{admission}/approve', [\App\Http\Controllers\AdmissionApplicationController::class, 'approve'])->name('admissions.approve');
        Route::post('admissions/{admission}/reject', [\App\Http\Controllers\AdmissionApplicationController::class, 'reject'])->name('admissions.reject');

        Route::get('subject-assignments', [SubjectAssignmentController::class, 'index'])->name('subject-assignments.index');
        Route::post('subject-assignments', [SubjectAssignmentController::class, 'store'])->name('subject-assignments.store');
        // Promotions index (pending approvals)
        Route::get('promotions', [\App\Http\Controllers\StudentPromotionController::class, 'index'])->name('promotions.index');
        // Bulk promotion requests (filter and create many requests)
        Route::get('promotions/bulk', [\App\Http\Controllers\StudentPromotionController::class, 'bulkForm'])->name('promotions.bulk.form');
        Route::post('promotions/bulk', [\App\Http\Controllers\StudentPromotionController::class, 'bulkStore'])->name('promotions.bulk.store');

        // View a specific enrollment
        Route::get('/student-enrollments/{student}', [StudentEnrollmentController::class, 'view'])
            ->name('enrollments.view');

        // Update the roll number (typically a PATCH or PUT request)
        Route::patch('/student-enrollments/{student}/roll-number', [StudentEnrollmentController::class, 'editRollNumber'])
            ->name('enrollments.update-roll');

    });

    // Student Promotions
    Route::get('students/{student}/promote', [\App\Http\Controllers\StudentPromotionController::class, 'create'])->name('students.promote.create');
    Route::post('students/{student}/promote', [\App\Http\Controllers\StudentPromotionController::class, 'store'])->name('students.promote.store');

    Route::get('promotions/{promotion}', [\App\Http\Controllers\StudentPromotionController::class, 'show'])->name('promotions.show');
    Route::post('promotions/{promotion}/approve', [\App\Http\Controllers\StudentPromotionController::class, 'approve'])->name('promotions.approve');
    Route::post('promotions/{promotion}/reject', [\App\Http\Controllers\StudentPromotionController::class, 'reject'])->name('promotions.reject');
    // Bulk approve selected promotions
    Route::post('promotions/bulk-approve', [\App\Http\Controllers\StudentPromotionController::class, 'bulkApprove'])->name('promotions.bulk.approve');
    Route::post('promotions/bulk-reject', [\App\Http\Controllers\StudentPromotionController::class, 'bulkReject'])->name('promotions.bulk.reject');

    Route::middleware(['auth', 'role:superadmin,admin,teacher'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');


        Route::get('attendance-report', [AttendanceController::class, 'report'])->name('attendance.report');

        Route::get('attendance-report/pdf', [AttendanceController::class,'reportPdf'])->name('attendance.report_pdf');

    });


    Route::middleware('auth')->group(function () {
        // Fee Groups
        Route::resource('fee-groups', FeeGroupController::class)->except('show');

        // Fees
        Route::resource('fees', FeeController::class)->except('show');
        Route::get('fees/create/{fee_group}', [FeeController::class, 'create'])->name('fees.create-for-group');
        Route::post('/fees/reorder', [FeeController::class, 'reorder'])->name('fees.reorder');



        Route::prefix('fee-assignments')->group(function() {
            // Route::get('/', [FeeAssignmentController::class, 'index'])->name('fee-assignments.index');
            // Route::get('create', [FeeAssignmentController::class, 'create'])->name('fee-assignments.create');
            Route::post('preview', [FeeAssignmentController::class, 'preview'])->name('fee-assignments.preview');
            Route::post('bulk-store', [FeeAssignmentController::class, 'bulkStore'])->name('fee-assignments.bulk-store');
        });
        Route::get('fee-assignments/students-by-year', [FeeAssignmentController::class, 'getStudentsByYear'])->name('fee-assignments.get-students-by-year');

        Route::resource('fee-assignments', FeeAssignmentController::class)->middleware('auth');









        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('students/{student}/generate-invoice', [InvoiceController::class, 'generate'])->name('invoices.generate');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');


        Route::prefix('invoices')->group(function () {
            Route::get('{invoice}/payments/create', [PaymentController::class, 'create'])
                ->name('invoices.payments.create');

            Route::post('{invoice}/payments', [PaymentController::class, 'store'])
                ->name('invoices.payments.store');
        });


        Route::get('reports/monthly-payment-sheet', [PaymentController::class, 'monthlyPaymentSheet'])->name('reports.monthly_payment_sheet');
        Route::post('reports/monthly-payment-sheet', [PaymentController::class, 'showMonthlyPaymentSheet'])->name('reports.show_monthly_payment_sheet');
        Route::get('reports/monthly-payment-sheet-print', [PaymentController::class, 'monthlyPaymentSheetPrint'])->name('reports.monthly_payment_sheet_print');



    // Student-centric payment form
    Route::get('payments/create', [PaymentController::class, 'createByStudent'])
        ->name('payments.create');

    // AJAX: fetch due invoice items for a student
    Route::get('students/{student}/due-items', [PaymentController::class, 'dueItems'])
        ->name('payments.due-items');

    // Submit one or more payments
    Route::post('payments', [PaymentController::class, 'storeByStudent'])
        ->name('payments.store');


  // Student-centric payment form
    Route::get('payments/create', [PaymentController::class, 'createByStudent'])
        ->name('payments.create');

    // AJAX: fetch due invoice items for a student
    Route::get('students/{student}/due-items', [PaymentController::class, 'dueItems'])
        ->name('payments.due-items');

    // Submit one or more payments
    Route::post('payments', [PaymentController::class, 'storeByStudent'])
        ->name('payments.store');


        Route::get('payments', [PaymentController::class, 'index'])
            ->name('payments.index');
        Route::get('payments/{payment}/show', [PaymentController::class, 'show'])
            ->name('payments.show');

        // New: edit & update for student‐centric payments
        Route::get('payments/{payment}/edit',   [PaymentController::class,'editByStudent'])
            ->name('payments.edit.byStudent');
        Route::put('payments/{payment}',        [PaymentController::class,'updateByStudent'])
            ->name('payments.update.byStudent');




        Route::get('payments/{payment}/download', [PaymentController::class, 'download'])
     ->name('payments.download');


    Route::get('payments/{payment}/print', [PaymentController::class, 'print'])
     ->name('payments.print');

     Route::get('payments/{payment}/print-html', [PaymentController::class, 'printHtml'])
     ->name('payments.print.html');

     Route::get('payments/{payment}/print-bn', [PaymentController::class, 'printHTMLBn'])
     ->name('payments.printHTMLBn');

     Route::middleware(['auth'])->group(function () {
    Route::get('invoices-generate', [InvoiceGenerationController::class, 'index'])
         ->name('invoices-generate.form');

    Route::post('invoices-generate', [InvoiceGenerationController::class, 'generate'])
         ->name('invoices-generate.run');
    });


    Route::get('students/{student}/manage-fees', [StudentController::class, 'manageFees'])->name('students.manage-fees');
    Route::post('students/{student}/manage-fees', [StudentController::class, 'updateFees'])->name('students.update-fees');


    Route::get('fee-assignments-print', [FeeAssignmentController::class, 'print'])->name('fee-assignments.print');








        // Route::prefix('payments')->group(function () {
        //     Route::get('/create/{invoice}', [PaymentController::class, 'create'])->name('payments.create');
        //     Route::post('/store/{invoice}', [PaymentController::class, 'store'])->name('payments.store');
        //     Route::get('/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        //     Route::get('/receipt/{payment}', [PaymentController::class, 'download'])->name('payments.receipt');
        // });

        // // For manual term assignments
        // Route::post('assign-term-fees', [FeeAssignmentController::class, 'assignTerm'])
        //     ->name('assign-term-fees');


        // Route::get('/students/{student}/class-fee/{fee}', function(Student $student, Fee $fee) {
        //     $classAmount = ClassFeeAmount::where([
        //         'class_id' => $student->class_id,
        //         'fee_id' => $fee->id
        //     ])->first();

        //     return response()->json([
        //         'amount' => $classAmount?->amount,
        //         'class_name' => $student->class->name
        //     ]);
        // });


    });







    // Route::middleware(['auth', 'role:superadmin,admin,accountant'])->group(function () {

        // Route::resource('fee-groups', FeeGroupController::class);
        // Route::resource('fees', FeeController::class);
        // Route::resource('fee-assignments', FeeAssignmentController::class);

        // // List all invoices
        // Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');

        // // Show details of a specific invoice
        // Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

        // // Show the form to add a fee to an existing invoice
        // Route::get('invoices/{invoice}/add_fee', [InvoiceController::class, 'showAddFeeForm'])->name('invoices.add_fee_form');

        // // Add a fee to an existing invoice
        // Route::post('invoices/{invoice}/add_fee', [InvoiceController::class, 'addFeeToInvoice'])->name('invoices.add_fee');

        // Route::get('invoices/{invoice}/download', [InvoiceController::class, 'downloadInvoice'])->name('invoices.download');

        // Route::post('invoices/{invoice}/process-payment', [InvoiceController::class, 'processPayment'])->name('invoices.process_payment');


        // // Show the form to pause or resume service for a student
        // Route::get('students/{student}/services', [ServiceController::class, 'showServiceForm'])->name('services.show_form');

        // // Update the service status (pause or resume)
        // Route::put('students/{student}/services', [ServiceController::class, 'updateService'])->name('services.update');

});
