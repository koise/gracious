<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gracious Smile - Appointments</title>
    @vite(['resources/scss/user/userappointment.scss', 'resources/scss/usersidebar.scss', 'resources/scss/usersidebar.scss', 'resources/scss/modal.scss', 'resources/scss/footer.scss', 'resources/js/user/records.js'])
    <style>

    </style>
</head>

<body>
    <main>
        <div class="wrapper">
            <div class="container">
                @include('partials.topbar')
                <div class="content">
                    
                    <div class="section">
                        <div class="section-header">
                            <h2>Authorization Records</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="searchInput" placeholder="Search appointments....." />
                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                <thead>
                                        <tr>
                                            <th>Type of Form</th>
                                            <th>Appointment Date</th>
                                            <th>Date Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="authorizationTableBody">
                                    </tbody>
                                </table>
                            </div>

                        </div>
                        <div id="authorizationPagination" class="pagination-controls"></div>
                    </div>
                    <div class="section">
                        <div class="section-header">
                            <h2>Orthodontic Treatment Plan</h2>
                        </div>
                        <div class="section-content">
                            <div class="table-wrapper">
                                <div class="scrollable-table">
                                    <table class="table table-sortable">
                                        <thead>
                                            <tr>
                                                    <th>Date</th>
                                                    <th>Procedure</th>
                                                    <th>Amount</th>
                                                    <th>Paid</th>
                                                    <th>Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recordTableBody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="viewModal">
        <div class="modal">
            <div class="form-header">
                <div id="view-close-modal">
                    X
                </div>
            </div>
            <div class="medical-record">
                <div class="medical-record-image">
                    <p>Medical Record Image</p>

                    <div id="img-view">
                        <img data-dz-thumbnail alt="Upload Image">

                    </div>
                </div>
                <div class="scrollable-table" id="procedure-container">
                    <p>Treatment Plan</p>

                    <table class="table table-sortable" id="procedureTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Procedure</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                            </tr>
                        </thead>

                        <tbody id="procedureTableBody">


                        </tbody>

                    </table>
                </div>

            </div>
        </div>
    </div>

    <div id="authorizationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span id="closeAuthorizationModal" class="close">&times;</span>
            <img id="authorizationImage" src="" alt="Authorization Image">
        </div>
    </div>
</body>

</html>
