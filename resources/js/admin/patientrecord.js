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


function renderUserTableRows(users, $tableBody) {
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
            </tr>
            `;
            $tableBody.append(row);
        });
    }
}

function fetchRecord(procedures, container) {
    container.empty();
    if(procedures.length === 0) {
        const button = `<tr><td colspan="6"><label class="add-record">+</label></td></tr>`;
        container.append(button);
        $('#img-view img[data-dz-thumbnail]').attr('src', '/images/upload.png').removeClass('show');
    } else {
        procedures.forEach(procedure => {
            const row = `
                <tr>
                    <td><input class="procedure-id" type="text" value="${procedure.id}" hidden /><input class="appointment-date" type="date" value="${procedure.appointment_date || ''}" /></td>
                    <td><input class="procedure-name" type="text" value="${procedure.procedure || ''}" /></td>
                    <td><input class="amount-input" type="text" value="${procedure.amount !== null ? procedure.amount : ' '}" class="amount-input" /></td>
                    <td><input class="paid-input" type="text" value="${procedure.paid !== null ? procedure.paid : ' '}" class="paid-input" /></td>
                    <td>
                        <input type="text" 
                            value="${procedure.balance !== null ? procedure.balance : (procedure.amount - procedure.paid) || ' '}" 
                            class="balance-input" 
                            ${procedure.balance !== null ? procedure.balance : 'disabled'} 
                        />
                    </td>
                    <td><label class="delete-btn" data-id="${procedure.id}"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/></svg></label></td>
                </tr>
            `;
            container.append(row);
        });

        const button = `<tr><td colspan="6"><label class="add-record">+</label></td></tr>`;
        container.append(button);
    }
}

function fetchUsers(page = 1, search = '') {
    axios.post(`/admin/record/user/populate?page=${page}&search=${search}`)
        .then(response => {
            const users = response.data.data;
            const $tableBody = $('#userTableBody');
            const $paginationWrapper = $('#userPagination');
            
            renderUserTableRows(users, $tableBody);

            renderPagination(response.data.current_page, response.data.last_page, $paginationWrapper, (page) => {
                fetchUsers(page, search);
            });
        })
        .catch(error => console.error('Error fetching users!', error));
}

$(document).ready(() => {
    fetchUsers();

    $(document).on('input', '.amount-input, .paid-input', function() {
        const row = $(this).closest('tr');
        const amount = parseFloat(row.find('.amount-input').val()) || 0;
        const paid = parseFloat(row.find('.paid-input').val()) || 0;
        const balance = amount - paid;
    
        if(balance > 0) {
            row.find('.balance-input').val(balance.toFixed(2));
        } else{
            row.find('.balance-input').val('');
        }
    });

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

    $(document).on('submit', '#procedureForm', function (e) {
        e.preventDefault();
    
        const data = [];
    
        $('#procedureTableBody tr').not(':last').each(function () {
            const row = $(this);
            const procedureId = row.find('.procedure-id').val(); 
            const appointmentDate = row.find('.appointment-date').val();
            const procedureName = row.find('.procedure-name').val();
            const amount = row.find('.amount-input').val();
            const paid = row.find('.paid-input').val();
            const balance = row.find('.balance-input').val();
            data.push({
                id: procedureId,
                appointment_date: appointmentDate,
                procedure: procedureName,
                amount: amount,
                paid: paid,
                balance: balance
            });
        });

        axios.post('/admin/record/save', { procedures: data })
            .then(response => {
                alert('Record saved successfully!');
                $(`.radio-btn[data-id="${selectedID}"]`).prop('checked', true).trigger('change');
            })
            .catch(error => {
                console.error('Error saving record!', error);
            });
    });

    $(document).on('click', '.add-record', function () {
        axios.post('/admin/record/add', {user_id: selectedID})
            .then(response => {
                $(`.radio-btn[data-id="${selectedID}"]`).prop('checked', true).trigger('change');
            })
            .catch(error => console.error('Error adding record!', error));
    });

    $(document).on('click', '.delete-btn', function () {
        const recordID = $(this).data('id');
        axios.post('/admin/record/delete', {record_id: recordID})
            .then(response => {
                $(`.radio-btn[data-id="${selectedID}"]`).prop('checked', true).trigger('change');
            })
            .catch(error => console.error('Error adding record!', error));
    });


    $(document).on('change', '.radio-btn', function () {
        const userId = $(this).data('id');
        selectedID = userId;
        const isChecked = $(this).is(':checked');
        const saveBtn = $('.save-btn');
        const container = $('#procedureTableBody');
        if (isChecked) {
            axios.post('/admin/record/populate', { user_id: userId })
                .then(response => {
                    const { procedures} = response.data.data; 
                    fetchRecord(procedures, container);
                    saveBtn.show();
                })
                .catch(error => console.error('Error fetching records!', error));
        } else {
            $('#procedureTableBody').empty(); 
        }
    });
});