import $ from "jquery";
import axios from "axios";

$(document).ready(function () {
    // Function to format date as "Month Day, Year"
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { month: 'long', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString('en-US', options);
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
                                <td><button class="view-btn" data-id="${qr.id}">View</button>
                                    <button class="deactivate-btn" data-id="${qr.id}">Deactivate</button></td>
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
                        <button class="page-btn" data-page="${i}">${i}</button>
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

        // Event delegation: Listen for click events on the body and handle view-btn clicks
    $('body').on('click', '.view-btn', function () {
        console.log('View button clicked');
        
        // Get QR ID
        let qrId = $(this).data('id');
        console.log('QR ID:', qrId); // You can use this ID to fetch data for the modal

        // Show modal logic
        let modal = $('#updateModal');
        if (modal) {
            modal.show(); // Show the modal
        } else {
            console.log("Modal not found");
        }
    });

    // Handle search input change
    $('#activeSearchInput').on('keyup', function () {
        let searchValue = $(this).val();
        populateQRTable(1, searchValue); // Always reset to page 1 when searching
    });

    // Handle pagination button click
    $(document).on('click', '.page-btn', function () {
        let page = $(this).data('page');
        let searchValue = $('#activeSearchInput').val();
        populateQRTable(page, searchValue); // Pass search term along with the page number
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
            // Optionally, close the modal and refresh the table
            $('#addQRModal').modal('hide');
            populateQRTable(); // Refresh the table after adding a QR code
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
                                <td>${formattedDate}</td> <!-- Use the formatted date -->
                                <td><button class="view-btn" data-id="${qr.id}">View</button>
                                <button class="activate-btn" data-id="${qr.id}">Activate</button></td>
                            </tr>
                        `);
                    }
                });

                // Handle pagination
                let paginationControls = $('#inactivePagination');
                paginationControls.empty();
                for (let i = 1; i <= response.data.last_page; i++) {
                    paginationControls.append(`
                        <button class="page-btn" data-page="${i}">${i}</button>
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
    //INACTIVE
    populateInactiveQRTable();
    $('#inactiveSearchInput').on('keyup', function () {
        let searchValue = $(this).val();
        populateInactiveQRTable(1, searchValue); 
    });

    $(document).on('click', '.page-btn', function () {
        let page = $(this).data('page');
        let searchValue = $('#inactiveSearchInput').val();
        populateInactiveQRTable(page, searchValue); 
    });

    $(document).ready(function () {
        // Event delegation for view button click
        $('body').on('click', '.view-btn', function () {
            console.log('View button clicked');
            
            // Get QR ID from the data attribute
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
        populateQRTable();  // Function to update the QR code table
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

    $(document).on('change', '#updateImage', function (event) {
        let reader = new FileReader();
        reader.onload = function (e) {
            $('#updateImagePreview').attr('src', e.target.result).show();
            $('#updateImagePreviewContainer').show();
        };
        reader.readAsDataURL(event.target.files[0]);
    });

        // Pagination handling for active QR codes
    $(document).on('click', '.page-btn', function () {
        let page = $(this).data('page');
        let searchValue = $('#activeSearchInput').val();
        populateQRTable(page, searchValue);
    });

    $('body').on('click', '.deactivate-btn', function () {
        let qrId = $(this).data('id');
        $('#deactivate-gcash-id').val(qrId);
        $('#deactivateModal').show();
    });
    
    // Close deactivate modal
    $('#deactivate-close-modal').on('click', function () {
        $('#deactivateModal').hide();
    });

    $('body').on('click', '.activate-btn', function () {
        let qrId = $(this).data('id');
        $('#activate-gcash-id').val(qrId);
        $('#activateModal').show();
    });
    
    // Close activate modal
    $('#activate-close-modal').on('click', function () {
        $('#activateModal').hide();
    });
    
    

    
    

});
