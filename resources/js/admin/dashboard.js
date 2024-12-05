import Chart from 'chart.js/auto';
import axios from 'axios';


document.addEventListener('DOMContentLoaded', () => {
    
    // function to update demographic on dashboard
    const updateDemographic = () => {
        axios.get('/admin/demographic-data')
            .then(response => {
                const { appointmentsToday, totalUsers, totalDoctors } = response.data;
                document.getElementById('appointmentsToday').innerText = appointmentsToday;
                document.getElementById('totalUsers').innerText = totalUsers;
                document.getElementById('totalDoctors').innerText = totalDoctors;
            })
            .catch(error => {
                console.error('Error fetching demographic data:', error);
            });
    };

    // function to update charts on dashboard
    const updateCharts = () => {
        axios.get('/admin/chart-data')
            .then(response => {
                const { labels, appointmentCounts, doughnutData } = response.data;

                // Update line chart (Appointments by day)
                const lineChartData = {
                    labels: labels,
                    datasets: [{
                        label: 'Appointments per day (current month)',
                        data: appointmentCounts,
                        fill: false,
                        borderColor: 'rgb(10, 109, 157)',
                        tension: 0.1
                    }]
                };

                var lineChart = new Chart(document.getElementById('lineChart'), {
                    type: 'line',
                    data: lineChartData,
                    options: {
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Update doughnut chart (Appointments status)
                const doughnutChartData = {
                    labels: Object.keys(doughnutData),
                    datasets: [{
                        label: 'Appointments',
                        data: Object.values(doughnutData),
                        backgroundColor: [
                            'rgb(255, 205, 86)',
                            'rgb(10, 109, 157)',
                            'rgb(255, 99, 132)',
                            'rgb(128,128,128)'
                        ],
                        hoverOffset: 4
                    }]
                };

                var pieChart = new Chart(document.getElementById('pieChart'), {
                    type: 'doughnut',
                    data: doughnutChartData,
                    options: {
                        animation: {
                            duration: 1000, 
                            easing: 'easeInOutQuart'
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching chart data:', error);
            });
    };

    updateDemographic();
    updateCharts();

    setInterval(() => {
        updateDemographic();
        updateCharts();
    }, 60000);
});
