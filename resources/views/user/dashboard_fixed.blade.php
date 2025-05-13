<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gracious Smile - Appointment</title>
    <!-- Add FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/main.min.css' rel='stylesheet' />
    <style>
        .calendar-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
            margin-bottom: 30px;
            position: relative;
        }
        .fc-event {
            cursor: pointer;
            padding: 4px;
            margin: 2px 0;
            border-radius: 4px;
            transition: transform 0.2s;
        }
        .fc-event:hover {
            transform: scale(1.02);
        }
        .fc-event-title {
            font-weight: bold;
            color: white;
        }
        .fc-event-time {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .fc-event-status {
            font-size: 0.8em;
            margin-top: 2px;
            padding: 2px 4px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.2);
        }
        .appointment-status-pending {
            background-color: #ffd700 !important;
            border-color: #ffd700 !important;
        }
        .appointment-status-accepted {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
        }
        .appointment-status-completed {
            background-color: #17a2b8 !important;
            border-color: #17a2b8 !important;
        }
        .appointment-status-cancelled {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
        }
        .appointment-status-ongoing {
            background-color: #6f42c1 !important;
            border-color: #6f42c1 !important;
        }
        .fc-toolbar-title {
            font-size: 1.5em !important;
            color: #333;
        }
        .fc-button-primary {
            background-color: #007bff !important;
            border-color: #007bff !important;
            transition: all 0.3s;
        }
        .fc-button-primary:hover {
            background-color: #0056b3 !important;
            border-color: #0056b3 !important;
            transform: translateY(-1px);
        }
        .fc-day-today {
            background-color: #f8f9fa !important;
        }
        .calendar-legend {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        .legend-title {
            font-weight: bold;
            margin-right: 10px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
        }
        .legend-label {
            font-size: 0.9em;
            color: #666;
        }
        .calendar-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        .appointment-details {
            padding: 20px;
        }
        .appointment-details h3 {
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .details-content {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
            min-width: 100px;
        }
        .detail-value {
            color: #333;
        }
        .fc-list-event:hover td {
            background-color: #f8f9fa !important;
        }
        .fc-list-event-time {
            font-weight: bold;
        }
        .fc-list-event-title a {
            color: #333;
            text-decoration: none;
        }
        .fc-list-event-title a:hover {
            color: #007bff;
        }
        .time-slots-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            margin-top: 1rem;
        }
        .time-slots-morning,
        .time-slots-afternoon {
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .time-slots-morning h4,
        .time-slots-afternoon h4 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.5rem;
        }
        .time-slot {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .time-slot:hover {
            background: #e9ecef;
            border-color: #ced4da;
            transform: translateY(-2px);
        }
        .time-slot.selected {
            background: #007bff;
            color: white;
            border-color: #0056b3;
        }
        @media (max-width: 768px) {
            .time-slots-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }
    </style>
    @vite(['resources/scss/user/userappointment.scss', 
    'resources/scss/usersidebar.scss',
    'resources/scss/modal.scss', 
    'resources/scss/footer.scss',
    'resources/scss/user/calendar-modal.scss',
    'resources/scss/modal-inline.scss',
    'resources/js/user/appointment.js'])
</head>

<body>
    <main>
        <div class="wrapper">
            <div class="container">
                @include('partials.topbar')
                <div class="content">
                    <!-- Add Calendar Section -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Appointment Calendar</h2>
                        </div>
                        <div class="calendar-container">
                            <div id="calendar"></div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-header">
                            <span>Note: Pending appointments can be cancelled at any time. Accepted or ongoing appointments cannot be cancelled within 24 hours of the scheduled time.</span>
                            <h2>Book Appointment</h2>
                        </div>
                        <div class="section-content">

                            <div class="counter">

                                <div class="progress-bar-container">
                                    <div class="progress-line" id="line"></div>
                                    <div class="progress-node" id="node1">
                                        <span>Pending</span>
                                    </div>
                                    <div class="progress-line" id="line1"></div>
                                    <div class="progress-node" id="node2">
                                        <span>Accepted</span>
                                    </div>
                                    <div class="progress-line" id="line2"></div>
                                    <div class="progress-node" id="node3">
                                        <span>Ongoing</span>
                                    </div>
                                    <div class="progress-line" id="line3"></div>
                                    <div class="progress-node" id="node4">
                                        <span>Completed</span>
                                    </div>
                                </div>

                                <div class="book-button">
                                    <button id="bookButton" style="display: none;">+ Book</button>
                                    <button id="cancelButton" style="display: none;">Cancel</button>
                                </div>
                            </div>
                            <div class="table-wrapper">
                                <div class="scrollable-table">
                                    <table class="table table-sortable">
                                        <thead>
                                            <tr>
                                                <th>Appointment Date</th>
                                                <th>Appointment Time</th>
                                                <th>Procedure</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <td><span id="dateText"></span></td>
                                            <td><span id="timeText"></span></td>
                                            <td><span id="serviceText"></span></td>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-header">
                            <span>Note: Pending appointments can be cancelled at any time. Accepted or ongoing appointments cannot be cancelled within 24 hours of the scheduled time.</span>
                            <h2>Book Appointment</h2>
                        </div>
                        <div class="section-content">

                            <div class="counter">

                                <div class="progress-bar-container">
                                    <div class="progress-line" id="line"></div>
                                    <div class="progress-node" id="node1">
                                        <span>Pending</span>
                                    </div>
                                    <div class="progress-line" id="line1"></div>
                                    <div class="progress-node" id="node2">
                                        <span>Accepted</span>
                                    </div>
                                    <div class="progress-line" id="line2"></div>
                                    <div class="progress-node" id="node3">
                                        <span>Ongoing</span>
                                    </div>
                                    <div class="progress-line" id="line3"></div>
                                    <div class="progress-node" id="node4">
                                        <span>Completed</span>
                                    </div>
                                </div>

                                <div class="book-button">
                                    <button id="bookButton" style="display: none;">+ Book</button>
                                    <button id="cancelButton" style="display: none;">Cancel</button>
                                </div>
                            </div>
                            <div class="table-wrapper">
                                <div class="scrollable-table">
                                    <table class="table table-sortable">
                                        <thead>
                                            <tr>
                                                <th>Appointment Date</th>
                                                <th>Appointment Time</th>
                                                <th>Procedure</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <td><span id="dateText"></span></td>
                                            <td><span id="timeText"></span></td>
                                            <td><span id="serviceText"></span></td>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-header">
                            <h2>Appointment History</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="searchInput" placeholder="Search appointments..." />
                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Procedure</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="appointmentListTableBody">

                                    </tbody>

                                </table>
                            </div>

                        </div>
                        <div id="appointmentPagination" class="pagination-controls"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="addModal">
        <div class="modal">
            <div class="form-header">
                <div id="add-close-modal">×</div>
            </div>
            <div class="form-content">
                <h2>Book Appointment</h2>
                <form id="addForm" method="POST" class="inline-form">
                    @csrf
                    <div class="form-control">
                        <ul id="add-errors" class="text-danger"></ul>
                    </div>
                    <input type="hidden" name="id" id="user-appointment-id" value="">

                    <div class="form-row">
                        <div class="col-6">
                            <div class="floating-label">
                                <input name="date" id="date" type="date" min="" required class="form-input">
                                <label for="date">Appointment Date</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="floating-label custom-select-wrapper">
                                <select name="preference" id="preference" required class="form-select">
                                    <option value="">Select preference</option>
                                    <option value="Morning">Morning</option>
                                    <option value="Afternoon">Afternoon</option>
                                </select>
                                <label for="preference">Time Preference</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-header-label">Selected Services</div>
                    <div class="service-selection">
                        <div class="service-input-group">
                            <div class="custom-select-wrapper">
                                <select id="service" class="form-select">
                                    <option value="">Select Service</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->service }}">{{ $service->service }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" id="addServiceButton" class="btn-add-service">Add Service</button>
                        </div>
                        <div class="selected-services">
                            <ul id="serviceList" class="service-list"></ul>
                            <input type="hidden" name="procedures" id="procedures" value="">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Additional Notes</label>
                        <textarea name="remarks" id="remarks" rows="4" placeholder="Any special requests or information you'd like us to know" class="form-textarea"></textarea>
                    </div>

                    <div class="terms-checkbox">
                        <div class="checkbox-container">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the Terms and Conditions. I understand that my appointment will be forfeited if not confirmed within 24 hours before the scheduled time.</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="cancelModal">
        <div class="modal">
            <div class="form-header">
                <div id="cancel-close-modal">×</div>
            </div>
            <div class="form-content">
                <h2>Cancel Appointment</h2>
                <form id="cancelForm" method="POST" class="inline-form">
                    @csrf
                    <input type="hidden" name="id" id="cancel-appointment-id" value="">
                    
                    <div class="appointment-info">
                        <div class="label-value-pair">
                            <span class="label">Date:</span>
                            <span class="value" id="cancel-date">-</span>
                        </div>
                        <div class="label-value-pair">
                            <span class="label">Time:</span>
                            <span class="value" id="cancel-time">-</span>
                        </div>
                        <div class="label-value-pair">
                            <span class="label">Service:</span>
                            <span class="value" id="cancel-service">-</span>
                        </div>
                    </div>
                    
                    <div class="alert-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <p>Are you sure you want to cancel your appointment? This action cannot be undone.</p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" id="cancel-close-btn">No, Keep It</button>
                        <button type="submit" class="submit-btn confirm-cancel-btn">Yes, Cancel Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


</body>

</html>

