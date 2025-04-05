<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gracious Smile Admin - Payment</title>
    @vite(['resources/scss/admin/admintable.scss', 'resources/scss/sidebar.scss', 'resources/scss/footer.scss', 'resources/js/sidebar.js', 'resources/scss/admin/adminmodal.scss'])
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
                                <h2>Admin | <span>Payment</span></h2>
                            </div>
                            <div class="profile">

                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-content-header">
                            <h2>Payment</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="activeSearchInput" placeholder="Search">
                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Appointment Services</th>
                                            <th>Paid</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>QR Gcash</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userTableBody">
                                        <th colspan="7" style ="font-size:17px;">Fetching QR Details</th>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="activePagination" class="pagination-controls"></div>
                    </div>
    </main>
</body>
</html>