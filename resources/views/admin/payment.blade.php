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
                            <input type="text" id="activeSearchInput" placeholder="Search patient name, reference number...">
                        </div>
                        <div class="filter-container">
                            <!-- Dropdown for Status Filter -->
                            <div class="status-filter">
                                <label for="status">Status:</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="All">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <!-- Date Range Filter -->
                            <div class="date-filter">
                                <label for="dateFrom">Date Range:</label>
                                <div class="date-inputs">
                                    <input type="date" id="dateFrom" class="form-control" placeholder="From">
                                    <span class="date-separator">to</span>
                                    <input type="date" id="dateTo" class="form-control" placeholder="To">
                                </div>
                            </div>
                            <!-- Amount Range Filter -->
                            <div class="amount-filter">
                                <label for="minAmount">Amount Range (₱):</label>
                                <div class="amount-inputs">
                                    <input type="number" id="minAmount" class="form-control" placeholder="Min" min="0">
                                    <span class="amount-separator">to</span>
                                    <input type="number" id="maxAmount" class="form-control" placeholder="Max" min="0">
                                </div>
                            </div>
                            <!-- Filter Button -->
                            <div class="filter-actions">
                                <button id="applyFilters" class="btn btn-primary">Apply Filters</button>
                                <button id="resetFilters" class="btn btn-secondary">Reset</button>
                            </div>
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
        <h5 class="modal-title" id="paymentModalLabel">Payment Details - ID: <span id="payment-id">1</span></h5>
      </div>
      <div class="modal-body">
        <form id="paymentForm">
          <div class="layout-container">
            <!-- Top row - two equal columns -->
            <div class="top-row">
              <!-- Patient ID Image -->
              <div class="top-cell">
                <div class="form-group image-container">
                  <label>Patient ID</label>
                  <img src="/storage/default-id-image.jpg" alt="Patient Image" class="img-fluid" id="patientImage">
                </div>
              </div>
              
              <!-- GCash QR Code -->
              <div class="top-cell">
                <div class="form-group image-container">
                  <label>GCash QR Code</label>
                  <img src="/default-image.jpg" alt="GCash QR Code" class="img-fluid" id="qrImage">
                </div>
              </div>
            </div>
            
            <!-- Middle row - patient information -->
            <div class="middle-row">
              <label>Patient Information</label>
              <div class="info-container">
                <input type="text" class="form-control" id="patientName" readonly>
                <input type="text" class="form-control" id="procedures" readonly>
                <input type="text" class="form-control" id="remarks" readonly>
              </div>
            </div>
            
            <!-- Bottom row - payment details -->
            <div class="bottom-row">
              <label>Payment Details</label>
              <div class="info-container">
                <div class="payment-info">
                  <input type="text" class="form-control" id="paid" readonly>
                  <input type="text" class="form-control" id="referenceNumber" readonly>
                  <input type="text" class="form-control" id="balance" readonly>
                  <div id="status" class="status-badge status-pending">Pending</div>
                </div>
                
                <div class="payment-actions">
                  <div class="form-group">
                    <label for="totalAmount">Subtotal/Total</label>
                    <input type="number" class="form-control" id="totalAmount" min="0">
                  </div>
                  
                  <div class="form-group">
                    <label for="paymentStatus">Update Payment Status</label>
                    <select id="paymentStatus" name="paymentStatus" class="form-control">
                      <option value="pending">Pending</option>
                      <option value="paid">Paid</option>
                      <option value="cancelled">Cancelled</option>
                      <option value="completed">Completed</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn payment-close-btn" data-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="savePaymentStatus">Save Changes</button>
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

<!-- Add the burger script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var burger = document.querySelector('.burger');
        if (burger) {
            burger.addEventListener('click', function() {
                document.querySelector('nav').classList.toggle('active');
                this.classList.toggle('active');
            });
        }
    });
</script>
</main>
</body>
</html>