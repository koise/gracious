import axios from 'axios';

function renderPagination(currentPage, lastPage, paginationWrapper, onPageClick) {
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

function renderTableRows(users, $tableBody, buttonCallback) {
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
            const createdAt = new Date(user.created_at).toISOString().split('T')[0];
            const row = `
            <tr>
                <td>${user.id}</td>
                <td>${user.first_name} ${user.last_name}</td>
                <td>${user.username}</td>
                <td>${user.number}</td>
                <td>${user.status}</td>
                <td>${createdAt}</td>
                <td>
                    ${buttonCallback(user.id)}
                </td>
            </tr>
            `;
            $tableBody.append(row);
        });
    }
}

function fetchActiveUsers(page = 1, search = '') {
    axios.post(`/admin/users/populate?page=${page}&search=${search}`)
        .then(response => {
            const users = response.data.data;
            const $tableBody = $('#userTableBody');
            const $paginationWrapper = $('#activePagination');

            renderTableRows(users, $tableBody, id => `
                <button data-id="${id}" class="edit-btn">Edit</button>
                <button data-id="${id}" class="deactivate-btn">Deactivate</button>
            `);

            renderPagination(response.data.current_page, response.data.last_page, $paginationWrapper, (page) => {
                fetchActiveUsers(page, search);
            });
        })
        .catch(error => console.error('Error fetching active users!', error));
}


function fetchDeactiveUsers(page = 1, search = '') {
    axios.post(`/admin/users/populateDeactivated?page=${page}&search=${search}`)
        .then(response => {
            const users = response.data.data;
            const $tableBody = $('#deactivatedUserTableBody');
            const $paginationWrapper = $('#deactivatedPagination');

            renderTableRows(users, $tableBody, id => `
                <button data-id="${id}" class="activate-btn">Activate</button>
            `);

            renderPagination(response.data.current_page, response.data.last_page, $paginationWrapper, (page) => {
                fetchDeactiveUsers(page, search);
            });
        })
        .catch(error => console.error('Error fetching deactivated users!', error));
}

function handleValidationError(error, $errorContainer) {
    if (error.response && error.response.status === 422) {
        const errors = error.response.data.errors;
        $errorContainer.empty().show();
        Object.values(errors).forEach(errorList => {
            errorList.forEach(errorMessage => {
                $errorContainer.append(`<li>${errorMessage}</li>`);
            });
        });
    } else {
        console.error('Error:', error);
    }
}

$(document).ready(() => {
    fetchActiveUsers();
    fetchDeactiveUsers();

    $('#activeSearchInput').on('input', function () {
        const search = $(this).val();
        fetchActiveUsers(1, search);
    });

    $('#deactiveSearchInput').on('input', function () {
        const search = $(this).val();
        fetchDeactiveUsers(1, search);
    });

    $(document).on('click', '.pagination-link', function () {
        const page = $(this).data('page');
        const search = $('#searchInput').val();
        if (!$(this).hasClass('disabled')) {
            const isActiveTable = $(this).closest('#activePagination').length > 0;
            if (isActiveTable) {
                fetchActiveUsers(page, search);
            } else {
                fetchDeactiveUsers(page, search);
            }
        }
    });

    $('#add-btn').on('click', function () {
        $('#addForm')[0].reset();
        $('#validation-errors').empty().hide();
        $('#addModal').fadeIn().css('display', 'flex');
    });

    $(document).on('click', '.edit-btn', function () {
        $('#update-errors').empty().hide();
        const employeeId = $(this).data('id');
        
        axios.get(`/admin/user/fetch/${employeeId}`)
            .then(response => {
                const data = response.data;
                $('#edit-user-id').val(data.id);
                $('#updateFirstname').val(data.first_name);
                $('#updateLastname').val(data.last_name);
                $('#updateUsername').val(data.username);
                $('#updateAge').val(data.age);
                $('#updateNumber').val(data.number);
                $('#updateStreetAddress').val(data.street_address);
                $('#updateProvince').val(data.province).trigger('change');
                
                const citySelect = $('#updateCity');
                const selectedCity = data.city;
    
                fetch(`/account/cities/${data.province}`)
                    .then(response => response.json())
                    .then(cities => {
                        citySelect.empty().append('<option value="">Select City</option>');
                        cities.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.name;
                            citySelect.append(option);
                        });
    
                        // Set the city value after options are populated
                        citySelect.val(selectedCity);
                    })
                    .catch(error => console.error('Error fetching cities:', error));
    
                $('#updateCountry').val(data.country);
                $('#updateModal').fadeIn().css('display', 'flex');
            })
            .catch(error => console.error('Error fetching user data:', error));
    });

    $(document).on('click', '.deactivate-btn', function () {
        const userId = $(this).data('id');
        axios.get(`/admin/user/fetch/${userId}`)
            .then(response => {
                const data = response.data;
                if (data) {
                    $('#deactivate-user-id').val(data.id);
                    $('#deactivate-username').text(data.username || 'No username available');
                    $('#deactivateModal').fadeIn().css('display', 'flex');
                } else {
                    console.error('No employee data found.');
                }
            })
            .catch(error => console.error('Error fetching employee data:', error));
    });

    $(document).on('click', '.activate-btn', function () {
        const userId = $(this).data('id');
        axios.get(`/admin/user/fetch/${userId}`)
            .then(response => {
                const data = response.data;
                if (data) {
                    $('#activate-user-id').val(data.id);
                    $('#activate-username').text(data.username || 'No username available');
                    $('#activateModal').fadeIn().css('display', 'flex');
                } else {
                    console.error('No employee data found.');
                }
            })
            .catch(error => console.error('Error fetching employee data:', error));
    });

    $('#addForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        axios.post('/admin/user/store', formData)
            .then(() => {
                fetchActiveUsers();
                fetchDeactiveUsers();
                $('#addModal').fadeOut();
                alert('Patient added successfully!');
            })
            .catch(error => handleValidationError(error, $('#validation-errors')));
    });
    
    $('#updateForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        axios.post('/admin/user/update', formData)
            .then(() => {
                fetchActiveUsers();
                fetchDeactiveUsers();
                $('#activeSearchInput').val('');
                $('#deactiveSearchInput').val('');
                $('#updateModal').fadeOut();
                alert('User updated successfully!');
            })
            .catch(error => handleValidationError(error, $('#update-errors')));
    });

    $('#deactivateForm').on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        axios.post('/admin/user/deactivate', formData)
            .then(() => {
                fetchActiveUsers();
                fetchDeactiveUsers();
                
                $('#deactivateModal').fadeOut();
                alert('Patient deactivated successfully!');
            })
            .catch(error => handleValidationError(error, $('#validation-errors')));
    });

    $('#activateForm').on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        axios.post('/admin/user/activate', formData)
            .then(() => {
                fetchActiveUsers();
                fetchDeactiveUsers();
                $('#activateModal').fadeOut();
                alert('Patient Activated successfully!');
            })
            .catch(error => handleValidationError(error, $('#validation-errors')));
    });


    $('#add-close-modal').click(() => $('#addModal').fadeOut());
    $('#update-close-modal').click(() => $('#updateModal').fadeOut());
    $('#deactivate-close-modal').click(() => $('#deactivateModal').fadeOut());
    $('#activate-close-modal').click(() => $('#activateModal').fadeOut());
});