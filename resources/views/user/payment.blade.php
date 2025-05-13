<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Gracious Dental Clinic | Payment</title>
    <style>
        /* Add CSS for Complete Payment button */
        .btn-complete {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-complete:hover {
            background-color: #218838;
        }
        
        .btn-complete:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
        }
        
        /* Additional styling for Payment Now button */
        .btn-complete.btn-pay-now {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: 10px;
        }

        .btn-complete.btn-pay-now:hover {
            background-color: #0069d9;
            transform: translateY(-2px);
        }

        .btn-complete.btn-pay-now:active {
            transform: translateY(0);
        }

        .btn-complete.btn-pay-now i {
            margin-right: 5px;
        }
        
        /* Fix action buttons in tables */
        .btn-view, .btn-pay, .btn-pay-now {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            margin: 0 2px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }
        
        .btn-view {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #ced4da;
        }
        
        .btn-view:hover {
            background-color: #e9ecef;
        }
        
        .btn-pay, .btn-pay-now {
            background-color: #007bff;
            color: white;
        }
        
        .btn-pay:hover, .btn-pay-now:hover {
            background-color: #0069d9;
        }
        
        .btn-pay-active {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .btn-complete.button-pulse,
        .btn-pay.button-pulse, 
        .btn-pay-now.button-pulse,
        .btn-view.button-pulse {
            animation: buttonPulse 0.3s ease;
        }
        
        @keyframes buttonPulse {
            0% { transform: scale(1); }
            50% { transform: scale(0.95); }
            100% { transform: scale(1); }
        }
    </style>
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
                <div class="content dashboard-layout">
                    <!-- Payment Info Banner -->
                    <div class="payment-info-banner">
                        <i class="fas fa-info-circle"></i>
                        <span>Note: A PHP 500 downpayment is required for booking. STRICTLY NO REFUND.</span>
                    </div>
                    
                    <!-- Dashboard Header -->
                    <div class="dashboard-header">
                        <h1>Payment Dashboard</h1>
                    </div>
                    
                    <!-- Latest Payment Card -->
                    <div class="dashboard-row">
                        <div class="dashboard-card latest-payment-card">
                            <div class="card-header">
                                <h2>Latest Payment</h2>
                            </div>
                            <div class="card-body">
                                <!-- Payment Status Tracker -->
                                <div class="payment-status-tracker">
                                    <div class="progress-bar-container">
                                        <div class="progress-line" id="line"></div>
                                        <div class="progress-node" id="node1"><span>Unpaid</span></div>
                                        <div class="progress-line" id="line1"></div>
                                        <div class="progress-node" id="node2"><span>Processing</span></div>
                                        <div class="progress-line" id="line2"></div>
                                        <div class="progress-node" id="node3"><span>Verified</span></div>
                                        <div class="progress-line" id="line3"></div>
                                        <div class="progress-node" id="node4"><span>Paid</span></div>
                                    </div>
                                </div>
                                
                                <!-- Latest Payment Details -->
                                <div class="latest-payment-details" id="latest-payment-details">
                                    <div class="loading-state">
                                        <div class="spinner"></div>
                      
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Procedure</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="latest-appointment-table">
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="loading-spinner"></div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment History Card -->
                    <div class="dashboard-row">
                        <div class="dashboard-card payment-history-card">
                            <div class="card-header">
                                <h2>Payment History</h2>
                                <div class="card-actions">
                                    <div class="search">
                                        <i class="fas fa-search search-icon"></i>
                                        <input type="text" id="searchInput" placeholder="Search payments..." />
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Procedure</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="paymentHistory">
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="loading-spinner"></div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="appointmentPagination" class="pagination-controls"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- Payment Modal -->
<div id="paymentModal" class="modern-modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="paymentModalLabel">Complete Payment</h3>
        <button type="button" class="close-modal" id="closePaymentModal">&times;</button>
      </div>
      <div class="modal-body">
        <!-- Payment error/success alerts -->
        <div id="payment-alerts"></div>
        
        <div class="payment-grid">
          <!-- Transaction Details Column -->
          <div class="payment-details-col">
            <div class="payment-card">
              <div class="payment-card-header">
                <h4>Transaction Details</h4>
              </div>
              <div class="payment-card-body">
                <div class="detail-row">
                  <span class="detail-label">Transaction ID:</span>
                  <span class="detail-value" id="payment-transaction-id"></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Service/Procedure:</span>
                  <span class="detail-value" id="payment-service-name"></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Appointment Date:</span>
                  <span class="detail-value" id="payment-date"></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Amount Due:</span>
                  <span class="detail-value highlight" id="payment-amount"></span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Payment Form Column -->
          <div class="payment-form-col">
            <form id="paymentForm" enctype="multipart/form-data">
              <input type="hidden" id="payment-form-transaction-id" name="transaction_id">
              <input type="hidden" id="payment-form-qr-id" name="qr_id">
              
              <div class="form-group">
                <label for="qr-selection">Select GCash Account</label>
                <div class="select-wrapper">
                  <select class="form-control" id="qr-selection" required>
                    <option value="">Choose GCash account</option>
                  </select>
                </div>
              </div>
              
              <div id="qr-details" class="qr-payment-details">
                <div class="qr-image-container">
                  <img id="qr-image" src="" alt="QR Code" class="qr-code-image">
                  <p class="scan-instruction">Scan this QR code with your GCash app</p>
                </div>
                
                <div class="payment-account-info">
                  <div class="account-detail">
                    <span class="account-label">Account Name:</span>
                    <span class="account-value" id="qr-account-name"></span>
                  </div>
                  <div class="account-detail">
                    <span class="account-label">Account Number:</span>
                    <span class="account-value" id="qr-account-number"></span>
                  </div>
                </div>
                
                <div class="payment-verification">
                  <div class="form-group">
                    <label for="payment-reference">Reference Number</label>
                    <input type="text" class="form-control" id="payment-reference" name="reference_number" placeholder="Enter GCash reference number" required>
                    <span class="input-hint">Find this in your GCash receipt</span>
                  </div>
                  
                  <div class="form-group">
                    <label for="payment-paid">Payment Amount</label>
                    <div class="amount-input-group">
                      <span class="currency-symbol">₱</span>
                      <input type="number" class="form-control" id="payment-paid" name="paid" placeholder="0.00" required>
                    </div>
                  </div>
                  
                  <button type="submit" id="submitPaymentBtn" class="btn-submit-payment">
                    <i class="fas fa-check-circle"></i> Confirm Payment
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Appointment Details Modal -->
<div class="modal-overlay" id="appointmentDetailsModal">
  <div class="modal-container">
    <div class="modal-header">
      <h3>Payment Details</h3>
      <button class="close-modal-btn">&times;</button>
    </div>
    
    <div class="modal-body">
      <div class="appointment-card">
        
        <!-- Appointment Header -->
        <div class="appointment-header">
          <div class="transaction-info">
            <span class="transaction-label">Transaction ID:</span>
            <span class="appointment-id" id="modalTransactionId"></span>
          </div>
          <span class="appointment-status" id="modalStatus"></span>
        </div>
        
        <!-- Appointment Content -->
        <div class="appointment-content">
          <div class="appointment-info">
            
            <!-- Service Information -->
            <div class="info-group">
              <h4>Service Information</h4>
              <div class="info-row">
                <span class="info-label">Procedures:</span>
                <span class="info-value" id="modalProcedures"></span>
              </div>
              <div class="info-row">
                <span class="info-label">Remarks:</span>
                <span class="info-value" id="modalRemarks"></span>
              </div>
            </div>
            
            <!-- Payment Information -->
            <div class="info-group">
              <h4>Payment Information</h4>
              <div class="info-row">
                <span class="info-label">Balance:</span>
                <span class="info-value highlight" id="modalBalance"></span>
              </div>
              <div class="info-row">
                <span class="info-label">Paid:</span>
                <span class="info-value" id="modalPaidStatus"></span>
              </div>
              <div class="info-row">
                <span class="info-label">Reference No:</span>
                <span class="info-value" id="modalReferenceNumber"></span>
              </div>
            </div>
            
            <!-- Date Information -->
            <div class="info-group">
              <h4>Date Information</h4>
              <div class="info-row">
                <span class="info-label">Appointment Date:</span>
                <span class="info-value" id="modalAppointmentDate"></span>
              </div>
              <div class="info-row">
                <span class="info-label">Time Preference:</span>
                <span class="info-value" id="modalPreference"></span>
              </div>
            </div>
            
            <!-- QR Information -->
            <div class="info-group">
              <h4>Payment Recipient</h4>
              <div class="info-row">
                <span class="info-label">GCash Account:</span>
                <span class="info-value" id="modalQrName"></span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="modal-actions">
          <button class="btn-close-details" id="closeDetailbtn">Close</button>
          <button class="btn-complete" id="modalCompleteButton" style="display:none;">Complete Payment</button>
        </div>
      </div>
    </div>
  </div>
</div>

@include('partials.footer')
</body>
</html>
