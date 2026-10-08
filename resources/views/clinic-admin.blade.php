<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quản trị lịch hẹn | Phòng khám Đa khoa Nhân Đức 3</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin-catalog.js', 'resources/js/admin-slots.js'])
    <style>
        .catalog-panel{max-width:1200px;margin:0 auto 24px;padding:28px;background:#fff;border:1px solid #dce8ee;border-radius:14px}.catalog-heading{margin-bottom:20px}.catalog-heading h2{font:700 22px Manrope;margin:7px 0}.catalog-heading p,.catalog-card-heading small{font-size:11px;color:#71869a}.catalog-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.catalog-card{border:1px solid #e2edf0;border-radius:10px;padding:18px}.catalog-card-heading{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:15px}.catalog-card-heading h3{font:700 15px Manrope;margin:0 0 4px}.catalog-form{display:grid;gap:9px;padding-bottom:18px;border-bottom:1px solid #e8f0f2}.catalog-form input{width:100%;border:1px solid #dce8ee;border-radius:6px;padding:9px 10px;font-size:11px;color:#17324d}.catalog-form input:focus{outline:0;border-color:#5db3cc}.form-row{display:flex;gap:9px;align-items:center}.form-row input{flex:1}.check-label{display:flex;align-items:center;gap:6px;color:#668391;font-size:11px;font-weight:500}.check-label input{width:auto}.catalog-form .button{width:max-content}.catalog-list{display:grid;gap:7px;margin-top:15px}.catalog-item{display:flex;align-items:center;gap:8px;min-height:44px;padding:7px 0;border-bottom:1px solid #edf2f4}.catalog-icon,.doctor-avatar{width:30px;height:30px;display:grid;place-items:center;border-radius:8px;background:#eaf7fb;color:#1677a8;font-weight:700}.doctor-avatar{border-radius:50%;background:#e5f4ed;color:#438b6a;font-size:12px}.catalog-item-copy{flex:1;min-width:0}.catalog-item-copy strong,.catalog-item-copy small{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.catalog-item-copy strong{font:700 11px Manrope}.catalog-item-copy small{font-size:10px;color:#71869a;margin-top:3px}.mini-status{padding:4px 7px;border-radius:12px;background:#e2f4e9;color:#31875e;font-size:9px;font-weight:700}.mini-status.off{background:#fbe7e5;color:#b75b55}.icon-action,.text-button{background:transparent;color:#668391;padding:3px;font-size:13px}.icon-action.danger{color:#c56b67}.text-button{font-size:11px;color:#1677a8}.catalog-toast{position:fixed;right:24px;bottom:24px;background:#17324d;color:#fff;border-radius:7px;padding:12px 16px;font-size:12px;z-index:9}.catalog-toast.error{background:#b85f5a}@media(max-width:800px){.catalog-grid{grid-template-columns:1fr}.catalog-panel{margin:0 15px 20px;padding:18px}}
    </style>
</head>
<body data-clinic-view="admin">
    <div id="clinic-app"></div>
    <div id="clinic-catalog"></div>
    <div id="clinic-slots"></div>
</body>
</html>