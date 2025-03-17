<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Gracious Smile - Login</title>

    @vite(['resources/scss/user/userregister.scss', 'resources/scss/footer.scss'])
</head>
<body>
    <main>
        <div class="container">
            <div class="form-default">
                <div class="form-heading">
                    <div class="heading-text">
                        <h1>CHANGE EMAIL</h1>
                    </div>
                </div>
                <div class="form-body">
                    <form action="" method="POST">
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
                        <div class="form-group-col">
                            <input type="email" id="email" name="email" placeholder="Email" @if($errors->has('email')) autofocus @endif required>
                        </div>  
                        <div class="form-group-col">
                            <div class="button">
                                <button type="submit" id="submitbtn">Submit</button>
                            </div>
                        </div>
                        <div class="form-group-row">
                            <a href="{{ route('user.login') }}">Go Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('partials.footer')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
