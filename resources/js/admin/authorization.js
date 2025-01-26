import axios from 'axios';
import { Dropzone } from "dropzone";

let selectedID = '';

function renderPagination(currentPage, lastPage, paginationWrapper) {
    const maxVisibleButtons = 3; 
    let startPage, endPage;

    if (lastPage <= maxVisibleButtons) {
        startPage = 1;
        endPage = lastPage;
    } else {
        startPage = Math.max(1, currentPage - Math.floor(maxVisibleButtons / 2));
        endPage = startPage + maxVisibleButtons - 1;

        if (endPage > lastPage) {
            endPage = lastPage;
            startPage = endPage - maxVisibleButtons + 1;
        }
    }

    paginationWrapper.empty();

    paginationWrapper.append(currentPage > 1
        ? `<button class="pagination-link" data-page="${currentPage - 1}">Previous</button>`
        : `<button class="pagination-link disabled" disabled>Previous</button>`);

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        paginationWrapper.append(`
            <button class="pagination-link ${activeClass}" data-page="${i}">${i}</button>
        `);
    }

    paginationWrapper.append(currentPage < lastPage
        ? `<button class="pagination-link" data-page="${currentPage + 1}">Next</button>`
        : `<button class="pagination-link disabled" disabled>Next</button>`);
}

function renderUserTableRows(users, $tableBody, buttonCallback) {
    $tableBody.empty();
    if (users.length === 0) {
        const noUsersRow = `
            <tr>
                <td colspan="6">No Users Found</td>
            </tr>
        `;
        $tableBody.append(noUsersRow);
    } else {
        users.forEach(user => {
            const isChecked = selectedID === user.id ? 'checked' : ''; 
            const row = `
            <tr>
                <td><input type="radio" data-id="${user.id}" name="id" class="radio-btn" ${isChecked} /></td>
                <td>${user.id}</td>
                <td>${user.first_name} ${user.last_name}</td>
                <td>${user.username}</td>
                <td>${user.number}</td>
                ${buttonCallback(user)}
            </tr>
            `;
            $tableBody.append(row);
        });
    }
}

function renderRecordTableRows(records, $tableBody, buttonCallback) {
    $tableBody.empty();
    if (records.length === 0) {
        const noRecordsRow = `
            <tr>
                <td colspan="6" style="font-weight: bold;">No Records Found</td>
            </tr>
        `;
        $tableBody.append(noRecordsRow);
    } else {
        records.forEach(record => {
            const row = `
            <tr>
                <td>${record.id}</td>
                <td>${record.patient_id}</td>
                <td>${record.type}</td>
                <td>${record.appointment_date}</td>
                <td>${record.created_at}</td>
                ${buttonCallback(record)}
            </tr>
            `;
            $tableBody.append(row);
        });
    }
}

function fetchUsers(page = 1, search = '') {
    axios.post(`/admin/authorization/user/populate?page=${page}&search=${search}`)
        .then(response => {
            const users = response.data.data;
            const $tableBody = $('#userTableBody');
            const $paginationWrapper = $('#userPagination');

            renderUserTableRows(users, $tableBody, (user) => `
                <td><button data-id="${user.id}" data-name="${user.first_name} ${user.last_name}" class="add-btn">+</button></td>
            `);

            renderPagination(response.data.current_page, response.data.last_page, $paginationWrapper, (page) => {
                fetchUsers(page, search);
            });
        })
        .catch(error => console.error('Error fetching users!', error));
}

function fetchRecords(userId, page = 1, search = '') {
    axios.post(`/admin/authorization/populate?page=${page}&search=${search}`, { user_id: userId })
        .then(response => {
            const records = response.data.data;
            const $tableBody = $('#recordTableBody');
            const $paginationWrapper = $('#recordPagination');

            renderRecordTableRows(records, $tableBody, (record) => `
                <td>
                <button data-path="${record.file_path}" class="view-btn">View</button>
                <button data-id="${record.id}" data-name="${record.patient_name}" data-type="${record.type}" data-date="${record.appointment_date}" data-path="${record.file_path}" class="edit-btn">Edit</button>
                </td>
                
            `);

            renderPagination(response.data.current_page, response.data.last_page, $paginationWrapper, (page) => {
                fetchRecords(userId, page, search);
            });
        })
        .catch(error => console.error('Error fetching records!', error));
}


