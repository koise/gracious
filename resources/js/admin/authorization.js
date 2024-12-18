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
                    ${buttonCallback(user.id, `${user.first_name} ${user.last_name}`)}
            </tr>
            `;
            $tableBody.append(row);
        });
    }
}

function renderAuthorizationGallery(authorizations, $container) {
    $container.empty();
    if (authorizations.length === 0) {
        $container.append('<div class="no-authorization"><h2>No authorizations found</h2></div>');
    } else {
        authorizations.forEach(authorization => {
            const fileName = authorization.file_path.split('\\').pop().split('/').pop();
            const imgElement = `
                <div class="authorization-item">
                    <img src="/authorizations/${fileName}" alt="${authorization.created_at}" class="gallery-image" />
                    <p>${authorization.name}</p>
                    <p>${authorization.created_at}</p>
                    <button data-name="${authorization.name}" data-id="${authorization.id}" data-image="${authorization.file_path}" class="edit-btn">Edit</button>
                </div>
            `;
            $container.append(imgElement);
        });
    }

    $('.gallery-image').on('click', function() {
        const modal = $('#imageModal');
        const modalImg = $('#modalImage');
        const captionText = $('#caption');

        modal.css('display', 'block');
        modalImg.attr('src', this.src);
        captionText.text(this.alt);
    });

    // Close the modal
    $('.close').on('click', function() {
        $('#imageModal').css('display', 'none');
    });
}

function fetchUsers(page = 1, search = '') {
    axios.post(`/admin/authorization/user/populate?page=${page}&search=${search}`)
        .then(response => {
            const users = response.data.data;
            const $tableBody = $('#userTableBody');
            const $paginationWrapper = $('#userPagination');
            
            renderUserTableRows(users, $tableBody, (id, name) => `
                <td><button data-id="${id}" data-name="${name}" class="add-btn">+</button></td>
            `);

            renderPagination(response.data.current_page, response.data.last_page, $paginationWrapper, (page) => {
                fetchUsers(page, search);
            });
        })
        .catch(error => console.error('Error fetching users!', error));
}

$(document).ready(() => {
    fetchUsers();
    $('#searchInput').on('input', function () {
        const search = $(this).val();
        fetchUsers(1, search);
    });

    $(document).on('click', '.pagination-link', function () {
        const page = $(this).data('page');
        const search = $('#searchInput').val();
        if (!$(this).hasClass('disabled')) {
            fetchUsers(page, search);
        }
    });

    $(document).on('change', '.radio-btn', function () {
        const userId = $(this).data('id');
        selectedID = userId;
        const isChecked = $(this).is(':checked');
        if (isChecked) {
            axios.post('/admin/authorization/populate', { user_id: userId })
                .then(response => {
                    const authorizations = response.data.data;
                    const container = $('#authorization-container');
    
                    renderAuthorizationGallery(authorizations, container);
                })
                .catch(error => console.error('Error fetching authorizations!', error));
        } else {
            $('#authorization-container').empty(); 
        }
    });
    const dropzone = new Dropzone(".dropzone", {
        url: "/admin/authorization/store", 
        autoProcessQueue: false,
        acceptedFiles: "image/*",
        maxFiles: 1,
        previewsContainer: "#img-view",
        disablePreviews: true,
        clickable: "#drop-area",
        addRemoveLinks: true,
        
        init: function () {
            const defaultMessage = document.querySelector(".dz-default.dz-message");
            const dzInstance = this;

            this.on("addedfile", (file) => {
                $("#addForm #img-view p").hide(); 
            });

            this.on("thumbnail", (file, dataUrl) => {
                $("#addForm #img-view img[data-dz-thumbnail]").attr("src", dataUrl).addClass('show');
            });

            this.on("sending", function (file, xhr, formData) {
                // Append additional form data before sending
                formData.append('id', $('#add-authorization-id').val());
                formData.append('_token', $('meta[name="csrf-token"]').attr('content')); 
            });

            this.on("success", (file, response) => {
                console.log("File uploaded successfully!", response);
                alert("Authorization added successfully!");
                $('#addModal').fadeOut();
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
        $('#validation-errors').empty().hide();
        $('#add-authorization-id').val(userId);
        $('#add-authorization-name').text('Name: ' + name);
        $('#addModal').fadeIn().css('display', 'flex');
    });

    $(document).on('click', '.edit-btn', function () {
        const userId = $(this).data('id');
        const name = $(this).data('name');
        const file_path = $(this).data('image');

        $('#updateForm')[0].reset();
        $('#validation-errors').empty().hide();
        $('#update-authorization-id').val(userId);
        $('#update-authorization-name').text('Name: ' + name);
        $('#updateModal').fadeIn().css('display', 'flex');
    });

    $('#addForm').on('submit', function (event) {
        event.preventDefault();

        if (dropzone.getAcceptedFiles().length === 0) {
            alert("Please upload an image.");
            return;
        }

        dropzone.processQueue();
    });

    $('#add-close-modal').click(() => $('#addModal').fadeOut());
    $('#update-close-modal').click(() => $('#updateModal').fadeOut());
});