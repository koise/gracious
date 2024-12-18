import $ from 'jquery';
import axios from 'axios';

$(document).ready(function () {
    function clearErrors() {
        $('.text-danger').remove();
    }
    const $termsCheckbox = $("#terms");
        const $termsModal = $("#termsModal");
    $("#terms, #termsLabel").click(function(e) {
        if (!$termsCheckbox.prop("checked")) {
            $termsModal.css("display", "flex");
        } else {
            $termsCheckbox.prop("checked", false);
        }
    });
    $('.close-button').click(function() {
        $termsModal.css("display", "none");
        $termsCheckbox.prop("checked", false);
    });

    $('#confirmButton').click(function() {
        $termsModal.addClass("fade-out");
        setTimeout(function() {
            $termsModal.css("display", "none").removeClass("fade-out");
            $termsCheckbox.prop("checked", true);
            $termsCheckbox.prop("disabled", false);
        }, 300);
    });
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

        var passwordValid = $('#password-validation-message').text();
        var confirmValid = $('#confirm-password-validation-message').text();

        if (passwordValid !== confirmValid) {
            return; // Exit early if passwords are not valid
        }

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

    $('#first_name, #last_name').on('input', function () {
        $(this).val($(this).val().replace(/[^A-Za-z0-9]/g, '')); // Allow only alphanumeric characters
    });

    $('#username').on('input', function () {
        $(this).val($(this).val().replace(/[^A-Za-z0-9!@_]/g, '')); // Allow alphanumeric and specific special characters
    });

    $('#number').on('input', function () {
        $(this).val($(this).val().replace(/\D/g, '').slice(0, 11)); // Remove non-digits and limit to 11 digits
    });

    $('#password, #confirm_password').on('input', function() {
        var password = $('#password').val();
        var confirm_password = $('#confirm_password').val();

        $(this).val($(this).val().replace(/\s/g, ''));
        // Validation for main password
        var lowercaseRegex = /[a-z]/;
        var uppercaseRegex = /[A-Z]/;
        var digitRegex = /\d/;
        var specialCharRegex = /[!@_]/;
        var passwordValid = true;
        var passwordMessage = '';

        if (password === '') {
            $('#password-validation-message').text('');
        } else if (!lowercaseRegex.test(password)) {
            passwordValid = false;
            passwordMessage = 'Password must contain at least one lowercase letter.';
        } else if (!uppercaseRegex.test(password)) {
            passwordValid = false;
            passwordMessage = 'Password must contain at least one uppercase letter.';
        } else if (!digitRegex.test(password)) {
            passwordValid = false;
            passwordMessage = 'Password must contain at least one digit.';
        } else if (!specialCharRegex.test(password)) {
            passwordValid = false;
            passwordMessage = 'Password must contain at least one special character (!@_).';
        } else if (password.length < 8) {
            passwordValid = false;
            passwordMessage = 'Password must be at least 8 characters long.';
        }

        // Update validation message for main password
        $('#password-validation-message').text(passwordMessage);
        $('#password-validation-message').css('color', passwordValid ? 'green' : 'red');

        // Validation for confirm password
        var confirmValid = true;
        var confirmMessage = '';

        if (password !== '') {
            if (confirm_password !== password) {
                confirmValid = false;
                confirmMessage = 'Passwords do not match.';
            }
        } else {
            confirmValid = false;
            confirmMessage = 'Please input your password first.';
        }

        // Update validation message for confirm password
        if (confirm_password.length > 0) {
            $('#confirm-password-validation-message').text(confirmMessage);
            $('#confirm-password-validation-message').css('color', confirmValid ? 'green' : 'red');
        } else {
            $('#confirm-password-validation-message').text('');
        }
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
