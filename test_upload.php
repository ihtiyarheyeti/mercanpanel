<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dosya Yükleme Testi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="container">
        <h3>Dosya Yükleme Testi</h3>
        
        <!-- Normal Input -->
        <div class="mb-4">
            <h5>1. Normal Input</h5>
            <input type="file" class="form-control">
        </div>

        <!-- Custom File Input -->
        <div class="mb-4">
            <h5>2. Özel Tasarımlı Input</h5>
            <div class="input-group">
                <label class="btn btn-primary">
                    Dosya Seç
                    <input type="file" style="display: none;" onchange="updateFileName(this)">
                </label>
                <span class="form-control" id="fileName">Dosya seçilmedi</span>
            </div>
        </div>

        <!-- Direct Button Trigger -->
        <div class="mb-4">
            <h5>3. Buton ile Tetikleme</h5>
            <input type="file" id="hiddenFileInput" style="display: none;">
            <button class="btn btn-secondary" onclick="document.getElementById('hiddenFileInput').click()">
                Dosya Seç
            </button>
        </div>
    </div>

    <script>
    function updateFileName(input) {
        document.getElementById('fileName').textContent = 
            input.files.length > 0 ? input.files[0].name : 'Dosya seçilmedi';
    }
    </script>
</body>
</html> 