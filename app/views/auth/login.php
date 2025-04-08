<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Mercan Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 90%;
            margin: auto;
            padding: 0;
        }
        
        .login-header {
            background: #fff;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .login-header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }
        
        .login-header h1 {
            color: #1a1a1a;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating input {
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 15px;
            height: auto;
        }
        
        .form-floating label {
            padding: 15px;
        }
        
        .btn-login {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .copyright {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <!-- Logo için yer tutucu, src kısmını kendi logonuzla değiştirin -->
                <img src="/mercanpanel/public/img/logo.png" alt="Mercan Panel Logo" onerror="this.src='data:image/svg+xml;charset=UTF-8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'60\' viewBox=\'0 0 200 60\'><text x=\'50%\' y=\'50%\' font-size=\'24\' fill=\'%232563eb\' text-anchor=\'middle\' dominant-baseline=\'middle\'>Mercan Panel</text></svg>'">
                <h1>Yönetim Paneli</h1>
            </div>
            
            <div class="login-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/mercanpanel/login">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı Adı" required>
                        <label for="username">
                            <i class="fas fa-user me-2"></i>
                            Kullanıcı Adı
                        </label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>
                            Şifre
                        </label>
                    </div>

                    <button type="submit" class="btn btn-login btn-lg text-white">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Giriş Yap
                    </button>
                </form>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?= date('Y') ?> Mercan Panel - Tüm hakları saklıdır.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 