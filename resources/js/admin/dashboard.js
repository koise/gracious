import $ from 'jquery';
import Chart from 'chart.js/auto';
import axios from 'axios';

$(document).ready(function () {
    // Update demographic data
    const updateDemographic = () => {
        axios.get('/admin/demographic-data')
            .then(response => {
                const { totalAppointments, appointmentsToday, totalUsers, totalDoctors, totalPending, totalMissed, totalAccepted, totalRejected, totalOngoing, totalCompleted, totalCancelled } = response.data;
                $('#appointmentsToday').text(appointmentsToday);
                $('#totalAppointments').text(totalAppointments);
                $('#totalMissed').text(totalMissed);
                $('#totalCompleted').text(totalCompleted);
                $('#totalUsers').text(totalUsers);
                $('#totalDoctors').text(totalDoctors);
            })
            .catch(error => {
                console.error('Error fetching demographic data:', error);
            });
    };

    // Update line chart
    const updateLineChart = (filter = 'less_than_a_week') => {
        axios.get(`/admin/line-chart-data/${filter}`)
            .then(response => {
                const data = response.data;
                console.log('Line Chart Data:', data);

                const lineChartData = {
                    labels: data.labels,
                    datasets: [
                        {
                            label: '',
                            data: data.appointmentCounts,
                            fill: false,
                            backgroundColor: 'rgb(10, 109, 157)',
                            borderColor: 'rgb(10, 109, 157)',
                            tension: 0.1
                        }
                    ]
                };

                if (window.lineChart && typeof window.lineChart.destroy === 'function') {
                    window.lineChart.destroy();
                }

                const ctx = $('#lineChart')[0].getContext('2d');
                    window.lineChart = new Chart(ctx, {
                        type: 'bar',
                        data: lineChartData,
                        options: {
                            animation: { duration: 1000, easing: 'easeInOutQuart' },
                            plugins: {
                                legend: {
                                    display: false // Disable legend entirely
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function () {
                                            return ''; // Disable tooltip title
                                        },
                                        label: function (context) {
                                            const value = context.raw;
                                        const total = data.appointmentCounts.reduce((acc, curr) => acc + curr, 0);
                                        const percentage = ((value / total) * 100).toFixed(2);
                                        return `Appointments: ${value} (${percentage}%)`; // Customize tooltip if needed
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1, // Ensures steps of 1 on the y-axis
                                        callback: function (value) {
                                            return Number.isInteger(value) ? value : null; // Show only integers
                                        }
                                    }
                                }
                            }
                        }
                    });
            })
            .catch(error => {
                console.error('Error fetching line chart data:', error);
            });
    };

    // Update doughnut chart
    const updateDoughnutChart = (filter = 'less_than_a_week') => {
        axios.get(`/admin/doughnut-chart-data/${filter}`)
            .then(response => {
                const { doughnutData } = response.data;

                const statusColors = {
                    "Missed/Cancelled/Rejected": 'rgb(255, 99, 132)',
                    "Completed": 'rgb(10, 109, 157)',
                    "Accepted/Ongoing": 'rgb(255, 205, 86)',
                    "Pending": 'rgb(128, 128, 128)'
                };

                const labels = Object.keys(doughnutData);
                const data = Object.values(doughnutData);
                const backgroundColors = labels.map(label => statusColors[label] || 'rgb(200, 200, 200)');

                if (window.doughnutChart) window.doughnutChart.destroy();

                const ctx = document.getElementById('pieChart').getContext('2d');
                window.doughnutChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Appointments',
                                data: data,
                                backgroundColor: backgroundColors,
                                hoverOffset: 4
                            }
                        ]
                    },
                    options: {
                        animation: { duration: 1000, easing: 'easeInOutQuart' },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const value = context.raw;
                                        const percentage = ((value / data.reduce((sum, val) => sum + val, 0)) * 100).toFixed(2);
                                        return `${context.label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching doughnut chart data:', error);
            });
    };

    // Event listener for line chart filter dropdown
    $('#lineChartFilter').on('change', function () {
        const selectedFilter = $(this).val();
        updateLineChart(selectedFilter);
    });

    $('#pieChartFilter').on('change', function () {
        const selectedFilter = $(this).val();
        updateDoughnutChart(selectedFilter);
    });


    // Initial data load
    updateDemographic();
    updateLineChart();
    updateDoughnutChart();

    // Periodic updates for demographic and doughnut chart data
    setInterval(() => {
        updateDemographic();
        updateDoughnutChart();
    }, 60000);
});
