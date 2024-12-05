import $ from 'jquery';
import axios from 'axios';

$(document).ready(function () {
    function clearErrors() {
        $('.text-danger').remove();
    }

    $('.eye-toggle').click(function () {
        const toggle = $($(this).attr('toggle'));
        const svgVisible = $(this).find('svg:first-child');
        const svgHidden = $(this).find('svg:last-child');

        if (toggle.attr('type') === 'password') {
            toggle.attr('type', 'text');
            svgVisible.hide();
            svgHidden.show();
        } else {
            toggle.attr('type', 'password');
            svgVisible.show();
            svgHidden.hide();
        }
    });

    $('#registerForm').on('submit', function (e) {
        e.preventDefault();

        clearErrors();

        const formData = $(this).serialize(); // Serialize form data

        axios.post('/account/register/process', formData)
            .then(function (response) {
                if (response.data.number) {
                    alert('Registration successful!');
                    window.location.href =  `/account/verification/${response.data.number}`;
                } else {
                    console.error('Phone number is undefined.');
                    alert('Failed to retrieve phone number!');
                }
            })
            .catch(function (error) {
                if (error.response && error.response.data.errors) {
                    const errors = error.response.data.errors;
                    let errorMessages = '<ul class="text-danger">';
                    for (const key in errors) {
                        errorMessages += `<li>${errors[key][0]}</li>`;
                    }
                    errorMessages += '</ul>';
                    $('#registerForm').prepend(errorMessages);
                }
            });
    });

    $('#age').on('input', function () {
        $(this).val($(this).val().replace(/\D/g, '').slice(0, 2)); // Remove non-digits and limit to 2 digits
    });

    $('#number').on('input', function () {
        $(this).val($(this).val().replace(/\D/g, '').slice(0, 11)); // Remove non-digits and limit to 11 digits
    });

    $('#province').on('change', function () {
        const selectedProvinceId = $(this).val();
        const citySelect = $('#city');

        citySelect.html('<option value="">Select City</option>');

        if (selectedProvinceId) {
            axios.get(`/account/cities/${selectedProvinceId}`)
                .then(function (response) {
                    response.data.forEach(function (city) {
                        citySelect.append(`<option value="${city.id}">${city.name}</option>`);
                    });
                })
                .catch(function (error) {
                    console.error('Error fetching cities:', error);
                });
        }
    });
});
