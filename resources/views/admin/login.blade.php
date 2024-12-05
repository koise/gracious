<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admin Login</title>

    @vite(['resources/scss/admin/adminlogin.scss', 'resources/scss/footer.scss', 'resources/scss/modal.scss'])
</head>
<body>
    <div class="container">
            <div class="form-default">
                <div class="form-heading">
                    <div class="heading-text">
                        <h1>ADMIN LOGIN</h1>
                    </div>
                </div>
                <div class="form-body">
                    <form action="{{ route('admin.authenticate') }}" method="POST">
                        @csrf
                        @if ($errors->any())
                            <div class="form-control">
                                <ul class="text-danger">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="form-control">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username"  value="{{ old('username') }}" @if($errors->has('username')) autofocus @endif required>
                        </div>
                        <div class="form-control">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" @if($errors->has('password')) autofocus @endif required>
                        </div>  
                        <div class="form-control">
                            <div class="button">
                                <button type="submit">Log In</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
    </div>
    @include('partials.footer')
</body>
</html>