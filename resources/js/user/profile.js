import axios from "axios";
import $ from 'jquery';

$(document).ready(function () {
    const url = '/user/fetch/';

    // Fetch user data and handle image logic
    function fetchUserData() {
        axios.get(url)
            .then(response => {
                console.log(response); 
                const { status, data, image } = response.data; 
                console.log('Image Value:', image); 
                if (status === 'success') {
                    const user = data;
                    $('.profile-name').text(`${user.first_name} ${user.last_name}`);
                    $('.profile-username').text(`@${user.username}`);
                    $('#phoneNum').text(user.number || 'N/A');
                    $('#age').text(user.age || 'N/A');
                    $('#address').text(user.street_address || 'N/A');
                    $('#city').text(user.city_name || 'N/A');
                    $('#province').text(user.province_name || 'N/A');
                    $('#status').text(user.status || 'N/A');
    
                    $('.profile-card .profile-footer button').show();
                    $('#ageInput').val(user.age || '');
                    $('#addressInput').val(user.street_address || '');
                    $('#cityInput').val(user.city_id || '');
                    $('#provinceInput').val(user.province_id || '');
                    handleImageDisplay(image); 
                } else {
                    console.error('User not found');
                }
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
    }

    // Function to handle image display logic
    function handleImageDisplay(image) {
        const dropzone = $('#dropzone');
        const uploadedImage = dropzone.find('.uploaded-image');
        const placeholder = dropzone.find('.placeholder-text');
        const uploadBtn = $('#uploadBtn');
        
        if (image === 'No image found' || !image) {
            uploadedImage.hide();  // Use jQuery hide()
            placeholder.show();     // Use jQuery show()
            uploadBtn.show();       // Use jQuery show()
        } else {
            uploadedImage.attr('src', `/storage/${image.file_path}`).show();
            placeholder.hide();                       
            uploadBtn.hide();
        }
    }

    // Fetch user data when page loads
    fetchUserData();

    // Handle image upload on button click
    $('#uploadBtn').on('click', function () {
        const fileInput = $('#id_image'); // Use the hidden file input directly
        fileInput.trigger('click'); // Trigger file input click
    });

    // Handle file selection
    $('#id_image').on('change', function (event) {
        const file = event.target.files[0];
        handleFileUpload(file); // Call the new function to handle file upload
    });

    // Function to handle file upload
    function handleFileUpload(file) {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();

            reader.onload = function (e) {
                const uploadedImg = $('.uploaded-image');
                uploadedImg.attr('src', e.target.result);
                uploadedImg.attr('alt', 'Uploaded ID Image');
                uploadedImg.show();

                $('.placeholder-text').hide();
                $('#uploadBtn').hide();
            };

            reader.readAsDataURL(file);

            const formData = new FormData();
            formData.append('id_image', file);

            // Upload the image to the server
            axios.post('/user/upload-id-image', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            })
            .then(response => {
                console.log('File uploaded successfully:', response.data);
                alert("Image uploaded successfully!");
            })
            .catch(error => {
                console.error('Error uploading file:', error);
                alert('Error uploading the image. Please try again.');
            });
        } else {
            alert('Please upload a valid image file.');
        }
    }
});
