<nav>
    <div class="navigation">
        <div class="logo-container">
            <img src="{{ asset('images/index/logo.png') }}" alt="logo" width="100">
        </div>
        <div class="navigation-content">
            <ul>
                <li><a href="{{ route('/')}}">Home</a></li>
                <li><span><a href="{{ route('user.login')}}">Login <i class="fa-solid fa-user"></i></a></span></li>
                <li><span><a href=" {{ route('user.register')}}">Register</a></span></li>
            </ul>
        </div>
    </div>
</nav>