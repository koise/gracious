<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>

    @vite(['resources/scss/user/userregister.scss', 
    'resources/scss/footer.scss',
    'resources/js/user/verification.js'])
</head>
<body>
    <main>
        <div class="container">
            <div class="form-default">
                <div class="form-heading">
                    <div class="heading-text">
                        <h1>FORGOT PASSWORD</h1>
                    </div>
                </div>
                <div class="form-body">
    
                    {{-- Form to Send OTP --}}
                    <form id="otpForm" method="POST" >
                        @csrf
                        @if ($errors->any())
                        <div class="form-group-col">
                            <ul class="text-danger">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <div class="form-group-row">
                            <div class="phone-number">
                                <span class="input-group-addon">+63</span>
                                <input type="text" id="number" name="number" 
                                value="{{ session('number') ?? old('number') }}" 
                                placeholder="Number" 
                                @if($errors->has('number')) autofocus @endif required>
                            </div>
                            <button type="submit" id="generateBtn">Send OTP</button>
                        </div>
                    </form>
    
                    {{-- Form to Verify OTP --}}
                    <form id="verificationOTP" method="POST">
                        @csrf
                        <input type="hidden" name="number" id="otpNumber">
                        <div class="form-group-col">
                            <input type="text" id="otp" name="otp" placeholder="Enter OTP">
                        </div>
                        <div class="form-group-col">
                            <div class="button">
                                <button type="submit" id="verifyBtn">Submit</button>
                            </div>
                        </div>
                    </form>
    
                    <div class="form-group-row">
                        <a href="{{ route('user.login') }}">Go Back</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('partials.footer')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
