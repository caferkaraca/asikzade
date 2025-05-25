<?php
// admin_config.php session_start() yaptığı için burada tekrar çağırmaya gerek yok,
// ama config dosyasının varlığından emin olmak için require_once iyi bir pratik.
require_once 'admin_config.php';

$error_message = $_SESSION['admin_error_message'] ?? null;
unset($_SESSION['admin_error_message']); // Mesajı gösterdikten sonra temizle

// Eğer kullanıcı zaten admin olarak giriş yapmışsa ve admin yetkisine sahipse, dashboard'a yönlendir
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true &&
    isset($_SESSION['is_admin_user']) && $_SESSION['is_admin_user'] === true) { // Bu kontrol önemli
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - AŞIKZADE</title>
    <style>
        /* Stil kodlarınız aynı kalabilir */
        :root {
            --asikzade-content-bg: #fef6e6;
            --asikzade-green: #8ba86d;
            --asikzade-dark-green: #6a8252;
            --asikzade-dark-text: #2d3e2a;
            --asikzade-light-text: #fdfcf8;
            --asikzade-gray: #7a7a7a;
        }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--asikzade-content-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .login-container {
            background-color: #fff;
            padding: 40px 35px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .login-container img.logo {
            height: 70px;
            margin-bottom: 25px;
        }
        .login-container h1 {
            color: var(--asikzade-dark-text);
            margin-bottom: 30px;
            font-size: 1.9rem;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 22px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #555;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"] { /* E-posta için de aynı stil */
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--asikzade-green);
            box-shadow: 0 0 0 2px rgba(139, 168, 109, 0.2);
        }
        .login-btn {
            background-color: var(--asikzade-green);
            color: var(--asikzade-light-text);
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 25px;
            font-size: 1.05rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        .login-btn:hover {
            background-color: var(--asikzade-dark-green);
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size:0.9rem;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="https://i.imgur.com/rdZuONP.png" alt="Aşıkzade Logo" class="logo">
        <h1>Admin Paneli Girişi</h1>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form action="admin_login_process.php" method="POST">
            <div class="form-group">
                <label for="admin_email">E-posta Adresiniz</label>
                <input type="email" id="admin_email" name="admin_email" required value="<?php echo htmlspecialchars($_SESSION['form_data_admin_login']['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="admin_password">Şifreniz</label>
                <input type="password" id="admin_password" name="admin_password" required>
            </div>
            <button type="submit" class="login-btn">Giriş Yap</button>
        </form>
        <?php unset($_SESSION['form_data_admin_login']); ?>
    </div>
</body>
</html>