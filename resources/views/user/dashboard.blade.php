<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gracious Smile - Appointment</title>
    <style>

    </style>
    @vite(['resources/scss/user/userappointment.scss', 
    'resources/scss/usersidebar.scss',
    'resources/scss/modal.scss', 
    'resources/scss/footer.scss', 
    'resources/js/user/appointment.js'])
</head>

<body>
    <main>
        <div class="wrapper">
            <div class="container">
                @include('partials.topbar')
                <div class="content">
                    <div class="section">
                        <div class="section-header">
                            <span>Note: Appointment that are less than 24 hrs cannot be cancelled</span>
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
                <div id="add-close-modal">X</div>
            </div>
            <div class="form-content">
                <h2>Book Appointment</h2>
                <form id="addForm" method="POST">
                    @csrf
                    <div class="form-control">
                        <ul id="add-errors" class="text-danger"></ul>
                    </div>
                    <input type="hidden" name="id" id="user-appointment-id" value="">

                    <div class="form-control">
                        <input name="date" id="date" type="date" min="" required>
                    </div>

                    <div class="form-control">
                        <select name="preference" id="preference" required>
                            <option value="">Select Schedule Preference</option>
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <select id="service">
                            <option value="">Select Service</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->service }}">{{ $service->service }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="addServiceButton">Add</button>
                    </div>

                    <div class="form-control">
                        <ul id="serviceList"></ul>
                        <input type="hidden" name="procedures" id="procedures" value="">
                    </div>

                    <div class="form-control">
                        <textarea name="remarks" id="remarks" rows="10" placeholder="Remarks"></textarea>
                    </div>

                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="cancelModal">
        <div class="modal">
            <div class="form-header">
                <div id="cancel-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <form id="cancelForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="cancel-appointment-id" value="">
                    <div class="form-control">
                        <p>Are you sure you want to cancel your appointment?</p>
                    </div>
                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


</body>

</html>
