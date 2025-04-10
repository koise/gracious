<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Gracious Smile - Payment</title>
    @vite([
        'resources/scss/user/userappointment.scss', 
        'resources/scss/user/paymentmodal.scss', 
        'resources/scss/usersidebar.scss',
        'resources/scss/modal.scss', 
        'resources/scss/footer.scss', 
        'resources/js/user/appointment.js',
        'resources/js/user/payment.js'
    ])
</head>

<body>
    <main>
        <div class="wrapper">
            <div class="container">
                @include('partials.topbar')
                <div class="content">
                    <div class="section">
                        <div class="section-header">
                            <span>Note: Payment should be 50% before accepting the bookings. STRICTLY NO REFUND</span>
                            <h2>Book Appointment</h2>
                        </div>
                        <div class="section-content">
                            <div class="counter">
                                <div class="progress-bar-container">
                                    <div class="progress-line" id="line"></div>
                                    <div class="progress-node" id="node1"><span>Pending</span></div>
                                    <div class="progress-line" id="line1"></div>
                                    <div class="progress-node" id="node2"><span>Accepted</span></div>
                                    <div class="progress-line" id="line2"></div>
                                    <div class="progress-node" id="node3"><span>Ongoing</span></div>
                                    <div class="progress-line" id="line3"></div>
                                    <div class="progress-node" id="node4"><span>Completed</span></div>
                                </div>
                                <div class="book-button"></div>
                            </div>
                            <div class="table-wrapper">
                                <div class="scrollable-table">
                                    <table class="table table-sortable">
                                        <thead>
                                            <tr>
                                                <th>Procedure</th>
                                                <th>Balance</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="latest-appointment-table">
                                            <tr>
                                                <td colspan="7" class="text-center">Loading latest appointment...</td>
                                            </tr>
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
                                            <th>Procedure</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentHistory"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="appointmentPagination" class="pagination-controls"></div>
                    </div>
                </div>
            </div>
        </div>


<!-- Payment Modal -->
<div id="paymentModal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header2" style = "background-color:tranparent;">
        <h3 style="font-size: 1.5em;" class="modal-title" id="paymentModalLabel">Make Payment</h3>
      </div>
      <div class="modal-body"" >
        <div class="row">
          <div class="col-md-6">
            <div class="card mb-3">
              <div class="card-header">
                <h6>Transaction Details</h6>
              </div>
              <div class="card-body">
                <table class="table table-bordered">
                  <tr>
                    <th>Transaction ID:</th>
                    <td id="payment-transaction-id"></td>
                  </tr>
                  <tr>
                    <th>Service/Procedure:</th>
                    <td id="payment-service-name"></td>
                  </tr>
                  <tr>
                    <th>Appointment Date:</th>
                    <td id="payment-date"></td>
                  </tr>
                  <tr>
                    <th>Amount Due:</th>
                    <td id="payment-amount" class="font-weight-bold"></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <form id="paymentForm" enctype="multipart/form-data">
              <input type="hidden" id="payment-form-transaction-id" name="transaction_id">
              <input type="hidden" id="payment-form-qr-id" name="qr_id">
              
              <div class="form-group">
                <label for="qr-selection">Select Gcash to pay</label>
                <select class="form-control" id="qr-selection" required>
                  <option value=""></option>
                </select>
              </div>
              
              <div id="qr-details">
                <div class="text-center mb-3" style="text-align:center">
                  <img id="qr-image" src="" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                </div>
                <div class="alert alert-info">
                  <p class="mb-1"><strong>Account Name:</strong> <span id="qr-account-name"></span></p>
                  <p class="mb-0"><strong>Account Number:</strong> <span id="qr-account-number"></span></p>
                </div>
                <div class="form-group">
                  <label for="payment-reference">Reference Number</label>
                  <input type="text" class="form-control" id="payment-reference" name="reference_number" placeholder="Enter payment reference number" required>
                </div>
                <div class="form-group">
                    <label for="payment-paid">Enter the amount you paid</label>
                    <input type="number" class="form-control" id="payment-paid" name="paid" placeholder="Enter the amount you paid" required>
                </div>
                <button type="submit" id = "submitPaymentBtn" class="btn btn-primary btn-block submit-btn">Submit Payment</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id = "closePaymentModal" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Appointment Details Modal (No Bootstrap) -->
<div class="modal-overlay" id="appointmentDetailsModal">
  <div class="modal-container">
    <div class="modal-header">
      <h3>Appointment Details</h3>
      <button class="close-modal-btn">&times;</button>
    </div>
    
    <div class="modal-body">
      <div class="appointment-card">
        <div class="appointment-header">
          <span class="appointment-id" id="modalTransactionId"></span>
          <span class="appointment-status" id="modalStatus"></span>
        </div>
        
        <div class="appointment-content">
          <div class="appointment-info">
            <div class="info-group">
              <h4>Service Information</h4>
              <div class="info-row">
                <span class="info-label">Service:</span>
                <span class="info-value" id="modalServiceName"></span>
              </div>
            </div>
            
            <div class="info-group">
              <h4>Payment Information</h4>
              <div class="info-row">
                <span class="info-label">Balance:</span>
                <span class="info-value highlight" id="modalBalance"></span>
              </div>
              <div class="info-row">
                <span class="info-label">Recipient:</span>
                <span class="info-value" id="modalPaymentRecipient"></span>
              </div>
            </div>
            
            <div class="info-group">
              <h4>Date Information</h4>
              <div class="info-row">
                <span class="info-label">Appointment Date:</span>
                <span class="info-value" id="modalDate"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</div>


<!-- Payment History Modal -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" style = "display:none;" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow">
      
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="paymentHistoryModalLabel">Payment History</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <h6 class="text-muted">Payment Info</h6>
        <hr>
        <div class="row mb-2">
          <div class="col-md-6"><strong>Status:</strong> <span id="ph-status" class="float-end badge bg-success"></span></div>
          <div class="col-md-6"><strong>Amount Paid:</strong> <span id="ph-paid" class="float-end"></span></div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6"><strong>Reference Number:</strong> <span id="ph-ref" class="float-end"></span></div>
          <div class="col-md-6"><strong>Created At:</strong> <span id="ph-created" class="float-end"></span></div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6"><strong>Last Updated:</strong> <span id="ph-updated" class="float-end"></span></div>
        </div>

        <h6 class="text-muted mt-4">Appointment Info</h6>
        <hr>
        <div class="row mb-2">
          <div class="col-md-6"><strong>Appointment Date:</strong> <span id="ph-app-date" class="float-end"></span></div>
          <div class="col-md-6"><strong>Time Preference:</strong> <span id="ph-app-time" class="float-end"></span></div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6"><strong>Status:</strong> <span id="ph-app-status" class="float-end badge bg-secondary"></span></div>
          <div class="col-md-6"><strong>Procedure:</strong> <span id="ph-procedure" class="float-end"></span></div>
        </div>
        <div class="row mb-2">
          <div class="col-md-12"><strong>Remarks:</strong> <span id="ph-remarks" class="float-end"></span></div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
</main>
</body>




</html>
