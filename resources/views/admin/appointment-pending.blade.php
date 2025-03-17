<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <title>Gracious Smile Admin - Appointments</title>

    @vite(['resources/scss/admin/admintable.scss', 'resources/scss/sidebar.scss', 'resources/scss/footer.scss', 'resources/js/sidebar.js', 'resources/scss/modal.scss', 'resources/js/admin/pendingappointment.js'])
</head>

<body>
    <main>
        <div class="wrapper">
            <div class="container">
                @include('partials.sidebar')
                <div class="content">
                    <div class="section">
                        <div class="section-header">
                            <div class="appointment-header">
                                <h2>Admin | <span>Appointments</span></h2>
                            </div>
                            <div class="profile">

                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-content-header">
                            <h2>Pending Appointments</h2>
                            <span>Filter Date: <input type="date" id="filterDate" name="filterDate"></span>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" placeholder="Search">
                                </div>
                                <div class="date">

                                </div>

                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient Name</th>
                                            <th>Appointment Date</th>
                                            <th>Preference</th>
                                            <th>Status</th>
                                            <th>Service</th>
                                            <th>Remarks</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pendingAppointmentsTableBody">

                                    </tbody>

                                </table>
                            </div>
                        </div>
                        <div id="pendingAppointmentsPagination" class="pagination-controls"></div>
                    </div>
                    <div class="section">
                        <div class="section-content-header">
                            <h2>Scheduled Appointments</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="printButton">
                                    <button id="generate-btn">Generate Schedule</button>
                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable" id="scheduleTable">
                                    <thead>
                                        <tr>
                                            <th colspan="10" id="date"></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5">Morning</th>
                                            <th colspan="5">Afternoon</th>
                                        </tr>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Time</th>
                                            <th>Procedure</th>
                                            <th>Status</th>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Time</th>
                                            <th>Procedure</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="scheduleAppointmentsTableBody">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
    </main>

    <div id="acceptModal">
        <div class="modal">
            <div class="form-header">
                <div id="accept-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <form id="acceptForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="accept-appointment-id" value="" required>
                    <div class="form-control">
                        <span id="time-validation-message" class="validation-message"></span>
                    </div>

                    <div class="form-control">
                        <p>Would you like to accept this appointment?</p>
                    </div>
                    <div class="form-control">
                        <p>Preference: <span id="preference"></span></p>
                    </div>
                    <div class="form-control">
                        <p>Time Input: <span id="timeRange"></span></p>
                    </div>
                    <div class="form-control">
                        <input type="time" name="time" id="time" required>
                    </div>

                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="rejectModal">
        <div class="modal">
            <div class="form-header">
                <div id="reject-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <form id="rejectForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="reject-appointment-id" value="" required>
                    <div class="form-control">
                        <p>Would you like to reject this appointment?</p>
                    </div>
                    <div class="form-control">
                        <p>Reason for rejecting (Optional)</p>
                    </div>
                    <div class="form-control">
                        <textarea name="reason" id="reason" cols="10" rows="4"></textarea>
                    </div>
                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>

    <script>
        /**
         * Sorts a HTML table.
         *
         * @param {HTMLTableElement} table The table to sort
         * @param {number} column The index of the column to sort
         * @param {boolean} asc Determines if the sorting will be in ascending
         */
        function sortTableByColumn(table, column, asc = true) {
            const dirModifier = asc ? 1 : -1;
            const tBody = table.tBodies[0];
            const rows = Array.from(tBody.querySelectorAll("tr"));

            // Sort each row
            const sortedRows = rows.sort((a, b) => {
                let aColText = a.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
                let bColText = b.querySelector(`td:nth-child(${column + 1})`).textContent.trim();

                // If both are numbers, convert them to integers before comparing
                if (!isNaN(aColText) && !isNaN(bColText)) {
                    aColText = parseInt(aColText);
                    bColText = parseInt(bColText);
                }

                return aColText > bColText ? (1 * dirModifier) : (-1 * dirModifier);
            });

            // Remove all existing TRs from the table
            while (tBody.firstChild) {
                tBody.removeChild(tBody.firstChild);
            }

            // Re-add the newly sorted rows
            tBody.append(...sortedRows);

            // Remember how the column is currently sorted
            table.querySelectorAll("th").forEach(th => th.classList.remove("th-sort-asc", "th-sort-desc"));
            table.querySelector(`th:nth-child(${column + 1})`).classList.toggle("th-sort-asc", asc);
            table.querySelector(`th:nth-child(${column + 1})`).classList.toggle("th-sort-desc", !asc);
        }

        // Add click listeners to the table headers (except for the 'Action' column)
        document.querySelectorAll(".table-sortable th").forEach((headerCell, index) => {
            // Exclude sorting for the 'Action' column (e.g., the last column)
            if (index < document.querySelectorAll(".table-sortable th").length - 1) {
                headerCell.addEventListener("click", () => {
                    const tableElement = headerCell.parentElement.parentElement.parentElement;
                    const headerIndex = Array.prototype.indexOf.call(headerCell.parentElement.children,
                        headerCell);
                    const currentIsAscending = headerCell.classList.contains("th-sort-asc");

                    sortTableByColumn(tableElement, headerIndex, !currentIsAscending);
                });
            }
        });
    </script>
</body>

</html>
