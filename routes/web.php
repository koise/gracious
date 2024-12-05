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
use App\Http\Controllers\User\UserVerificationController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\User\UserForgotController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;


Route::get('/', function () {
    return view('just');
})->name('/');

Route::get('/index', function () {
    return view('index');
});



Route::prefix('account')->middleware('user.guest')->group(function () {
    Route::get('login', [UserLoginController::class, 'create'])->name('user.login');
    Route::post('login/authenticate', [UserLoginController::class, 'authenticate'])->name('user.authenticate');
    Route::get('cities/{provinceId}', [UserRegisterController::class, 'populateCities']);
    Route::get('register', [UserRegisterController::class, 'create'])->name('user.register');
    Route::post('register/process', [UserRegisterController::class, 'processRegister']);
    Route::get('verification/{number}', [UserVerificationController::class, 'verify'])->name('user.verify');
    Route::get('verification', [UserVerificationController::class, 'create'])->name('user.verification');
    Route::get('forgot-password', [UserForgotController::class, 'create'])->name('user.forgot.password');
    Route::post('verification/process', [UserVerificationController::class, 'process'])->name('user.verification.process');
    Route::post('verification/send-otp', [UserVerificationController::class, 'sendOtp'])->name('user.verification.sendOtp');
});

Route::middleware('user.auth')->group(function () {
    Route::get('dashboard', [UserDashboardController::class, 'create'])->name('user.dashboard');
    Route::get('user/fetch/id', [UserDashboardController::class, 'fetch'])->name('fetch.id');
    Route::post('appointment/fetch', [UserDashboardController::class, 'fetchAppointments']);
    Route::post('book/appointment', [UserDashboardController::class, 'bookAppointment'])->name('book.appointment');
    Route::post('cancel/appointment', [UserDashboardController::class, 'cancelAppointment'])->name('cancel.appointment');
    Route::post('appointment/populate', [UserDashboardController::class, 'populateAppointments']);
    Route::get('notification', [UserDashboardController::class, 'create'])->name('user.notification');
    Route::get('records', [UserDashboardController::class, 'create'])->name('user.record');
    Route::get('payment', [UserDashboardController::class, 'create'])->name('user.payment');
    Route::get('logout', [UserLoginController::class, 'logout'])->name('user.logout');
});

Route::prefix('admin')->middleware('admin.guest')->group(function () {
    Route::get('login', [AdminLoginController::class, 'view'])->name('admin.login');
    Route::post('authenticate', [AdminLoginController::class, 'authenticate'])->name('admin.authenticate');
});

Route::prefix('admin')->middleware(['admin.auth', 'role:Admin'])->group(function () {
    //DASHBOARD
    Route::get('dashboard', [AdminDashboardController::class, 'view'])->name('admin.dashboard');
    Route::get('chart-data', [AdminDashboardController::class, 'getChartData'])->name('admin.chart-data');
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
    Route::get('patient/record', [AdminPatientRecordController::class, 'view'])->name('admin.patient.record');
    Route::get('patient/authorization', [AdminAuthorizationController::class, 'view'])->name('admin.authorization');
    Route::post('patient/record/store', [AdminPatientRecordController::class, 'store']);
    Route::post('patient/record/populate', [AdminPatientRecordController::class, 'populate']);

    //STAFF
    Route::get('appointment', [AdminAppointmentController::class, 'viewPending'])->name('admin.appointment');
    Route::post('appointment/pending/populate', [AdminAppointmentController::class, 'populatePendingAppointment']);
    Route::get('appointment/fetch/{id}', [AdminAppointmentController::class, 'fetch']);
    Route::post('appointment/confirm', [AdminAppointmentController::class, 'confirm']);
    Route::post('appointment/reject', [AdminAppointmentController::class, 'reject']);
    Route::post('appointment/populate', [AdminAppointmentController::class, 'populateAppointmentList']);


    Route::get('notification', [AdminNotificationController::class, 'index'])->name('admin.notification');
    Route::get('transaction', [AdminNotificationController::class, 'index'])->name('admin.transaction');
});

Route::prefix('staff')->middleware(['admin.auth', 'role:Staff'])->group(function () {
    Route::get('appointment/pending', [AdminAppointmentController::class, 'viewPending'])->name('staff.pending.appointment');
    Route::post('appointment/pending/populate', [AdminAppointmentController::class, 'populatePendingAppointment']);
    Route::get('appointment/fetch/{id}', [AdminAppointmentController::class, 'fetch']);
    Route::post('appointment/confirm', [AdminAppointmentController::class, 'confirm']);
    Route::post('appointment/reject', [AdminAppointmentController::class, 'reject']);

    Route::get('appointment/list', [AdminAppointmentController::class, 'viewList'])->name('staff.appointment.list');
    Route::post('appointment/populate', [AdminAppointmentController::class, 'populateAppointmentList']);
    Route::get('notification', [AdminNotificationController::class, 'index'])->name('staff.notification');
    Route::get('transaction', [AdminNotificationController::class, 'index'])->name('staff.transaction');
});

Route::prefix('admin')->middleware('admin.auth')->group(function () {
    Route::get('logout', [AdminLoginController::class, 'logout'])->name('admin.logout');
});
