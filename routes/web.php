<?php

use App\Http\Controllers\User\UserLoginController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEmployeeController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\User\UserRegisterController;
use App\Http\Controllers\Admin\AdminAppointmentController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminPatientRecordController;
use App\Http\Controllers\Admin\AdminAuthorizationController;
use App\Http\Controllers\Admin\AdminSmsController;
use App\Http\Controllers\User\UserVerificationController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\User\UserRecordsController;
use App\Http\Controllers\User\UserResetPasswordController;
use App\Http\Controllers\Admin\AdminQRController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;


Route::get('/', function () {
    return view('just');
})->name('/');


Route::prefix('account')->middleware('user.guest')->group(function () {
    Route::get('login', [UserLoginController::class, 'create'])->name('user.login');
    Route::post('authenticate', [UserLoginController::class, 'authenticate']);
    Route::get('cities/{provinceId}', [UserRegisterController::class, 'populateCities']);
    Route::get('register', [UserRegisterController::class, 'create'])->name('user.register');
    Route::post('register/process', [UserRegisterController::class, 'processRegister']);
    Route::get('verification/{number}', [UserVerificationController::class, 'verify'])->name('user.verify');
    Route::get('verification', [UserVerificationController::class, 'create'])->name('user.verification');
    Route::get('forgot-password', [UserResetPasswordController::class, 'create'])->name('user.forgot.password');
    Route::post('verification/process', [UserVerificationController::class, 'process'])->name('user.verification.process');
    Route::post('verification/send-otp', [UserVerificationController::class, 'sendOtp'])->name('user.verification.sendOtp');
    Route::post('reset/process', [UserResetPasswordController::class, 'process'])->name('user.reset.process');
    Route::post('reset/send-otp', [UserResetPasswordController::class, 'sendOtp'])->name('user.reset.sendOtp');
});

Route::middleware('user.auth')->group(function () {
    Route::get('dashboard', [UserDashboardController::class, 'create'])->name('user.dashboard');
    Route::get('user/fetch/id', [UserDashboardController::class, 'fetch'])->name('fetch.id');
    Route::post('appointment/fetch', [UserDashboardController::class, 'fetchAppointments']);
    Route::post('book/appointment', [UserDashboardController::class, 'bookAppointment'])->name('book.appointment');
    Route::post('cancel/appointment', [UserDashboardController::class, 'cancelAppointment'])->name('cancel.appointment');
    Route::post('appointment/populate', [UserDashboardController::class, 'populateAppointments']);
    Route::get('records', [UserRecordsController::class, 'create'])->name('user.record');
    Route::post('authorization/populate', [UserRecordsController::class, 'populateAuthorizations']);
    Route::post('record/populate', [UserRecordsController::class, 'populateRecords']);
    Route::post('/record/modal/populate/{id}', [UserRecordsController::class, 'populateModalRecords']);
    Route::get('payment', [UserDashboardController::class, 'create'])->name('user.payment');
    Route::get('logout', [UserLoginController::class, 'logout'])->name('user.logout');
    //PAYMMENT
    Route::get('payment', [UserDashboardController::class, 'indexPayment'])->name('user.payment');
    Route::get('dashboard/payment', [UserDashboardController::class, 'getLatestAppointmentDetails']);
    Route::get('payment/history', [UserDashboardController::class, 'paymentHistory'])->name('user.payment.history');
});

Route::prefix('admin')->middleware('admin.guest')->group(function () {
    Route::get('login', [AdminLoginController::class, 'view'])->name('admin.login');
    Route::post('authenticate', [AdminLoginController::class, 'authenticate'])->name('admin.authenticate');
});

