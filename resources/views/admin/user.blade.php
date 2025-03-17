<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gracious Smile Admin -  Patient</title>

    @vite(['resources/scss/admin/admintable.scss', 'resources/scss/sidebar.scss', 'resources/scss/modal.scss', 'resources/scss/footer.scss', 'resources/js/sidebar.js', 'resources/js/admin/user.js'])

    <style>

    </style>
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
                                <h2>Admin | <span>Patients</span></h2>
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
                                    <button id="add-btn">Add Patient</button>
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
                                            <th>Status</th>
                                            <th>Date Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userTableBody">

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
                                            <th>Email</th>
                                            <th>Phone Number</th>
                                            <th>Status</th>
                                            <th>Date Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="deactivatedUserTableBody">

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
                <h2>Add Patient</h2>
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
                        <div class="age">
                            <input type="number" id="addAge" name="age" value="{{ old('age') }}"
                                placeholder="Age" @if ($errors->has('age')) autofocus @endif required>
                        </div>
                        <div class="phone-number">
                            <span class="input-group-addon">+63</span>
                            <input type="text" id="addNumber" name="number" value="{{ old('number') }}"
                                placeholder="Number" @if ($errors->has('number')) autofocus @endif required>
                        </div>
                    </div>
                    <div class="form-control">
                        <input type="text" id="add_street_address" name="street_address"
                            value="{{ old('street_address') }}"
                            placeholder="Street Address"@if ($errors->has('street_address')) autofocus @endif required>
                    </div>
                    <div class="form-control">
                        <div class="province">
                            <select name="province" id="addProvince" required>
                                <option value="">Select Province</option>
                                @foreach (App\Models\Province::orderBy('name')->get() as $province)
                                    <option value="{{ $province->id }}"
                                        {{ old('province') == $province->id ? 'selected' : '' }}>{{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="city">
                            <select name="city" id="addCity" required>
                                <option value="">Select City</option>
                            </select>
                        </div>
                        <div class="country">
                            <select name="country" id="addCountry" required>
                                <option value="Philippines" default>Philippines</option>
                            </select>
                        </div>
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
                <h2>Edit Patient</h2>
                <form id="updateForm" method="POST">
                    @csrf
                    <div class="form-control">
                        <ul id="update-errors" class="text-danger">

                        </ul>
                    </div>
                    <input type="hidden" name="id" id="edit-user-id" value="">
                    <div class="form-control">
                        <input name="first_name" id="updateFirstname" type="text" placeholder="First Name"
                            required>
                    </div>
                    <div class="form-control">
                        <input name="last_name" id="updateLastname" type="text" placeholder="Last Name" required>
                    </div>
                    <div class="form-control">
                        <input name="username" id="updateUsername" type="text" placeholder="Username" required>
                    </div>
                    <div class="form-control">
                        <div class="age">
                            <input type="number" id="updateAge" name="age" value="{{ old('age') }}"
                                placeholder="Age" @if ($errors->has('age')) autofocus @endif required>
                        </div>
                        <div class="phone-number">
                            <span class="input-group-addon">+63</span>
                            <input type="text" id="updateNumber" name="number" value="{{ old('number') }}"
                                placeholder="Number" @if ($errors->has('number')) autofocus @endif required>
                        </div>
                    </div>
                    <div class="form-control">
                        <input type="text" id="updateStreetAddress" name="street_address"
                            value="{{ old('street_address') }}"
                            placeholder="Street Address"@if ($errors->has('street_address')) autofocus @endif required>
                    </div>
                    <div class="form-control">
                        <div class="province">
                            <select name="province" id="updateProvince" required>
                                <option value="">Select Province</option>
                                @foreach (App\Models\Province::orderBy('name')->get() as $province)
                                    <option value="{{ $province->id }}"
                                        {{ old('province') == $province->id ? 'selected' : '' }}>{{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="city">
                            <select name="city" id="updateCity" required>
                                <option value="">Select City</option>
                            </select>
                        </div>
                        <div class="country">
                            <select name="country" id="updateCountry">
                                <option value="" default>Philippines</option>
                            </select>
                        </div>
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
                    <input type="hidden" name="id" id="deactivate-user-id" value="">
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
                    <input type="hidden" name="id" id="activate-user-id" value="">
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
        document.addEventListener("DOMContentLoaded", function() {
            /**
             * Sorts a HTML table.
             * @param {HTMLTableElement} table The table to sort
             * @param {number} column The index of the column to sort
             * @param {boolean} asc Determines if the sorting will be in ascending order
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

                // Clear and re-add sorted rows
                tBody.innerHTML = '';
                tBody.append(...sortedRows);

                // Update sort classes
                table.querySelectorAll("th").forEach(th => th.classList.remove("th-sort-asc", "th-sort-desc"));
                table.querySelector(`th:nth-child(${column + 1})`).classList.toggle("th-sort-asc", asc);
                table.querySelector(`th:nth-child(${column + 1})`).classList.toggle("th-sort-desc", !asc);
            }

            // Initialize sortable table headers
            document.querySelectorAll(".table-sortable th").forEach((headerCell, index) => {
                if (index < document.querySelectorAll(".table-sortable th").length - 1) {
                    headerCell.addEventListener("click", () => {
                        const tableElement = headerCell.closest("table");
                        const currentIsAscending = headerCell.classList.contains("th-sort-asc");
                        sortTableByColumn(tableElement, index, !currentIsAscending);
                    });
                }
            });

            /**
             * Handle province and city select changes
             * @param {HTMLElement} provinceSelect The province select element
             * @param {HTMLElement} citySelect The city select element
             */
            function handleProvinceChange(provinceSelect, citySelect) {
                provinceSelect.addEventListener("change", function() {
                    const selectedProvinceId = this.value;
                    citySelect.innerHTML = '<option value="">Select City</option>';

                    if (selectedProvinceId) {
                        fetch(`/account/cities/${selectedProvinceId}`)
                            .then(response => response.json())
                            .then(data => {
                                data.forEach(city => {
                                    const option = document.createElement("option");
                                    option.value = city.id;
                                    option.textContent = city.name;
                                    citySelect.appendChild(option);
                                });
                            })
                            .catch(error => console.error("Error fetching cities:", error));
                    }
                });
            }

            // Initialize province and city select elements
            handleProvinceChange(document.getElementById("addProvince"), document.getElementById("addCity"));
            handleProvinceChange(document.getElementById("updateProvince"), document.getElementById("updateCity"));

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
            // Initialize restricted inputs
            restrictAlphaInput(document.getElementById("addFirstname"));
            restrictAlphaInput(document.getElementById("addLastname"));
            restrictAlnumInput(document.getElementById("addUsername"));
            restrictNumericInput(document.getElementById("addAge"), 2);
            restrictNumericInput(document.getElementById("addNumber"), 11);
            restrictAlphaInput(document.getElementById("updateFirstname"));
            restrictAlphaInput(document.getElementById("updateLastname"));
            restrictAlnumInput(document.getElementById("updateUsername"));
            restrictNumericInput(document.getElementById("updateAge"), 2);
            restrictNumericInput(document.getElementById("updateNumber"), 11);
        });
    </script>

</body>

</html>
