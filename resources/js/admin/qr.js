import $ from "jquery";
import axios from "axios";

axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

$(document).ready(function () {
    // Function to format date as "Month Day, Year"
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { month: 'long', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    function toggleQRStatus(qrId, newStatus) {
        axios.post('/qr/status', {
            qrID: qrId,  // QR ID being passed
            status: newStatus  // The status to set (inactive/active)
        })
        .then(response => {
            console.log(response.data.message);  // Success message
            alert(`QR status changed to ${newStatus}`);  // Alert the user of the status change
            // Refresh both tables after toggling status
            populateQRTable();
            populateInactiveQRTable();
        })
        .catch(error => {
            console.error("Error updating status:", error);
            alert('Failed to update status');
        });
    }
    
    
    // Fetch QR data and populate the table
    function populateQRTable(page = 1, search = '') {
        axios.get(`/admin/qr/populate?page=${page}&search=${search}`)
            .then((response) => {
                let qrData = response.data.data;
                let tableBody = $('#userTableBody');
                tableBody.empty(); // Clear the table body before adding new data

                // Loop through the data and append to the table, but only if status is 'active'
                qrData.forEach(qr => {
                    if (qr.status === 'active') { // Only process QR with 'active' status
                        let formattedDate = formatDate(qr.created_at); // Format the date

                        tableBody.append(`
                            <tr>
                                <td>${qr.id}</td>
                                <td>${qr.name}</td>
                                <td>${qr.gcash_name}</td>
                                <td>${formattedDate}</td> <!-- Use the formatted date -->
                                <td><img src="/${qr.image_path}" alt="QR Image" width="50" height="50"></td>
                               <td>
                                    <button class="view-btn" data-id="${qr.id}">View</button>
                                    <button class="deactivate-btn" data-id="${qr.id}">Deactivate</button>
                                </td>
                            </tr>
                        `);
                        console.log(qr.image_path);
                    }
                });

                // Handle pagination
                let paginationControls = $('#activePagination');
                paginationControls.empty();
                for (let i = 1; i <= response.data.last_page; i++) {
                    paginationControls.append(`
                        <button class="page-btn active-page-btn" data-page="${i}">${i}</button>
                    `);
                }
            })
            .catch((error) => {
                console.error("Error fetching QR data:", error);

                // Check if the error response exists and log it
                if (error.response) {
                    console.log('Error Response Data:', error.response.data); 
                    console.log('Error Status:', error.response.status); 
                    console.log('Error Headers:', error.response.headers); 
                } else if (error.request) {
                    console.log('No Response:', error.request);
                } else {
                    console.log('Axios Error:', error.message);
                }
            });
    }

    // Initial load of QR data
    populateQRTable();

    // Fetch QR data and populate the inactive QR table
    function populateInactiveQRTable(page = 1, search = '') {
        axios.get(`/admin/qr/populate?page=${page}&search=${search}`)
            .then((response) => {
                let qrData = response.data.data;
                let tableBody = $('#inactiveQRTableBody');
                tableBody.empty();

                qrData.forEach(qr => {
                    if (qr.status === 'inactive') { // Only process QR with 'inactive' status
                        let formattedDate = formatDate(qr.created_at); // Format the date
                        tableBody.append(`
                            <tr>
                                <td>${qr.id}</td>
                                <td>${qr.name}</td>
                                <td>${qr.gcash_name}</td>
                                <td>${formattedDate}</td>
                                <td>
                                    <button class="view-btn" data-id="${qr.id}">View</button>
                                    <button class="activate-btn" data-id="${qr.id}">Activate</button>
                                </td>
                            </tr>
                        `);
                    }
                });

                // Pagination controls
                let paginationControls = $('#inactivePagination');
                paginationControls.empty();
                for (let i = 1; i <= response.data.last_page; i++) {
                    paginationControls.append(`
                        <button class="page-btn inactive-page-btn" data-page="${i}">${i}</button>
                    `);
                }
            })
            .catch((error) => {
                console.error("Error fetching QR data:", error);
                if (error.response) {
                    console.log('Error Response Data:', error.response.data); 
                    console.log('Error Status:', error.response.status); 
                    console.log('Error Headers:', error.response.headers); 
                } else if (error.request) {
                    console.log('No Response:', error.request);
                } else {
                    console.log('Axios Error:', error.message);
                }
            });
    }

    // Initial load of inactive QR data
    populateInactiveQRTable();

    // Handle search input for active QRs
    $('#activeSearchInput').on('keyup', function () {
        let searchValue = $(this).val();
        populateQRTable(1, searchValue); // Always reset to page 1 when searching
    });

    // Handle search input for inactive QRs
    $('#inactiveSearchInput').on('keyup', function () {
        let searchValue = $(this).val();
        populateInactiveQRTable(1, searchValue); 
    });

    // Handle pagination for active QRs
    $(document).on('click', '.active-page-btn', function () {
        let page = $(this).data('page');
        let searchValue = $('#activeSearchInput').val();
        populateQRTable(page, searchValue);
    });

    // Handle pagination for inactive QRs
    $(document).on('click', '.inactive-page-btn', function () {
        let page = $(this).data('page');
        let searchValue = $('#inactiveSearchInput').val();
        populateInactiveQRTable(page, searchValue);
    });

    // Handle deactivate button click - delegate to document level
    $(document).on('click', '.deactivate-btn', function() {
        let qrId = $(this).data('id');
        $('#deactivate-gcash-id').val(qrId);  // Store the QR ID in the hidden input for use in modal
        $('#deactivateModal').css('display', 'flex');  // Show the deactivation modal
    });

    // Handle activate button click - delegate to document level
    $(document).on('click', '.activate-btn', function() {
        let qrId = $(this).data('id');
        $('#deactivate-gcash-id').val(qrId); // Set QR ID to hidden input
        $('#activateModal').css('display', 'flex');
    });

    // Deactivation Button Inside Modal (Handle the action in the modal)
    $('#deactivate-qr-button').click(function() {
        var qrId = $('#deactivate-gcash-id').val();  // Get the QR ID from hidden input
        toggleQRStatus(qrId, 'inactive');  // Call function to change status to inactive
        $('#deactivateModal').css('display', 'none');  // Close the modal after deactivation
    });

    // Activation Button Inside Modal
    $('#activate-qr-button').click(function() {
        var qrId = $('#deactivate-gcash-id').val(); // Get the QR ID from hidden input
        toggleQRStatus(qrId, 'active'); // Pass QR ID and status to toggle function
        $('#activateModal').css('display', 'none'); // Close modal after activation
    });

    // Event delegation for view button click
    $(document).on('click', '.view-btn', function () {
        console.log('View button clicked');
        
        // Get QR ID
        let qrId = $(this).data('id');
        console.log('QR ID:', qrId);

        // Make AJAX request to fetch QR code details
        $.ajax({
            url: `qr/${qrId}`,  // Ensure this route is correct and accessible
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    console.log(response);
                    let qrCode = response.qrCode;

                    // Populate modal fields with fetched QR code data
                    $('#updateQRName').val(qrCode.name);
                    $('#updateGCashName').val(qrCode.gcash_name);
                    $('#updateGCashNumber').val(qrCode.number);
                    $('#updateQRId').val(qrCode.id); // Make sure to set the QR ID for update form

                    // Check if there's an image path, then set the preview
                    if (qrCode.image_path) {
                        $('#updateImagePreview').attr('src', `/${qrCode.image_path}`).show();
                        $('#updateImagePreviewContainer').show();
                    } else {
                        $('#updateImagePreviewContainer').hide(); // Hide if no image
                    }

                    // Show the update modal
                    $('#updateModal').show();
                } else {
                    console.log('QR code not found');
                }
            },
            error: function (xhr, status, error) {
                console.log('AJAX error:', error);
                console.log('Status:', status);
                console.log('Response:', xhr.responseText); // Log full error response
            }
        });
    });

    // Handle image preview for update form
    $(document).on('change', '#updateImage', function (event) {
        let reader = new FileReader();
        reader.onload = function (e) {
            $('#updateImagePreview').attr('src', e.target.result).show();
            $('#updateImagePreviewContainer').show();
        };
        reader.readAsDataURL(event.target.files[0]);
    });

    // Close the modal when clicking the close button
    $('body').on('click', '#update-close-modal', function () {
        $('#updateModal').hide();
    });

    // Close the modal if clicked outside
    $(window).on('click', function (event) {
        if ($(event.target).is('#updateModal')) {
            $('#updateModal').hide();
        }
    });

    // Submit the update form (QR code details)
    $('#updateQRForm').on('submit', function (event) {
        event.preventDefault();
        
        let qrId = $('#updateQRId').val();
        let formData = new FormData(this);
        
        // Append modal input values
        formData.append('name', $('#updateQRName').val());
        formData.append('gcash_name', $('#updateGCashName').val());
        formData.append('number', $('#updateGCashNumber').val());

        // If an image was selected, append it to the form data
        if ($('#updateImage')[0].files.length > 0) {
            formData.append('image', $('#updateImage')[0].files[0]);
        }

        // Ensure CSRF token is included
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        // Send update request
        axios.post(`/qr/update/${qrId}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .then((response) => {
            console.log("Success:", response.data);
            alert('QR Code updated successfully!');
            $('#updateModal').hide();
            populateQRTable();
            populateInactiveQRTable();
        })
        .catch((error) => {
            console.error("Error caught:", error);
            if (error.response) {
                console.log('Error Response Data:', error.response.data);
            } else if (error.request) {
                console.log('No Response:', error.request);
            } else {
                console.log('Axios Error:', error.message);
            }
        });
    });

    // Handle QR form submission
    $('#addQRForm').on('submit', function (event) {
        event.preventDefault(); // Prevent default form submission

        let formData = new FormData(this);

        // Send the form data to the backend via Axios
        axios.post('/admin/qr/store', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .then((response) => {
            console.log("Success:", response.data);
            alert('QR Code saved successfully!');
            $('#addQRModal').css('display', 'hide');
            populateQRTable(); 
        })
        .catch((error) => {
            console.error("Error caught:", error);
            if (error.response) {
                console.log('Error Response Data:', error.response.data); 
                console.log('Error Status:', error.response.status); 
            } else if (error.request) {
                console.log('No Response:', error.request);
            } else {
                console.log('Axios Error:', error.message);
            }
        });
    });
});