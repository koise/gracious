import $ from "jquery";
import axios from "axios";

document.addEventListener('DOMContentLoaded', function () {
    const url = `/user/fetch/`; // Adjust the URL if needed

    function fetchUserData() {
        axios.get(url)
            .then(response => {
                console.log(response);
                const data = response.data;
    
                if (data.status === 'success') {
                    const user = data.data;
                    const image = data.image;
                    console.log(image.file_path);
                    document.querySelector('.profile-name').textContent = `${user.first_name} ${user.last_name}`;
                    document.querySelector('.profile-username').textContent = `@${user.username}`;
                    document.querySelector('#phoneNum').textContent = user.number || 'N/A';
                    document.querySelector('#age').textContent = user.age || 'N/A';
                    document.querySelector('#address').textContent = user.street_address || 'N/A';
                    document.querySelector('#city').textContent = user.city_name || 'N/A';
                    document.querySelector('#province').textContent = user.province_name || 'N/A';
                    document.querySelector('#status').textContent = user.status || 'N/A';
                    document.querySelector('.profile-card .profile-footer button').style.display = 'block';
    
                    document.querySelector('#ageInput').value = user.age || 20;
                    document.querySelector('#addressInput').value = user.street_address || 'Cluster K';
                    document.querySelector('#cityInput').value = user.city_id || 'City Name Here';
                    document.querySelector('#provinceInput').value = user.province_id || 'Province Name Here';

                    const dropzone = document.querySelector('#dropzone');
                    var uploadedImage = dropzone.querySelector('.uploaded-image');
                    const placeholder = dropzone.querySelector('.placeholder-text');
                    const uploadBtn = document.querySelector('#dropzone + button');

                    if (image.file_path) {
                        uploadedImage.src = `/storage/${image.file_path}`;
                        uploadedImage.style.display = 'block';                        
                        placeholder.style.display = 'none';
                        uploadBtn.style.display = 'inline-block'; 
                    } else {
                        uploadedImage.style.display = 'none';
                        placeholder.style.display = 'block';
                        uploadBtn.style.display = 'inline-block'; // show if not uploaded yet
                    }

                    // If the user's number is not verified, disable the upload functionality
                    if (user.number_verified === false) {
                        document.querySelector('#dropzone').classList.add('disabled'); // Optional: style the dropzone as disabled
                        alert("Your number is not verified. You cannot upload an ID image.");
                    }
                } else {
                    console.error('User not found');
                }
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
    }
    fetchUserData();

    document.getElementById('submitBtn').addEventListener('click', function () {
        if (document.querySelector('#dropzone').classList.contains('disabled')) {
            return; // Do nothing if the dropzone is disabled
        }
    
        // Trigger the file input when the submit button is clicked
        let fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';
    
        // When file is selected, handle the upload
        fileInput.addEventListener('change', function (event) {
            const file = event.target.files[0];
    
            // Check if the file is an image
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const uploadedImg = document.querySelector('.uploaded-image');
                    uploadedImg.src = e.target.result;
                    uploadedImg.alt = 'Uploaded ID Image';
                    uploadedImg.style.display = 'block';
                
                    document.querySelector('.placeholder-text').style.display = 'none';
                    document.querySelector('#uploadBtn').style.display = 'none';
                };
                
                reader.readAsDataURL(file);
    
                // Handle the file upload via Axios
                const formData = new FormData();
                formData.append('id_image', file);
    
                axios.post('/user/upload-id-image', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                })
                .then(response => {
                    console.log('File uploaded successfully:', response.data);
                    alert("Image uploaded successfully!"); // Alert on successful upload
                })
                .catch(error => {
                    console.error('Error uploading file:', error);
                    alert('Error uploading the image. Please try again.');
                });                
            } else {
                alert('Please upload a valid image file.');
            }
        });
    
        // Trigger the file input click to open the file dialog
        fileInput.click();
    });
    
    // Check if the dropzone is empty and show the upload button
    function checkDropzone() {
        const dropzone = document.querySelector('#dropzone');
        const uploadBtn = document.createElement('button');
        uploadBtn.id = 'uploadBtn';
        uploadBtn.textContent = 'Upload ID';
        uploadBtn.style.display = 'none'; // Initially hidden

        if (dropzone.innerHTML.trim() === '') {
            dropzone.appendChild(uploadBtn); // Add the button to the dropzone
            uploadBtn.style.display = 'inline-block'; // Show the upload button if no image
        } else {
            uploadBtn.style.display = 'none'; // Hide the upload button if image exists
        }
    }
    checkDropzone();
});
