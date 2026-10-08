<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị | Phòng khám Đa khoa Nhân Đức 3</title>
    @vite('resources/css/app.css')
    <style>
        .login-page{min-height:100vh;display:grid;place-items:center;padding:24px;background:linear-gradient(135deg,#eaf7fb,#f8fcfd)}
        .login-card{width:min(100%,410px);background:#fff;border:1px solid #dce8ee;border-radius:14px;padding:34px;box-shadow:0 18px 60px rgba(23,50,77,.1)}
        .login-brand{display:flex;align-items:center;gap:14px;color:#17324d;font:800 20px Manrope;margin-bottom:28px}.login-logo{width:126px;height:42px;object-fit:contain;display:block}
        .login-card h1{font:800 24px Manrope;margin:0 0 8px;color:#17324d}.login-card p{font-size:12px;color:#71869a;margin:0 0 25px}.login-form{display:grid;gap:16px}.login-form label{display:grid;gap:7px;font-size:11px;font-weight:700;color:#597587}.login-form input{border:1px solid #dce8ee;border-radius:7px;padding:12px;color:#17324d;outline:0}.login-form input:focus{border-color:#5db3cc;box-shadow:0 0 0 3px #e5f6f8}.login-button{border:0;border-radius:7px;padding:13px;background:#1677a8;color:#fff;font-weight:700;cursor:pointer}.login-error{padding:10px;border-radius:7px;background:#fbe7e5;color:#b75b55;font-size:11px}.login-note{margin-top:20px!important;font-size:10px!important;line-height:1.5}
    </style>
</head>
<body>
    <main class="login-page">
        <section class="login-card">
            <div class="login-brand"><img class="login-logo" src="{{ asset('images/SSH logo.png') }}" alt="Logo Phòng khám Đa khoa Nhân Đức 3"><span>Phòng khám Đa khoa Nhân Đức 3</span></div>
            <h1>Đăng nhập quản trị</h1>
            <p>Chỉ dành cho nhân viên được cấp quyền.</p>
            @if ($errors->any())
                <div class="login-error">{{ $errors->first() }}</div>
            @endif
            <form class="login-form" method="POST" action="{{ route('clinic.admin.login.submit') }}">
                @csrf
                <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
                <label>Mật khẩu<input type="password" name="password" autocomplete="current-password" required></label>
                <button class="login-button" type="submit">Đăng nhập</button>
            </form>
            <p class="login-note">Phiên đăng nhập được bảo vệ bằng session Laravel và tự hủy khi bạn đăng xuất.</p>
        </section>
    </main>
</body>
</html>
