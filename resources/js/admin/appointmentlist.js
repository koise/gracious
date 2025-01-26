import axios from 'axios';
import $ from 'jquery';

const renderPagination = (currentPage, lastPage, paginationWrapper) => {
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
};

const renderTableRows = (appointments, tableBody) => {
    tableBody.empty();
    if (appointments.length === 0) {
        const noAppointmentsRow = `
            <tr>
                <td colspan="8">No Appointments Found</td>
            </tr>
        `;
        tableBody.append(noAppointmentsRow);
    } else {
        appointments.forEach(appointment => {
            const createdAt = new Date(appointment.created_at).toISOString().split('T')[0];
            const row = `
            <tr>
                <td>${appointment.id}</td>
                <td>${appointment.name}</td>
                <td>${appointment.appointment_date}</td>
                <td>${appointment.appointment_time}</td>
                <td>${appointment.procedures}</td>
                <td>${appointment.status}</td>
                <td>${createdAt}</td>
            </tr>
        `;
            tableBody.append(row);
        });
    }
};

const fetchAppointmentList = (page = 1, search = '') => {
    axios.post(`/admin/appointment/populate?page=${page}&search=${search}`)
        .then(response => {
            const appointments = response.data.data;
            console.log(appointments);
            const $tableBody = $('#appointmentsTableBody');
            const $paginationWrapper = $('#appointmentsPagination');

            renderTableRows(appointments, $tableBody);

            renderPagination(response.data.pagination.current_page, response.data.pagination.last_page, $paginationWrapper, (page) => {
                fetchAppointmentList(page, search);
            });
        })
        .catch(error => console.error('Error fetching appointments!', error));
};


$(document).ready(() => {
    fetchAppointmentList();

    $('#searchInput').on('input', function () {
        const search = $(this).val();
        fetchAppointmentList(1, search);
    });

    $(document).on('click', '.pagination-link', function () {
        const page = $(this).data('page');
        const search = $('#searchInput').val();
        if (!$(this).hasClass('disabled')) {
            fetchAppointmentList(page, search);
        }
    });
});