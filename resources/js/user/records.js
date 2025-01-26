import $ from 'jquery';
import axios from 'axios';

// Pagination Renderer
function renderPagination(currentPage, lastPage, paginationWrapper, onPageClick) {
    const maxVisibleButtons = 3;
    let startPage, endPage;

    if (lastPage <= maxVisibleButtons) {
        startPage = 1;
        endPage = lastPage;
    } else {
        startPage = Math.max(1, currentPage - Math.floor(maxVisibleButtons / 2));
        endPage = Math.min(lastPage, startPage + maxVisibleButtons - 1);

        if (endPage > lastPage) {
            endPage = lastPage;
            startPage = Math.max(1, endPage - maxVisibleButtons + 1);
        }
    }

    paginationWrapper.empty();

    // Previous Button
    paginationWrapper.append(
        currentPage > 1
            ? `<button class="pagination-link" data-page="${currentPage - 1}">Previous</button>`
            : `<button class="pagination-link disabled" disabled>Previous</button>`
    );

    // Page Buttons
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        paginationWrapper.append(`<button class="pagination-link ${activeClass}" data-page="${i}">${i}</button>`);
    }

    // Next Button
    paginationWrapper.append(
        currentPage < lastPage
            ? `<button class="pagination-link" data-page="${currentPage + 1}">Next</button>`
            : `<button class="pagination-link disabled" disabled>Next</button>`
    );

    // Pagination Click Events
    paginationWrapper.find('.pagination-link').not('.disabled').on('click', function () {
        const page = $(this).data('page');
        onPageClick(page);
    });
}

// Authorization Table Renderer
function renderAuthorizationRow(authorizations, tableBody, buttonCallback) {
    tableBody.empty();
    if (authorizations.length === 0) {
        tableBody.append('<tr><td colspan="4">No Records Found</td></tr>');
        return;
    }

    authorizations.forEach((authorization) => {
        const row = `
            <tr>
                <td>${authorization.type}</td>
                <td>${authorization.appointment_date}</td>
                <td>${authorization.created_at}</td>

                ${buttonCallback(authorization.id, authorization.file_path)}
            </tr>`;
        tableBody.append(row);
    });
}

// Record Table Renderer
function renderRecordRow(records, tableBody, buttonCallback) {
    tableBody.empty();
    if (records.length === 0) {
        tableBody.append('<tr><td colspan="5">No Treatment Plan Found</td></tr>');
        return;
    }

    records.forEach((record) => {
        const row = `
            <tr>
                <td>${record.appointment_date}</td>
                <td>${record.procedure}</td>
                <td>${record.amount}</td>
                <td>${record.paid}</td>
                <td>${record.balance}</td>
                ${buttonCallback(record.id, record.file_path)}
            </tr>`;
        tableBody.append(row);
    });
}

// Fetch Record Data
function fetchRecord(page = 1, search = '') {
    axios
        .post(`record/populate?page=${page}`, { search })
        .then((response) => {
            const { data: records, current_page, last_page } = response.data;
            const tableBody = $('#recordTableBody');
            const paginationWrapper = $('#recordPagination');

            renderRecordRow(records, tableBody, (id, file_path) => `
                <td><button data-id="${id}" data-file="${file_path}" class="view-btn">View</button></td>
            `);

            renderPagination(current_page, last_page, paginationWrapper, (page) => {
                fetchRecord(page, search);
            });
        })
        .catch((error) => console.error('Error fetching records:', error));
}

function fetchModalRecord(procedures, medical_records, container) {
    container.empty();
    if (procedures.length === 0) {
        container.append('<tr><td colspan="5">No procedures found</td></tr>');
    } else {
        procedures.forEach((procedure) => {
            container.append(`
                <tr>
                    <td>${procedure.appointment_date || ''}</td>
                    <td>${procedure.procedure || ''}</td>
                    <td>${procedure.amount !== null ? procedure.amount : ''}</td>
                    <td>${procedure.paid !== null ? procedure.paid : ''}</td>
                    <td>${procedure.balance !== null ? procedure.balance : ''}</td>
                </tr>
            `);
        });
    }

    if (medical_records.length > 0) {
            $('#img-view [data-dz-thumbnail]').attr('src', '/' + medical_records[0].file_path);
    } else {
        $('#img-view [data-dz-thumbnail]').attr('src', '/images/upload.png')
    }
}
// Fetch Authorization Data
function fetchAuthorization(page = 1, search = '') {
    axios
        .post(`authorization/populate?page=${page}`, { search })
        .then((response) => {
            const { data: authorizations, current_page, last_page } = response.data;
            const tableBody = $('#authorizationTableBody');
            const paginationWrapper = $('#authorizationPagination');

            renderAuthorizationRow(authorizations, tableBody, (id, file_path) => `
                <td><button data-id="${id}" data-file="${file_path}" class="view-authorization-btn">View</button></td>
            `);

            renderPagination(current_page, last_page, paginationWrapper, (page) => {
                fetchAuthorization(page, search);
            });
        })
        .catch((error) => console.error('Error fetching authorizations:', error));
}

// Event Listeners
$(document).ready(() => {
    fetchRecord();
    fetchAuthorization();

    $('#searchInput').on('input', function () {
        const search = $(this).val();
        fetchAuthorization(1, search);
    });

    $(document).on('click', '.view-btn', function () {
        const recordId = $(this).data('id');
        axios
            .post(`record/modal/populate/${recordId}`)
            .then((response) => {
                const { procedures, medical_records } = response.data.data;
                const procedureContainer = $('#procedureTableBody');
                console.log(procedures, medical_records);
                fetchModalRecord(procedures, medical_records, procedureContainer);

                // Show the modal
                $('#viewModal').fadeIn().css('display', 'flex');
            })
            .catch((error) => console.error('Error fetching record details:', error));
    });

    $(document).on('click', '.view-authorization-btn', function () {
        const filePath = $(this).data('file');

        $('#authorizationImage').attr('src', '/' + filePath);

        $('#authorizationModal').fadeIn();
    });
    
    $('#closeAuthorizationModal').on('click', function () {

        $('#authorizationModal').fadeOut();
    });

    $('#view-close-modal').on('click', function () {
        $('#viewModal').fadeOut().css('display', 'none');
    });
});
