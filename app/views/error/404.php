<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Sayfa Bulunamadı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .error-page {
            text-align: center;
            padding: 40px;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #dc3545;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .error-message {
            font-size: 24px;
            color: #6c757d;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="error-page">
                    <div class="error-code">404</div>
                    <div class="error-message">Sayfa Bulunamadı</div>
                    <p class="text-muted">Aradığınız sayfa bulunamadı veya taşınmış olabilir.</p>
                    <a href="/mercanpanel" class="btn btn-primary mt-3">
                        <i class="fas fa-home me-2"></i>
                        Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 