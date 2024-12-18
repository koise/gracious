import $ from 'jquery';
import axios from 'axios';

$(document).ready(function () {
    function clearErrors() {
        $('.text-danger').remove();
    }

    $('#loginForm').on('submit', function (e) {
        e.preventDefault();

        clearErrors();

        const formData = new FormData(this);

        axios
            .post('/admin/authenticate', formData)
            .then(function (response) {
                window.location.href = '/admin/dashboard';
            })
            .catch(function (error) {
                if (error.response && error.response.data.errors) {
                    const errors = error.response.data.errors;
                    let errorMessages =
                        '<div class="form-group-col"><ul class="text-danger">';
                    for (const key in errors) {
                        errorMessages += `<li>${errors[key][0]}</li>`;
                    }
                    errorMessages += '</ul></div>';
                    $('#loginForm').prepend(errorMessages);
                }
            });
    });

    $('.eye-toggle').click(function () {
        const toggle = $($(this).attr('toggle'));
        const svgVisible = $(this).find('svg:first-child');
        const svgHidden = $(this).find('svg:last-child');

        if (toggle.attr('type') == 'password') {
            toggle.attr('type', 'text');
            svgVisible.css('display', 'none');
            svgHidden.css('display', 'block');
        } else {
            toggle.attr('type', 'password');
            svgVisible.css('display', 'block');
            svgHidden.css('display', 'none');
        }
    });

    $('#password').on('input', function () {
        $(this).val($(this).val().replace(/\s/g, ''));
    });
});
