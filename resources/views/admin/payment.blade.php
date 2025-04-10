<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gracious Smile Admin - Payment</title>
    @vite(['resources/scss/admin/admintable.scss', 
           'resources/js/admin/payment.js', 
           'resources/scss/sidebar.scss',
           'resources/scss/footer.scss', 
           'resources/js/sidebar.js',
           'resources/scss/admin/adminpayment.scss',
           'resources/scss/admin/adminmodal.scss'])
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
                        <!-- Dropdown for Status Filter -->
                        <div class="status-filter">
                            <label for="status">Filter by Status:</label>
                            <select id="status" name="status">
                                <option value="All">All</option>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="scrollable-table">
                        <table class="table table-sortable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Appointment Services</th>
                                    <th>Patient Name</th>
                                    <th>Paid</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>QR Gcash</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                                <th colspan="7" style="font-size:17px;">Fetching QR Details</th>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="activePagination" class="pagination-controls"></div>
            </div>
        </div>
    </div>
</div>

<!-- Payment View Modal -->
<div class="modal" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalLabel">Payment Details</h5>
      </div>
      <div class="modal-body">
        <form id="paymentForm">
          <div class="form-group" style = "text-align:center;">
            <label for="patientImage">Patient ID</label><br>
            <img src="public/qr_images/67e713b2d5b6d.png" alt="Patient Image" class="img-fluid" id="patientImage" style="width: 200px; height: auto;">
          </div>

            <!-- Patient Details -->
        <div class="form-group">
            <label for="patientName">Patient Name</label>
            <span id="patientName">Edgar Dollentas</span>
            <p id="paid">Edgar Dollentas</span>
            <p id="referenceNumber"></span>
            <p id="appointmentStatus"></span>
            <p id="procedures"></span>
            <p id="remarks"></span>
          </div>
          
          <!-- Subtotal/Total -->
          <div class="form-group">
            <label for="totalAmount">Subtotal/Total</label>
            <input type="number" class="form-control" id="totalAmount"  min="0">
          </div>

          <!-- Payment Status Dropdown -->
          <div class="form-group">
            <label for="paymentStatus">Payment Status</label>
            <select id="paymentStatus" name="paymentStatus" class="form-control">
              <option value="pending">Pending</option>
              <option value="paid">Paid</option>
              <option value="cancelled">Cancelled</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="text-align:center">
        <button type="button" class="btn payment-close-btn" data-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="savePaymentStatus">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirm Payment Status Update</h5>
            </div>
            <div class="modal-body">
                <p id="confirmationMessage">Are you sure you want to update the payment status?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange">Confirm</button>
            </div>
        </div>
    </div>
</div>


    </main>
</body>
</html>