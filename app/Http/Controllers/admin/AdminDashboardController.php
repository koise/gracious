<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    public function view()
    {

        return view('admin.dashboard');
    }

    public function getDemographicData()
    {
        // Optimize by combining queries
        $statuses = Appointment::selectRaw("
            COUNT(*) AS total_appointments,
            SUM(status = 'Pending') AS total_pending,
            SUM(status = 'Missed') AS total_missed,
            SUM(status = 'Accepted') AS total_accepted,
            SUM(status = 'Rejected') AS total_rejected,
            SUM(status = 'Ongoing') AS total_ongoing,
            SUM(status = 'Completed') AS total_completed,
            SUM(status = 'Cancelled') AS total_cancelled
        ")
            ->first();

        $appointmentsToday = Appointment::whereDate('appointment_date', now())->count();
        $totalUsers = User::count();
        $totalDoctors = Employee::count();

        return response()->json([
            'totalAppointments' => $statuses->total_appointments,
            'totalPending' => $statuses->total_pending,
            'totalMissed' => $statuses->total_missed,
            'totalAccepted' => $statuses->total_accepted,
            'totalRejected' => $statuses->total_rejected,
            'totalOngoing' => $statuses->total_ongoing,
            'totalCompleted' => $statuses->total_completed,
            'totalCancelled' => $statuses->total_cancelled,
            'appointmentsToday' => $appointmentsToday,
            'totalUsers' => $totalUsers,
            'totalDoctors' => $totalDoctors,
        ], 200);
    }
    public function getLineChartData(Request $request)
    {
        $filter = $request->filter;
        $startDate = null;
        $endDate = Carbon::now()->toDateString(); // Current date as the end date

        switch ($filter) {
            case 'less_than_a_week':
                $startDate = Carbon::now()->subDays(6)->toDateString(); // Last 7 days
                break;
            case 'less_than_a_month':
                $startDate = Carbon::now()->subDays(29)->toDateString(); // Last 30 days
                break;
            case 'less_than_a_year':
                $startDate = Carbon::now()->subMonths(12)->toDateString(); // Last 12 months
                break;
            default:
                return response()->json(['error' => 'Invalid filter'], 400);
        }

        // Fetch data
        if ($filter === 'less_than_a_year') {
            $userData = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
                ->selectRaw('MONTH(appointment_date) as month, YEAR(appointment_date) as year, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
        } else {
            $userData = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
                ->selectRaw('appointment_date as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        $labels = [];
        switch ($filter) {
            case 'less_than_a_week':
                $labels = $this->generateDateRangeLabels($startDate, $endDate, 'M d'); // Format: Dec 1
                break;
            case 'less_than_a_month':
                $labels = $this->generateDateRangeLabels($startDate, $endDate, 'M d'); // Format: Dec 1
                break;
            case 'less_than_a_year':
                $labels = $this->generateDateRangeLabels($startDate, $endDate, 'M Y'); // Format: Jan 2024
                break;
        }

        // Map counts to labels
        $counts = array_fill(0, count($labels), 0);
        foreach ($userData as $data) {
            $date = $filter === 'less_than_a_year' 
                ? Carbon::create($data->year, $data->month)->format('M Y') 
                : Carbon::parse($data->date)->format('M d');
            $index = array_search($date, $labels);
            if ($index !== false) {
                $counts[$index] = $data->count;
            }
        }

        return response()->json([
            'labels' => $labels,
            'appointmentCounts' => $counts,
        ], 200);
    }

    /**
     * Generate a range of labels between two dates with a specified format.
     */
    private function generateDateRangeLabels($startDate, $endDate, $format)
    {
        $labels = [];
        $currentDate = Carbon::parse($startDate);

        while ($currentDate->lte(Carbon::parse($endDate))) {
            $labels[] = $currentDate->format($format);
            $currentDate->addDay();
        }

        return array_values(array_unique($labels));
    }

    public function getDoughnutChartData(Request $request)
    {
        $filter = $request->filter;
        $startDate = null;
        $endDate = Carbon::now()->toDateString(); // Current date as the end date

        switch ($filter) {
            case 'less_than_a_week':
                $startDate = Carbon::now()->subDays(6)->toDateString(); // Last 7 days
                break;
            case 'less_than_a_month':
                $startDate = Carbon::now()->subDays(29)->toDateString(); // Last 30 days
                break;
            case 'less_than_a_year':
                $startDate = Carbon::now()->subYear()->startOfYear()->toDateString(); // Start of the current year
                break;
            default:
                return response()->json(['error' => 'Invalid filter'], 400);
        }

        // Fetch data based on filter range
        $doughnutData = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
            ->selectRaw('
            CASE
                WHEN status IN ("Missed", "Cancelled", "Rejected") THEN "Missed/Cancelled/Rejected"
                WHEN status = "Completed" THEN "Completed"
                WHEN status IN ("Accepted", "Ongoing") THEN "Accepted/Ongoing"
                ELSE "Pending"
            END as status_group,
            COUNT(*) as count
        ')
            ->groupBy('status_group')
            ->pluck('count', 'status_group')
            ->toArray();

        // Ensure all groups are present in the response
        $defaultGroups = [
            "Missed/Cancelled/Rejected" => 0,
            "Completed" => 0,
            "Accepted/Ongoing" => 0,
            "Pending" => 0,
        ];

        $doughnutData = array_merge($defaultGroups, $doughnutData);

        return response()->json([
            'doughnutData' => $doughnutData
        ], 200);
    }
}
