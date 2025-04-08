<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Sunucu Hatası</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="display-1">500</h1>
                <h2 class="mb-4">Sunucu Hatası</h2>
                <div class="alert alert-danger">
                    <p class="lead"><?php echo isset($message) ? $message : 'Bir sunucu hatası oluştu.'; ?></p>
                </div>
                <a href="/" class="btn btn-primary">Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>
</body>
</html> 