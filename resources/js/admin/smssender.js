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
            const row = `
            <tr>
                <td>${user.id}</td>
                <td>${user.first_name} ${user.last_name}</td>
                <td>${user.username}</td>
                <td>${user.number}</td>
                    ${buttonCallback(user.id, `${user.first_name} ${user.last_name}`, user.number)}
            </tr>
            `;
            $tableBody.append(row);
        });
    }
}


function fetchUsers(page = 1, search = '') {
    axios.post(`/admin/sms/user/populate?page=${page}&search=${search}`)
        .then(response => {
            const users = response.data.data;
            const $tableBody = $('#userTableBody');
            const $paginationWrapper = $('#userPagination');
            
            renderUserTableRows(users, $tableBody, (id, name, number) => `
                <td><button data-id="${id}" data-name="${name}"  data-number="${number}" class="send-btn"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M120-160v-640l760 320-760 320Zm80-120 474-200-474-200v140l240 60-240 60v140Zm0 0v-400 400Z"/></svg></button></td>
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

    $(document).on('click', '.send-btn', function () {

        const userId = $(this).data('id');
        const name = $(this).data('name');
        const number = $(this).data('number');
        $('#addForm')[0].reset();
        $('#validation-errors').empty().hide();
        $('#add-authorization-id').val(userId);
        $('#add-authorization-name').text('Name: ' + name);
        $('#add-authorization-number').text('Number: ' + number);
        $('#addModal').fadeIn().css('display', 'flex');
    });


    $('#addForm').on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        axios.post('/admin/sms/send', formData)
            .then(response => {
                fetchUsers();
                $('#addModal').fadeOut();
                alert('SMS Sent Successfully');
            })
            .catch(error => {
                const errors = error.response.data.errors;
                const errorsList = Object.values(errors).map(error => `<li>${error}</li>`);
                $('#validation-errors').empty().append(errorsList).show();
            });
    });
$('#add-close-modal').click(() => $('#addModal').fadeOut());
});