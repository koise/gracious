import axios from 'axios';
document.addEventListener('DOMContentLoaded', function () {

function fetchAppointments() {
    axios.post('/admin/appointment/populate')
        .then(response => {
            const appointments = response.data;
            const tableBody = document.getElementById('appointmentTableBody');
            tableBody.innerHTML = '';
            appointments.forEach(appointment => {
                const appointmentDate = new Date(appointment.appointment_date).toLocaleDateString('en-CA');
                const createdAt = new Date(appointment.created_at).toLocaleDateString('en-CA');
                const row = `
                    <tr>
                        <td>${appointment.id}</td>
                        <td>${appointment.name}</td>
                        <td>${appointmentDate}</td>
                        <td>${appointment.status}</td>
                        <td>${appointment.service}</td>
                        <td>${createdAt}</td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        })
        .catch(error => {
            console.error('There was an error fetching the pending appointments!', error);
        });
}

    fetchAppointments();
});