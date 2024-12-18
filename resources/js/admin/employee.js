import axios from 'axios';

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


const renderTableRows = (employees, tableBody, buttonCallback) => {
    tableBody.empty();
    if (employees.length === 0) {
        const noEmployeesRow = `
            <tr>
                <td colspan="8">No Employees Found</td>
            </tr>
        `;
        tableBody.append(noEmployeesRow);
    } else {
        employees.forEach(employee => {
            const createdAt = new Date(employee.created_at).toISOString().split('T')[0];
            const row = `
                <tr>
                    <td>${employee.id}</td>
                    <td>${employee.first_name} ${employee.last_name}</td>
                    <td>${employee.username}</td>
                    <td>${employee.number}</td>
                    <td>${employee.role}</td>
                    <td>${employee.status}</td>
                    <td>${createdAt}</td>
                    <td>
                        ${buttonCallback(employee.id)}
                    </td>
                </tr>
            `;
            tableBody.append(row);
        });
    }
};

const fetchActiveEmployees = (page = 1, search = '') => {
    axios.post(`/admin/employee/populate?page=${page}&search=${search}`)
        .then(response => {
            const employees = response.data.data;
            const $tableBody = $('#employeeTableBody');
            const $paginationWrapper = $('#activePagination');

            renderTableRows(employees, $tableBody, id => `
                <button data-id="${id}" class="edit-btn">Edit</button>
                <button data-id="${id}" class="deactivate-btn">Deactivate</button>
            `);

            renderPagination(response.data.current_page, response.data.last_page, $paginationWrapper, (page) => {
                fetchActiveEmployees(page, search);
            });
        })
        .catch(error => console.error('Error fetching active employees!', error));
};

const fetchDeactiveEmployees = (page = 1, search = '') => {
    axios.post(`/admin/employee/populateDeactivated?page=${page}&search=${search}`)
        .then(response => {
            const employees = response.data.data;
            const $tableBody = $('#deactivatedEmployeeTableBody');
            const $paginationWrapper = $('#deactivatedPagination');

            renderTableRows(employees, $tableBody, id => `
                <button data-id="${id}" class="activate-btn">Activate</button>
            `);

            renderPagination(response.data.current_page, response.data.last_page, $paginationWrapper, (page) => {
                fetchDeactiveEmployees(page, search);
            });
        })
        .catch(error => console.error('Error fetching deactivated employees!', error));
};

const handleValidationError = (error, $errorContainer) => {
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
};

$(document).ready(() => {
    fetchActiveEmployees();
    fetchDeactiveEmployees();

    $('#activeSearchInput').on('input', function () {
        const search = $(this).val();
        fetchActiveEmployees(1, search);
    });

    $('#deactiveSearchInput').on('input', function () {
        const search = $(this).val();
        fetchDeactiveEmployees(1, search);
    });

    $(document).on('click', '.pagination-link', function () {
        const page = $(this).data('page');
        const search = $('.searchInput').val();
        if (!$(this).hasClass('disabled')) {
            const isActiveTable = $(this).closest('#activePagination').length > 0;
            if (isActiveTable) {
                fetchActiveEmployees(page, search);
            } else {
                fetchDeactiveEmployees(page, search);
            }
        }
    });

    $(document).on('click', '.edit-btn', function () {
        $('#update-errors').empty().hide();
        const employeeId = $(this).data('id');
        axios.get(`/admin/employee/fetch/${employeeId}`)
            .then(response => {
                const data = response.data;
                $('#edit-employee-id').val(data.id);
                $('#updateFirstname').val(data.first_name);
                $('#updateLastname').val(data.last_name);
                $('#updateUsername').val(data.username);
                $('#updateNumber').val(data.number);
                $('#updateRole').val(data.role);
                $('#updateModal').fadeIn().css('display', 'flex');
            })
            .catch(error => console.error('Error fetching employee data:', error));
    });

    $(document).on('click', '.deactivate-btn', function () {
        const employeeId = $(this).data('id');
        axios.get(`/admin/employee/fetch/${employeeId}`)
            .then(response => {
                const data = response.data;
                if (data) {
                    $('#deactivate-employee-id').val(data.id);
                    $('#deactivate-username').text(data.username || 'No username available');
                    $('#deactivateModal').fadeIn().css('display', 'flex');
                } else {
                    console.error('No employee data found.');
                }
            })
            .catch(error => console.error('Error fetching employee data:', error));
    });

    $(document).on('click', '.activate-btn', function () {
        const employeeId = $(this).data('id');
        axios.get(`/admin/employee/fetch/${employeeId}`)
            .then(response => {
                const data = response.data;
                if (data) {
                    $('#activate-employee-id').val(data.id);
                    $('#activate-username').text(data.username || 'No username available');
                    $('#activateModal').fadeIn().css('display', 'flex');
                } else {
                    console.error('No employee data found.');
                }
            })
            .catch(error => console.error('Error fetching employee data:', error));
    });

    $('#add-btn').on('click', function () {
        $('#addForm')[0].reset();
        $('#validation-errors').empty().hide();
        $('#addModal').fadeIn().css('display', 'flex');
    });

    $('#addForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        axios.post('/admin/employee/store', formData)
            .then(() => {
                fetchActiveEmployees();
                fetchDeactiveEmployees();
                $('#addModal').fadeOut();
                alert('Employee added successfully!');
            })
            .catch(error => handleValidationError(error, $('#validation-errors')));
    });

    $('#updateForm').on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        axios.post('/admin/employee/update', formData)
            .then(() => {
                fetchActiveEmployees();
                fetchDeactiveEmployees();
                $('#updateModal').fadeOut();
                alert('Employee updated successfully!');
            })
            .catch(error => handleValidationError(error, $('#update-errors')));
    });

    $('#deactivateForm').on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        axios.post('/admin/employee/deactivate', formData)
            .then(() => {
                fetchActiveEmployees();
                fetchDeactiveEmployees();
                $('#deactivateModal').fadeOut();
                alert('Employee deactivated successfully!');
            })
            .catch(error => handleValidationError(error, $('#validation-errors')));
    });

    $('#activateForm').on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        axios.post('/admin/employee/activate', formData)
            .then(() => {
                fetchActiveEmployees();
                fetchDeactiveEmployees();
                $('#activateModal').fadeOut();
                alert('Employee Activated successfully!');
            })
            .catch(error => handleValidationError(error, $('#validation-errors')));
    });


    $('#add-close-modal').click(() => $('#addModal').fadeOut());
    $('#update-close-modal').click(() => $('#updateModal').fadeOut());
    $('#deactivate-close-modal').click(() => $('#deactivateModal').fadeOut());
    $('#activate-close-modal').click(() => $('#activateModal').fadeOut());
});
