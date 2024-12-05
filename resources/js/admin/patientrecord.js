import axios from 'axios';


document.addEventListener('DOMContentLoaded', function () {

    function fetchRecords() {
        axios.post('/admin/patient/record/populate')
            .then(response => {
                const records = response.data;
                const tableBody = document.getElementById('patientRecordTableBody');
                tableBody.innerHTML = '';
                records.forEach(record => {
                    const createdAt = new Date(record.created_at).toISOString().split('T')[0];
                    const row = `
                        <tr>
                            <td>${record.id}</td>
                            <td>${record.name}</td>
                            <td>${createdAt}</td>
                            <td>
                                <button id="edit-btn-${record.id}" data-id="${record.id}" class="edit-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                        <path d="M200-200h57l391-391-57-57-391 391v57Zm-80 80v-170l528-527q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L290-120H120Zm640-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z"/>
                                    </svg>
                                </button>
                            </td>
                            <td>
                                <button id="edit-btn-${record.id}" data-id="${record.id}" class="edit-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                        <path d="M200-200h57l391-391-57-57-391 391v57Zm-80 80v-170l528-527q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L290-120H120Zm640-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            })
            .catch(error => {
                console.error('There was an error fetching the records!', error);
            });
    }

    fetchRecords();

    // Add record button click
    $(document).on('click', '#add-btn', function () {
        // Clear form fields and any previous error messages
        $('#addForm')[0].reset();
        $('#validation-errors').empty();
        $('.success-message').hide();

        // Show the modal
        $('#addModal').fadeIn().css("display", "flex");
    });

    // Close modal
    $('#add-close-modal').click(function () {
        $('#addModal').fadeOut();
    });

    // Form submit handling with Axios
    $('#addForm').submit(function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        axios.post('/admin/patient/record/store', formData)
            .then(response => {
                console.log('Record added successfully:', response.data);
                fetchRecords();
                // Fetch updated records and close modal
                $('#addModal').fadeOut();

                // Display success alert
                alert('Record added successfully!');
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    const errors = error.response.data.errors;

                    const divContainer = document.getElementById('validation-error');
                    const errorContainer = document.getElementById('validation-errors');
                    if (errorContainer) {
                        errorContainer.innerHTML = '';

                        // Display new validation errors
                        Object.values(errors).forEach(errorList => {
                            errorList.forEach(errorMessage => {
                                const errorItem = document.createElement('li');
                                errorItem.textContent = errorMessage;
                                errorContainer.appendChild(errorItem);
                            });
                        });

                        // Display error container if hidden
                        divContainer.style.display = 'block';
                    } else {
                        console.error('Validation error container not found.');
                    }
                } else {
                    console.error('Error adding record:', error);
                }
            });
    });
});