$(document).ready(() => {
    fetchUsers();
    $('#searchInput').on('input', function () {
        const search = $(this).val();
        fetchUsers(1, search);
    });

    $('#searchRecord').on('input', function () {
        const search = $(this).val();
        fetchRecords(selectedID, 1, search);
    });

    $(document).on('click', '.pagination-link', function () {
        const page = $(this).data('page');
        const search = $('#searchInput').val();
        if (!$(this).hasClass('disabled')) {
            fetchUsers(page, search);
        }
    });

    $(document).on('click', '.view-btn', function () {
        $('#imageModal').fadeIn().css('display', 'flex');

        const file_path = $(this).data('path');
        $("#modalImage").attr("src", "http://127.0.0.1:8000/" + file_path);
    });

    $(document).on('change', '.radio-btn', function () {
        const userId = $(this).data('id');
        selectedID = userId;
        const isChecked = $(this).is(':checked');
        if (isChecked) {
            fetchRecords(userId);
        } else {
            $('#recordTableBody').empty(); 
        }
    });
    
    const dropzone = new Dropzone("#addForm .dropzone", {
        url: "/admin/authorization/store",
        autoProcessQueue: false,
        acceptedFiles: "image/*",
        maxFiles: 1,
        previewsContainer: "#addForm #img-view",
        disablePreviews: true,
        clickable: "#addForm #drop-area",
        addRemoveLinks: true,
        
        init: function () {
            const dzInstance = this;
    
            this.on("addedfile", (file) => {
                if (this.files.length > 1) {
                    this.removeFile(this.files[0]);
                }
                $("#addForm #img-view p").hide(); 
            });
    
            this.on("thumbnail", (file, dataUrl) => {
                $("#addForm #img-view img[data-dz-thumbnail]").attr("src", dataUrl).addClass('show');
            });
    
            this.on("sending", function (file, xhr, formData) {
                formData.append('id', $('#add-authorization-id').val());
                formData.append('type', $('#type').val());
                formData.append('appointment_date', $('#appointment_date').val());
                formData.append('_token', $('meta[name="csrf-token"]').attr('content')); 
            });
    
            this.on("success", (file, response) => {
                alert("Authorization added successfully!");
                $('#addModal').fadeOut();
    
                $('#addForm #img-view img[data-dz-thumbnail]').attr('src', 'http://127.0.0.1:8000/images/upload.png').removeClass('show');
                fetchUsers(); 
            });
    
            this.on("error", (file, errorMessage) => {
                console.error("Upload failed!", errorMessage);
            });
        },
    });

    const updateDropzone = new Dropzone("#updateForm .dropzone", {
        url: "/admin/authorization/update",
        autoProcessQueue: false,
        acceptedFiles: "image/*",
        maxFiles: 1,
        previewsContainer: "#updateForm #img-view",
        disablePreviews: true,
        clickable: "#updateForm #drop-area",
        addRemoveLinks: true,
        
        init: function () {
            const dzInstance = this;
    
            this.on("addedfile", (file) => {
                if (this.files.length > 1) {
                    this.removeFile(this.files[0]);
                }
                $("#updateForm #img-view p").hide(); 
            });
    
            this.on("thumbnail", (file, dataUrl) => {
                $("#updateForm #img-view img[data-dz-thumbnail]").attr("src", dataUrl).addClass('show');
            });
    
            this.on("sending", function (file, xhr, formData) {
                formData.append('id', $('#update-authorization-id').val());
                formData.append('type', $('#type').val());
                formData.append('appointment_date', $('#appointment_date').val());
                formData.append('_token', $('meta[name="csrf-token"]').attr('content')); 
            });
    
            this.on("success", (file, response) => {
                alert("Record updated successfully!");
                $('#updateModal').fadeOut();
    
                $('#updateForm #img-view img[data-dz-thumbnail]').attr('src', 'http://127.0.0.1:8000/images/upload.png').removeClass('show');
                fetchUsers(); 
            });
    
            this.on("error", (file, errorMessage) => {
                console.error("Upload failed!", errorMessage);
            });
        },
    });


    $(document).on('click', '.add-btn', function () {
        const userId = $(this).data('id');
        const name = $(this).data('name');
    
        $('#addForm')[0].reset();
        $('#appointment_date').empty();
        $('#add-authorization-id').val(userId);
        $('#add-authorization-name').text('Name: ' + name);

        $('#addModal').fadeIn().css('display', 'flex');

        axios.get(`/admin/record/appointments/${userId}`)
            .then(response => {
                const appointments = response.data;
                if (appointments.length > 0) {
                    $('#appointment_date').append(`<option selected disabled value="">Select Appointment Date</option>`);
                    appointments.forEach(({ appointment_date, procedures }) => {
                        $('#appointment_date').append(`<option value="${appointment_date}">${appointment_date} - ${procedures}</option>`);
                    });
                } else {
                    $('#appointment_date').append('<option value="" disabled>No appointments found</option>');
                }
            })
            .catch(error => {
                console.error('Failed to fetch appointment dates:', error);
            });
    });

    $(document).on('click', '.edit-btn', function () {
        const userId = $(this).data('id');
        const name = $(this).data('name');
        const type = $(this).data('type');
        const appointment_date = $(this).data('date');
        const file_path = $(this).data('path');
    
        $('#updateForm')[0].reset();
        $('#update-appointment-date').empty();
        $('#update-authorization-id').val(userId);
        $('#update-authorization-name').text('Name: ' + name);
        $('#update-type').val(type);
        $('#update-appointment-date').val(appointment_date);
        $('#updateModal').fadeIn().css('display', 'flex');

        if (file_path) {
            const mockFile = {
                name: "Existing File",
                size: 1234567,
                accepted: true 
            };

            updateDropzone.emit("thumbnail", mockFile, "http://127.0.0.1:8000/" + file_path); // Add thumbnail
            $("#updateForm #img-view p").hide();
        }
        axios.get(`/admin/record/appointments/${userId}`)
            .then(response => {
                const appointments = response.data;

                if (appointments.length > 0) {
                    $('#update-appointment-date').append(`<option selected disabled value="">Select Appointment Date</option>`);
                    appointments.forEach(({ appointment_date, procedures }) => {
                        $('#update-appointment-date').append(`<option value="${appointment_date}">${appointment_date} - ${procedures}</option>`);
                    });
                } else {
                    $('#update-appointment-date').append('<option value="" disabled>No appointments found</option>');
                }
            })
            .catch(error => {
                console.error('Failed to fetch appointment dates:', error);
            });
    });

    $('#addForm').on('submit', function (event) {
        event.preventDefault();

        if (dropzone.getAcceptedFiles().length === 0) {
            alert("Please upload an image.");
            return;
        }

        dropzone.processQueue();
    });

    $('#updateForm').on('submit', function (event) {
        event.preventDefault();
    
        if (updateDropzone.getAcceptedFiles().length > 0) {
            updateDropzone.processQueue();
        } else {
            const formData = new FormData(this);
            axios.post('/admin/authorization/update', formData)
                .then(response => {
                    alert("Record updated successfully!");
                    $('#updateModal').fadeOut();
                })
                .catch(error => {
                    console.error("Error:", error.response.data);
                });
        }
    });

    $('#add-close-modal').click(() => {
        $('#addModal').fadeOut();
    });
    
    $('#update-close-modal').click(() => {
        $('#updateModal').fadeOut();
    });

    $('.close').click(() => $('#imageModal').fadeOut());
});