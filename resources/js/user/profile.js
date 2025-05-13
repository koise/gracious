import axios from "axios";
import $ from 'jquery';

$(document).ready(function () {
    const url = '/user/fetch/';

    // Fetch user data and handle image logic
    function fetchUserData() {
        axios.get(url)
            .then(response => {
                console.log('User data response:', response); 
                const { status, data, image } = response.data; 
                
                if (status === 'success') {
                    const user = data;
                    // Set user data in the UI
                    $('.profile-name').text(`${user.first_name} ${user.last_name}`);
                    $('.profile-username').text(`@${user.username}`);
                    $('#phoneNum').text(user.number || 'N/A');
                    $('#age').text(user.age || 'N/A');
                    $('#address').text(user.street_address || 'N/A');
                    $('#city').text(user.city_name || 'N/A');
                    $('#province').text(user.province_name || 'N/A');
                    $('#status').text(user.status || 'Active');
                    
                    // Handle ID image display
                    handleImageDisplay(image);
                    
                    // Fetch appointment statistics
                    fetchAppointmentStats();
                    
                    // Set event listeners for action buttons
                    setupActionButtons(user);
                } else {
                    console.error('User not found');
                    showNotification('Error', 'Could not load user profile data.', 'error');
                }
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                showNotification('Error', 'Could not load user profile data.', 'error');
            });
    }

    // Function to handle image display logic
    function handleImageDisplay(image) {
        const dropzone = $('#dropzone');
        const uploadedImage = dropzone.find('.uploaded-image');
        const placeholder = dropzone.find('.placeholder-text');
        const uploadBtn = $('#uploadBtn');
        
        if (image === 'No image found' || !image) {
            uploadedImage.hide();
            placeholder.show();
            uploadBtn.show();
        } else {
            uploadedImage.attr('src', `/storage/${image.file_path}`).show();
            placeholder.hide();                       
            uploadBtn.show(); // Still show the button to allow re-uploading
        }
    }
    
    // Fetch appointment statistics
    function fetchAppointmentStats() {
        axios.get('/user/appointment/stats')
            .then(response => {
                if (response.data.status === 'success') {
                    const stats = response.data.data;
                    $('#appointmentCount').text(stats.total || 0);
                    $('#completedCount').text(stats.completed || 0);
                    $('#upcomingCount').text(stats.upcoming || 0);
                    $('#rejectedCount').text(stats.rejected || 0);
                    $('#cancelledCount').text(stats.cancelled || 0);
                }
            })
            .catch(error => {
                console.error('Error fetching appointment stats:', error);
                // Don't show notification for this to avoid cluttering the UI
            });
    }
    
    // Setup action buttons functionality
    function setupActionButtons(user) {
        // Edit Profile button
        $('.edit-profile').on('click', function() {
            // In a real implementation, this would show an edit form or navigate to an edit page
            showNotification('Coming Soon', 'Profile editing will be available soon!', 'info');
        });
        
        // Change Password button
        $('.change-password').on('click', function() {
            // In a real implementation, this would show a password change form
            showNotification('Coming Soon', 'Password change feature will be available soon!', 'info');
        });
        
        // Notification Settings button
        $('.notification-settings').on('click', function() {
            // In a real implementation, this would show notification settings
            showNotification('Coming Soon', 'Notification settings will be available soon!', 'info');
        });
        
        // Edit section button
        $('.edit-section-btn').on('click', function() {
            const section = $(this).data('section');
            showNotification('Coming Soon', `Editing ${section} information will be available soon!`, 'info');
        });
        
        // Edit cover photo button
        $('#editCoverBtn').on('click', function() {
            showNotification('Coming Soon', 'Cover photo upload will be available soon!', 'info');
        });
    }
    
    // Show notification toast
    function showNotification(title, message, type = 'success') {
        // Create toast element if it doesn't exist
        if ($('#notification-toast').length === 0) {
            $('body').append(`
                <div id="notification-toast" class="toast ${type}">
                    <div class="toast-header">
                        <strong class="toast-title"></strong>
                        <button type="button" class="close-toast">&times;</button>
                    </div>
                    <div class="toast-body"></div>
                </div>
            `);
            
            // Close toast event
            $(document).on('click', '.close-toast', function() {
                $('#notification-toast').removeClass('show');
            });
        }
        
        // Set toast content and show
        $('#notification-toast .toast-title').text(title);
        $('#notification-toast .toast-body').text(message);
        $('#notification-toast').removeClass('success info error warning').addClass(type).addClass('show');
        
        // Auto hide after 3 seconds
        setTimeout(function() {
            $('#notification-toast').removeClass('show');
        }, 3000);
    }

    // Fetch user data when page loads
    fetchUserData();

    // Handle dropzone click to trigger file input
    $('#dropzone').on('click', function(e) {
        if (e.target !== this) return; // Don't trigger if clicking on child elements
        $('#id_image').trigger('click');
    });

    // Handle file selection
    $('#id_image').on('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            handleFileUpload(file);
        }
    });
    
    // Handle drag and drop functionality
    const dropzone = document.getElementById('dropzone');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropzone.classList.add('dragover');
    }
    
    function unhighlight() {
        dropzone.classList.remove('dragover');
    }
    
    dropzone.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const file = dt.files[0];
        handleFileUpload(file);
    }

    // Upload button click handler
    $('#uploadBtn').on('click', function() {
        $('#id_image').trigger('click');
    });

    // Function to handle file upload
    function handleFileUpload(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();

            reader.onload = function(e) {
                const uploadedImg = $('.uploaded-image');
                uploadedImg.attr('src', e.target.result);
                uploadedImg.show();
                $('.placeholder-text').hide();
            };

            reader.readAsDataURL(file);

            const formData = new FormData();
            formData.append('id_image', file);

            // Show uploading state
            $('#uploadBtn').prop('disabled', true).text('Uploading...');

            // Upload the image to the server
            axios.post('/user/upload-id-image', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then(response => {
                console.log('File uploaded successfully:', response.data);
                showNotification('Success', 'ID image uploaded successfully!', 'success');
                $('#uploadBtn').prop('disabled', false).text('Upload ID');
            })
            .catch(error => {
                console.error('Error uploading file:', error);
                showNotification('Error', 'Failed to upload ID image. Please try again.', 'error');
                $('#uploadBtn').prop('disabled', false).text('Upload ID');
            });
        } else {
            showNotification('Error', 'Please upload a valid image file.', 'error');
        }
    }
});
