<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>

    @vite(['resources/scss/header.scss', 'resources/scss/footer.scss', 'resources/scss/user/userregister.scss', 'resources/js/user/register.js'])
</head>

<body>
    @include('partials.header')
    <main>
        <div class="container">
            <div class="form">
                <div class="form-heading">
                    <h1>CREATE ACCOUNT</h1>
                </div>
                <div class="form-body">
                    <form id="registerForm" method="POST">
                        @csrf
                        <div class="form-control">
                            <h2>User Information</h2>
                        </div>
                        <div class="form-control">
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                                placeholder="First Name" @if ($errors->has('first_name')) autofocus @endif required>
                        </div>
                        <div class="form-control">
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"
                                placeholder="Last Name" @if ($errors->has('last_name')) autofocus @endif required>
                        </div>
                        <div class="form-control">
                            <div class="age">
                                <input type="number" id="age" name="age" value="{{ old('age') }}"
                                    placeholder="Age" @if ($errors->has('age')) autofocus @endif required>
                            </div>
                            <div class="phone-number">
                                <span class="input-group-addon">+63</span>
                                <input type="text" id="number" name="number" value="{{ old('number') }}"
                                    placeholder="Number" @if ($errors->has('number')) autofocus @endif required>
                            </div>
                        </div>
                        <div class="form-control">
                            <input type="text" id="street_address" name="street_address"
                                value="{{ old('street_address') }}" placeholder="Street Address"
                                @if ($errors->has('street_address')) autofocus @endif required>
                        </div>
                        <div class="form-control">
                            <select name="province" id="province" required>
                                <option value="">Select Province</option>
                                @foreach (App\Models\Province::orderBy('name')->get() as $province)
                                    <option value="{{ $province->id }}"
                                        {{ old('province') == $province->id ? 'selected' : '' }}>{{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="city" id="city" required>
                                <option value="">Select City</option>
                            </select>
                            <select name="country" id="country" required>
                                <option value="Philippines" default>Philippines</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <h2>Login Details</h2>
                        </div>
                        <div class="form-control">
                            <input type="text" id="username" name="username" value="{{ old('username') }}"
                                placeholder="Username" @if ($errors->has('username')) autofocus @endif
                                autocomplete="username" required>
                        </div>
                        <div class="form-control">
                            <div class="password-toggle">
                                <input type="password" id="password" name="password" placeholder="Password"
                                    @if ($errors->has('password')) autofocus @endif autocomplete="new-password"
                                    required>
                                <span toggle="#password" class="eye-toggle">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                        width="24px" fill="#e8eaed">
                                        <path
                                            d="M480-320q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm0 192q-146 0-266-81.5T40-500q54-137 174-218.5T480-800q146 0 266 81.5T920-500q-54 137-174 218.5T480-200Zm0-300Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z" />
                                    </svg>

                                    <!-- Custom SVG for visible off (hidden) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                        width="24px" fill="#e8eaed" style="display: none;">
                                        <path
                                            d="m644-428-58-58q9-47-27-88t-93-32l-58-58q17-8 34.5-12t37.5-4q75 0 127.5 52.5T660-500q0 20-4 37.5T644-428Zm128 126-58-56q38-29 67.5-63.5T832-500q-50-101-143.5-160.5T480-720q-29 0-57 4t-55 12l-62-62q41-17 84-25.5t90-8.5q151 0 269 83.5T920-500q-23 59-60.5 109.5T772-302Zm20 246L624-222q-35 11-70.5 16.5T480-200q-151 0-269-83.5T40-500q21-53 53-98.5t73-81.5L56-792l56-56 736 736-56 56ZM222-624q-29 26-53 57t-41 67q50 101 143.5 160.5T480-280q20 0 39-2.5t39-5.5l-36-38q-11 3-21 4.5t-21 1.5q-75 0-127.5-52.5T300-500q0-11 1.5-21t4.5-21l-84-82Zm319 93Zm-151 75Z" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="form-control">
                            <div class="password-toggle">
                                <input type="password" id="confirm_password" name="confirm_password"
                                    placeholder="Confirm Password" autocomplete="new-password" required>
                                <span toggle="#confirm_password" class="eye-toggle">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                        width="24px" fill="#e8eaed">
                                        <path
                                            d="M480-320q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm0 192q-146 0-266-81.5T40-500q54-137 174-218.5T480-800q146 0 266 81.5T920-500q-54 137-174 218.5T480-200Zm0-300Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z" />
                                    </svg>

                                    <!-- Custom SVG for visible off (hidden) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                        width="24px" fill="#e8eaed" style="display: none;">
                                        <path
                                            d="m644-428-58-58q9-47-27-88t-93-32l-58-58q17-8 34.5-12t37.5-4q75 0 127.5 52.5T660-500q0 20-4 37.5T644-428Zm128 126-58-56q38-29 67.5-63.5T832-500q-50-101-143.5-160.5T480-720q-29 0-57 4t-55 12l-62-62q41-17 84-25.5t90-8.5q151 0 269 83.5T920-500q-23 59-60.5 109.5T772-302Zm20 246L624-222q-35 11-70.5 16.5T480-200q-151 0-269-83.5T40-500q21-53 53-98.5t73-81.5L56-792l56-56 736 736-56 56ZM222-624q-29 26-53 57t-41 67q50 101 143.5 160.5T480-280q20 0 39-2.5t39-5.5l-36-38q-11 3-21 4.5t-21 1.5q-75 0-127.5-52.5T300-500q0-11 1.5-21t4.5-21l-84-82Zm319 93Zm-151 75Z" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="form-control">
                            <input type="checkbox" id="accepted" name="accepted" required><span> Do you accept
                                our</span> <a href="#"> Terms and Conditions</a>
                        </div>
                        <div class="form-control">
                            <button type="submit" id="submitbtn">Submit</button>
                        </div>
                        <div class="form-control">
                            <span>Already have an account?</span><a href="{{ route('user.login') }}"> Sign In</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('partials.footer')
</body>

</html>