Route::get('/qr/fetch', [AdminQRController::class, 'fetchQRData']);
Route::prefix('admin')->middleware(['admin.auth', 'role:Admin'])->group(function () {
    //PAYMENTS
    Route::get('/payments', function () { return view('admin.payment');})->name('admin.payments');
    Route::get('/qr', function () { return view('admin.qr'); })->name('admin.qr');
    Route::get('/qr/fetch', [AdminQRController::class, 'fetchQRData'])->name('admin.qr.fetch');
    Route::post('/qr/add', [AdminQRController::class, 'addQR']);
    Route::get('/qrs/{id}', [AdminQRController::class, 'show']); 
    Route::post('/qrs/{id}', [AdminQRController::class, 'update']); 
    //DASHBOARD
    Route::get('dashboard', [AdminDashboardController::class, 'view'])->name('admin.dashboard');
    Route::get('/line-chart-data/{filter}', [AdminDashboardController::class, 'getLineChartData']);
    Route::get('/doughnut-chart-data', [AdminDashboardController::class, 'getDoughnutChartData']);
    Route::get('/doughnut-chart-data/{filter}', [AdminDashboardController::class, 'getDoughnutChartData']);
    Route::get('demographic-data', [AdminDashboardController::class, 'getDemographicData'])->name('admin.demographic-data');

    //EMPLOYEE
    Route::get('employee', [AdminEmployeeController::class, 'view'])->name('admin.employee');
    Route::get('employee/fetch/{id}', [AdminEmployeeController::class, 'fetch']);
    Route::post('employee/populate', [AdminEmployeeController::class, 'fetchActiveEmployees']);
    Route::post('employee/populateDeactivated', [AdminEmployeeController::class, 'fetchDeactiveEmployees']);
    Route::post('employee/update', [AdminEmployeeController::class, 'update']);
    Route::post('employee/store', [AdminEmployeeController::class, 'store']);
    Route::post('employee/deactivate', [AdminEmployeeController::class, 'deactivate']);
    Route::post('employee/activate', [AdminEmployeeController::class, 'activate']);

    //USER
    Route::get('user', [AdminUserController::class, 'index'])->name('admin.user');
    Route::get('user/fetch/{id}', [AdminUserController::class, 'fetch']);
    Route::post('users/populate', [AdminUserController::class, 'fetchActiveUsers']);
    Route::post('users/populateDeactivated', [AdminUserController::class, 'fetchDeactiveUsers']);
    Route::post('user/store', [AdminUserController::class, 'store']);
    Route::post('user/update', [AdminUserController::class, 'update']);
    Route::post('user/deactivate', [AdminUserController::class, 'deactivate']);
    Route::post('user/activate', [AdminUserController::class, 'activate']);

    //DOCTOR
    Route::get('record', [AdminPatientRecordController::class, 'view'])->name('admin.patient.record');
    Route::post('record/store', [AdminPatientRecordController::class, 'store']);
    Route::post('record/user/populate', [AdminPatientRecordController::class, 'populateUsers']);
    Route::post('record/populate', [AdminPatientRecordController::class, 'populateRecords']);
    Route::post('record/add', [AdminPatientRecordController::class, 'addRecord']);
    Route::post('record/store', [AdminPatientRecordController::class, 'storeRecord']);
    Route::get('record/appointments/{id}', [AdminAuthorizationController::class, 'getAppointmentDates']);
    Route::post('record/delete', [AdminPatientRecordController::class, 'deleteRecord']);
    Route::post('record/save', [AdminPatientRecordController::class, 'saveRecord']);

    Route::get('authorization', [AdminAuthorizationController::class, 'view'])->name('admin.authorization');
    Route::post('authorization/user/populate', [AdminAuthorizationController::class, 'populateUsers']);
    Route::post('authorization/populate', [AdminAuthorizationController::class, 'populateRecords']);
    Route::post('authorization/store', [AdminAuthorizationController::class, 'store'])->name('authorization.store');
    Route::post('authorization/update', [AdminAuthorizationController::class, 'update'])->name('authorization.update');
    //STAFF
    Route::get('appointments/pending', [AdminAppointmentController::class, 'viewPending'])->name('admin.appointments.pending');
    Route::get('appointments/list', [AdminAppointmentController::class, 'viewAppointments'])->name('admin.appointments.list');
    Route::post('appointment/pending/populate', [AdminAppointmentController::class, 'populatePendingAppointment']);
    Route::post('appointment/schedule/populate', [AdminAppointmentController::class, 'populateScheduledAppointment']);
    Route::post('appointment/populate', [AdminAppointmentController::class, 'populateAppointmentList']);
    Route::get('appointment/fetch/{id}', [AdminAppointmentController::class, 'fetch']);
    Route::post('appointment/confirm', [AdminAppointmentController::class, 'confirm']);
    Route::post('appointment/reject', [AdminAppointmentController::class, 'reject']);
    Route::post('appointment/update', [AdminAppointmentController::class, 'update']);
    Route::post('appointment/schedule/generate-pdf', [AdminAppointmentController::class, 'generateSchedulePDF'])->name('generate.schedule.pdf');

    Route::get('sms', [AdminSmsController::class, 'view'])->name('admin.sms');
    Route::post('sms/user/populate', [AdminSmsController::class, 'populateUsers']);
    Route::post('sms/send', [AdminSmsController::class, 'confirm']);
});

Route::prefix('staff')->middleware(['admin.auth', 'role:Staff'])->group(function () {
    Route::get('appointment/pending', [AdminAppointmentController::class, 'viewPending'])->name('staff.pending.appointment');
    Route::post('appointment/pending/populate', [AdminAppointmentController::class, 'populatePendingAppointment']);
    Route::get('appointment/fetch/{id}', [AdminAppointmentController::class, 'fetch']);
    Route::post('appointment/confirm', [AdminAppointmentController::class, 'confirm']);
    Route::post('appointment/reject', [AdminAppointmentController::class, 'reject']);
    Route::post('/admin/appointment/update/{appointmentId}', [AdminAppointmentController::class, 'update']);
    Route::get('appointment/list', [AdminAppointmentController::class, 'viewList'])->name('staff.appointment.list');
    Route::post('appointment/populate', [AdminAppointmentController::class, 'populateAppointmentList']);
    Route::get('notification', [AdminNotificationController::class, 'index'])->name('staff.notification');
    Route::get('transaction', [AdminNotificationController::class, 'index'])->name('staff.transaction');
});

Route::prefix('admin')->middleware('admin.auth')->group(function () {
    Route::get('logout', [AdminLoginController::class, 'logout'])->name('admin.logout');
});
