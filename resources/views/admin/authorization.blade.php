<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <title>Authorization</title>

    @vite(['resources/scss/admin/admintable.scss', 'resources/scss/sidebar.scss', 'resources/scss/footer.scss', 'resources/js/sidebar.js', 'resources/scss/modal.scss', 'resources/js/admin/authorization.js'])
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
                                <h2>Admin | <span>Records</span></h2>
                            </div>
                            <div class="profile">

                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-content-header">
                            <h2>Patient List</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="searchInput" placeholder="Search">
                                </div>
                                <div class="button">

                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                    <thead>
                                        <tr>
                                            <th>View</th>
                                            <th>ID</th>
                                            <th>Patient Name</th>
                                            <th>Username</th>
                                            <th>Contact Number</th>
                                            <th>Add Record</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userTableBody">

                                    </tbody>

                                </table>
                            </div>
                        </div>
                        <div id="userPagination" class="pagination-controls"></div>
                    </div>
                    <div class="section">
                        <div class="section-content-header">
                            <h2>Records</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="searchRecord" placeholder="Search">
                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient ID</th>
                                            <th>Type of Form</th>
                                            <th>Appointment Date</th>
                                            <th>Date Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recordTableBody">

                                    </tbody>

                                </table>
                            </div>
                        </div>
                        <div id="recordPagination" class="pagination-controls"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <div id="imageModal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <div id="addModal">
        <div class="modal">
            <div class="form-header">
                <div id="add-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <h2>Add Record</h2>
                <form id="addForm" method="POST">
                    @csrf
                    <div class="form-control">
                        <ul id="validation-errors" class="text-danger">

                        </ul>
                    </div>
                    <input type="hidden" name="id" id="add-authorization-id" value="">
                    <div class="form-control">
                        <p id="add-authorization-name"></p>
                    </div>
                    <div class="form-control">
                        <select name="type" id="type" required>
                            <option value="" selected disabled>Select Type of Record</option>
                            <option value="Medical Record">Medical Record Form</option>
                            <option value="Authorization">Authorization Form</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <select name="appointment_date" id="appointment_date" required>
                            <option value="" selected disabled>Select Appointment</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="dropzone" id="drop-area">
                            <div id="img-view">
                                <img data-dz-thumbnail src="{{ asset('images/upload.png') }}" alt="Upload Image">
                                <p>Drag and drop or click here <br>to upload image</p>
                            </div>
                        </label>
                    </div>
                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="updateModal">
        <div class="modal">
            <div class="form-header">
                <div id="update-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <h2>Edit Record</h2>
                <form id="updateForm" method="POST">
                    @csrf
                    <div class="form-control">
                        <ul id="validation-errors" class="text-danger">

                        </ul>
                    </div>
                    <input type="hidden" name="id" id="update-authorization-id" value="">
                    <div class="form-control">
                        <p id="update-authorization-name"></p>
                    </div>
                    <div class="form-control">
                        <select name="type" id="update-type" required>
                            <option value="" selected disabled>Select Type of Record</option>
                            <option value="Medical Record">Medical Record Form</option>
                            <option value="Authorization">Authorization Form</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <select name="appointment_date" id="update-appointment-date" required>
                            <option value="" selected disabled>Select Appointment</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="dropzone" id="drop-area">
                            <div id="img-view">
                                <img data-dz-thumbnail src="{{ asset('images/upload.png') }}" alt="Upload Image">
                                <p>Drag and drop or click here <br>to upload image</p>
                            </div>
                        </label>
                    </div>
                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
