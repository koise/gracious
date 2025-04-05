<div class="topbar">
    <div class="leftside-navigation">
        <ul>
            <li><img src="{{ url('images/index/logo.png') }}" alt=""></li>
            <li>
                <a href="{{ route('user.dashboard') }}" class="{{ Route::is('user.dashboard') ? 'active' : '' }}">
                    Appointment
                </a>
            </li>
            <li>
                <a href="{{ route('user.record') }}" class="{{ Route::is('user.record') ? 'active' : '' }}">
                    Records
                </a>
            </li>
            <li>
            <a href="{{ route('user.payment') }}" class="{{ Route::is('user.payment') ? 'active' : '' }}">
                Payment Transaction
            </a>
            </li>
        </ul>
    </div>
    <div class="rightside-navigation">
        <span>Account</span>
        <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px" fill="#e8eaed">
            <path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Z"/>
        </svg>
        <div class="account">
            <ul>
                <li><a href="{{ route('user.profile') }}">Profile</a></li>
                <li><a href="">Change Password</a></li>
                <li><a href="{{ route('user.logout') }}">Logout</a></li>
            </ul>
        </div>
    </div>
    <div class="burger menu-icon" onclick="toggleMenu()">
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
    </div>
</div>
<div class="sidebar">
<ul>
         
            <li>
                <a href="{{ route('user.dashboard') }}" class="{{ Route::is('user.dashboard') ? 'active' : '' }}">
                    Appointment
                </a>
            </li>
            <li>
                <a href="{{ route('user.record') }}" class="{{ Route::is('user.record') ? 'active' : '' }}">
                    Records
                </a>
            </li>
            <li>
                <a href="{{ route('user.payment') }}" class="{{ Route::is('user.payment') ? 'active' : '' }}">
                    Payment Transaction
                </a>
            </li>
        @auth
                <li><a href="{{ route('user.profile') }}">Profile</a></li>
                <li><a href="">Change Password</a></li>
                <li><a href="{{ route('user.logout') }}">Logout</a></li>
            </ul>
        @endauth
</div>
<script>
function toggleMenu() {
    document.querySelector('.burger.menu-icon').classList.toggle('open');
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>