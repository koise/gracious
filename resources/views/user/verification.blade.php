<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Gracious Smile - Login</title>

    @vite(['resources/scss/header.scss', 'resources/scss/user/userregister.scss', 'resources/scss/footer.scss', 'resources/js/user/verification.js'])
</head>

<body>
    @include('partials.header')
    <main>
        <div class="container">
            <div class="form">
                <div class="form-heading">
                    <div class="heading-text">
                        <h1>Verification</h1>
                    </div>
                </div>
                <div class="form-body">

                    {{-- Form to Send OTP --}}
                    <form id="otpForm" method="POST">
                        @csrf
                        <div class="form-control">
                            <div class="phone-number">
                                <span class="input-group-addon">+63</span>
                                <input type="text" id="number" name="number"
                                    value="{{ $phoneNumber ?? old('number') }}" placeholder="Number" required>
                            </div>
                            <button type="submit" id="generateBtn">Send OTP</button>
                        </div>
                    </form>

                    {{-- Form to Verify OTP --}}
                    <form id="verificationOTP" method="POST">
                        @csrf
                        <input type="hidden" name="number" id="otpNumber">
                        <div class="form-control">
                            <input type="text" id="otp" name="otp" placeholder="Enter OTP" required>
                        </div>
                        <div class="form-control">
                            <button type="submit" id="verifyBtn">Submit</button>
                        </div>
                        <div class="form-control">
                            <a href="{{ route('user.login') }}">Go Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('partials.footer')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $('#number').on('input', function() {
            $(this).val($(this).val().replace(/\D/g, ''));

            var maxDigits = 11;
            if ($(this).val().length > maxDigits) {
                $(this).val($(this).val().slice(0, maxDigits));
            }
        });
        $('#otp').on('input', function() {
            $(this).val($(this).val().replace(/\D/g, ''));
            var maxDigits = 6;
            if ($(this).val().length > maxDigits) {
                $(this).val($(this).val().slice(0, maxDigits));
            }
        });
    </script>
</body>

</html>
