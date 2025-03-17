<nav>
    <div class="navigation">
        <div class="logo-container">
            <img src="{{ asset('images/index/logo.png') }}" alt="logo" width="100">
        </div>
        <div class="navigation-content">
            <ul>
                <li><a href="{{ url('/') }}">Home</a></li>
                <li><a href="{{ route('user.login') }}">Login <i class="fa-solid fa-user"></i></a></li>
                <li><a href="{{ route('user.register') }}">Register</a></li>
            </ul>
        </div>
    </div>
    <div class="burger-menu" onclick="toggleMenu()">
            <div class="line"></div>
            <div class="line"></div>
            <div class="line"></div>
        </div>
</nav>

<div class="sidebar">
    <ul>
        <li><a href="{{ url('/') }}">Home</a></li>
        <li><a href="{{ route('user.login') }}">Login <i class="fa-solid fa-user"></i></a></li>
        <li><a href="{{ route('user.register') }}">Register</a></li>
    </ul>
</div>

<script>
function toggleMenu() {
    document.querySelector('.burger.menu-icon').classList.toggle('open');
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>