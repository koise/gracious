import $ from 'jquery';
import axios from 'axios';

// Utility Functions
function resetCurrentAppointmentUI() {
    $('.progress-node').removeClass('active');
    $('.progress-line').removeClass('active');
    $('#dateText').text('None');
    $('#timeText').text('None');
    $('#serviceText').text('None');
    $('#bookButton').show();
    $('#cancelButton').hide();
}

function updateCancelButton(status, hoursDifference) {
    const cancelButton = $('#cancelButton');
    const bookButton = $('#bookButton');
    if (status === 'Accepted' || status === 'Pending' || status === 'Ongoing') {
        if (hoursDifference < 24) {
            cancelButton.prop('disabled', true).css('background-color', 'grey');
        } else {
            cancelButton.prop('disabled', false).css('background-color', '');
        }
        cancelButton.show();
        bookButton.hide();
    } else {
        cancelButton.hide();
        bookButton.show();
    }
}


function updateProgressNodes(status) {
    if (status === 'Pending') {
        $('#node1').addClass('active');
        $('#node2, #node3, #node4').removeClass('active');
        $('#line1, #line2, #line3').removeClass('active');
    } else if (status === 'Accepted') {
        $('#node1, #node2').addClass('active');
        $('#line1').addClass('active');
        $('#node3, #node4').removeClass('active');
        $('#line2, #line3').removeClass('active');
    } else if (status === 'Ongoing') {
        $('#node1, #node2, #node3').addClass('active');
        $('#line1').addClass('active');
        setTimeout(() => $('#line2').addClass('active'), 100);
        $('#node4').removeClass('active');
        $('#line3').removeClass('active');
    } else if (status === 'Completed') {
        $('#node1, #node2, #node3, #node4').addClass('active');
        $('#line1').addClass('active');
        setTimeout(() => $('#line2').addClass('active'), 100);
        setTimeout(() => $('#line3').addClass('active'), 100);
        setTimeout(() => $('#line3').addClass('active'), 100);
    }
}

function renderPagination(currentPage, lastPage, paginationWrapper, onPageClick) {
    const startPage = Math.max(1, currentPage - 1);
    const endPage = Math.min(lastPage, currentPage + 1);

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

function renderAppointmentRows(appointments, tableBody) {
    tableBody.empty();

    if (appointments.length === 0) {
        tableBody.html('<tr><td colspan="4">No Appointments Found</td></tr>');
        return;
    }

    appointments.forEach(({ appointment_date, formatted_time, procedures, status }) => {
        const row = `
            <tr>
                <td>${appointment_date}</td>
                <td>${formatted_time}</td>
                <td>${procedures}</td>
                <td>${status}</td>
            </tr>`;
        tableBody.append(row);
    });
}

function fetchAppointmentList(page = 1, search = '') {
    axios.post(`appointment/populate?page=${page}&search=${search}`)
        .then(response => {
            const { data: appointments, current_page, last_page } = response.data;
            const tableBody = $('#appointmentListTableBody');
            const paginationWrapper = $('#appointmentPagination');

            renderAppointmentRows(appointments, tableBody);
            renderPagination(current_page, last_page, paginationWrapper, (page) => {
                fetchAppointmentList(page);
            });
        })
        .catch(error => console.error('Error fetching appointments:', error));
}

function fetchCurrentAppointment() {
    axios.post('appointment/fetch')
        .then(response => {
            const appointments = response.data;

            if (Array.isArray(appointments) && appointments.length > 0) {
                const appointment = appointments[0];
                const { status, appointment_date_time, formatted_time, procedures, appointment_date, hours_difference } = appointment;
                updateProgressNodes(status);
                $('#dateText').text(appointment_date);
                $('#timeText').text(formatted_time);
                $('#serviceText').text(procedures);

                updateCancelButton(status, hours_difference);
            }
        })
        .catch(error => console.error('Error fetching appointments:', error));
}

$(document).ready(() => {
    resetCurrentAppointmentUI();
    fetchCurrentAppointment();
    fetchAppointmentList();
    setInterval(fetchCurrentAppointment, 3000);

    let selectedServices = [];

    $("#addServiceButton").click(function () {
        const selectedService = $("#service").val();

        if (selectedServices.length >= 2) {
            alert("You can only select up to 2 services.");
            return; 
        }

        if (selectedService && !selectedServices.includes(selectedService)) {
            selectedServices.push(selectedService);

            const listItem = `
                <li>
                    ${selectedService}
                    <button type="button" class="remove-btn" data-service="${selectedService}">Remove</button>
                </li>
            `;
            $("#serviceList").append(listItem);
    
            updateProceduresInput();
        } else if (selectedService) {
            alert("This service has already been selected.");
        }
    
        $("#service").val("");
    });
    $("#serviceList").on("click", ".remove-btn", function () {
        const serviceToRemove = $(this).data("service");

        selectedServices = selectedServices.filter(service => service !== serviceToRemove);

        $(this).parent().remove();

        updateProceduresInput();
    });

    function updateProceduresInput() {
        $("#procedures").val(selectedServices.join(", "));
    }


    $(document).on('click', '.pagination-link', function () {
        const page = $(this).data('page');
        const search = $('#searchInput').val() || '';
        if (page) {
            fetchAppointmentList(page, search);
        }
    });

    $('#searchInput').on('input', function () {
        const search = $(this).val();
        fetchAppointmentList(1, search); 
    });

    $(document).on('click', '#bookButton', function() {
        $('#add-errors').empty().hide();
        axios.get('/user/fetch/id')
            .then(response => {
                $('#user-appointment-id').val(response.data.id);
                $('#addModal').fadeIn().css('display', 'flex');
            })
            .catch(error => console.error('Error fetching user data:', error));
    });

    $(document).on('click', '#cancelButton', function() {
        $('#add-errors').empty().hide();

        axios.get('/user/fetch/id')
            .then(response => {
                $('#cancel-appointment-id').val(response.data.id);
                $('#cancelModal').fadeIn().css('display', 'flex');
            })
            .catch(error => console.error('Error fetching user data:', error));
    });

    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        $('#add-errors').empty().hide();

        const formData = new FormData(this);
        axios.post('/book/appointment', formData)
            .then(() => {
                $('#addModal').fadeOut();
                alert('Appointment booked successfully!');
                fetchCurrentAppointment();
                fetchAppointmentList();
            })
            .catch(error => {
                const errorMessage = error.response?.data?.message || 'An error occurred.';
                $('#add-errors').html(`<li>${errorMessage}</li>`).show();
            });
    });

    $('#cancelForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        axios.post('/cancel/appointment', formData)
            .then(() => {
                $('#cancelModal').fadeOut();
                alert('Appointment cancelled successfully!');
                fetchCurrentAppointment();
                fetchAppointmentList();
                resetCurrentAppointmentUI();
            })
            .catch(error => console.error('Error cancelling appointment:', error));
    });

    $('#add-close-modal').click(() => $('#addModal').fadeOut());
    $('#cancel-close-modal').click(() => $('#cancelModal').fadeOut());

    const dateInput = $('#date');
    const today = new Date();
    const minDate = new Date(today.setDate(today.getDate() + 3)).toISOString().split('T')[0];
    dateInput.attr('min', minDate);
});
