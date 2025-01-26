<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees</title>

    @vite([
        // Partials
        'resources/scss/sidebar.scss',
        'resources/scss/footer.scss',
        'resources/scss/modal.scss',
        'resources/js/sidebar.js',
    
        // Employee
        'resources/scss/admin/admintable.scss',
        'resources/js/admin/employee.js',
    ])
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
                                <h2>Admin | <span>Employees</span></h2>
                            </div>
                            <div class="profile">

                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-content-header">
                            <h2>Activated Accounts</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="activeSearchInput" placeholder="Search">
                                </div>
                                <div class="button">
                                    <button id="add-btn">Add Employee</button>
                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Phone Number</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Date Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="employeeTableBody">

                                    </tbody>

                                </table>
                            </div>
                        </div>
                        <div id="activePagination" class="pagination-controls"></div>
                    </div>
                    <div class="section">
                        <div class="section-content-header">
                            <h2>Deactivated Accounts</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="deactiveSearchInput" placeholder="Search">
                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Phone Number</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Date Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="deactivatedEmployeeTableBody">

                                    </tbody>

                                </table>
                            </div>
                        </div>
                        <div id="deactivatedPagination" class="pagination-controls"></div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <div id="addModal">
        <div class="modal">
            <div class="form-header">
                <div id="add-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <h2>Add Employee</h2>
                <form id="addForm" method="POST">
                    @csrf
                    <div class="form-control">
                        <ul id="validation-errors" class="text-danger">

                        </ul>
                    </div>
                    <div class="form-control">
                        <input name="first_name" id="addFirstname" type="text" placeholder="First Name" required>
                    </div>
                    <div class="form-control">
                        <input name="last_name" id="addLastname" type="text" placeholder="Last Name" required>
                    </div>
                    <div class="form-control">
                        <input name="username" id="addUsername" type="text" placeholder="Username" required>
                    </div>
                    <div class="form-control">
                        <input name="number" id="addNumber" type="text" placeholder="Phone Number" required>
                    </div>
                    <div class="form-control">
                        <select name="role" id="addRole" required>
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Doctor">Doctor</option>
                            <option value="Staff">Staff</option>
                        </select>
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
                <h2>Edit Employee</h2>
                <form id="updateForm" method="POST">
                    @csrf

                    <div class="form-control">
                        <ul id="update-errors" class="text-danger">

                        </ul>
                    </div>
                    <input type="hidden" name="id" id="edit-employee-id" value="">
                    <div class="form-control">
                        <input name="first_name" id="updateFirstname" type="text" placeholder="First Name" required>
                    </div>
                    <div class="form-control">
                        <input name="last_name" id="updateLastname" type="text" placeholder="Last Name" required>
                    </div>
                    <div class="form-control">
                        <input name="username" id="updateUsername" type="text" placeholder="Username" required>
                    </div>
                    <div class="form-control">
                        <input name="number" id="updateNumber" type="text" placeholder="Phone Number" required>
                    </div>
                    <div class="form-control">
                        <select name="role" id="updateRole" required>
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Doctor">Doctor</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deactivateModal">
        <div class="modal">
            <div class="form-header">
                <div id="deactivate-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <form id="deactivateForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="deactivate-employee-id" value="">
                    <div class="form-control">
                        <p>Are you sure you want to deactivate this account?</p>
                    </div>
                    <div class="form-control">
                        <p id="deactivate-username"></p>
                    </div>
                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="activateModal">
        <div class="modal">
            <div class="form-header">
                <div id="activate-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <form id="activateForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="activate-employee-id" value="">
                    <div class="form-control">
                        <p>Are you sure you want to activate this account?</p>
                    </div>
                    <div class="form-control">
                        <p id="activate-username"></p>
                    </div>
                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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

        /**
             * Restrict input to numeric and limit digits
             * @param {HTMLElement} inputElement The input element to restrict
             * @param {number} maxDigits The maximum number of digits allowed
             */
            function restrictNumericInput(inputElement, maxDigits) {
                inputElement.addEventListener("input", function() {
                    this.value = this.value.replace(/\D/g, "").slice(0, maxDigits);
                });
            }

            function restrictAlphaInput(inputElement) {
                inputElement.addEventListener("input", function() {
                    this.value = this.value.replace(/[^a-zA-Z]/g, '');
                });
            }

            function restrictAlnumInput(inputElement) {
                inputElement.addEventListener("input", function() {
                    this.value = this.value.replace(/[^a-zA-Z0-9_@.]/g, '');
                });
            }

            $(document).ready(function() {
                restrictAlphaInput($("#addFirstname")[0]);
                restrictAlphaInput($("#addLastname")[0]);
                restrictAlnumInput($("#addUsername")[0]);
                restrictNumericInput(document.getElementById("addNumber"), 11);
                restrictAlphaInput($("#updateFirstname")[0]);
                restrictAlphaInput($("#updateLastname")[0]);
                restrictAlnumInput($("#updateUsername")[0]);
                restrictNumericInput(document.getElementById("updateNumber"), 11);
            });
    </script>
</body>

</html>
