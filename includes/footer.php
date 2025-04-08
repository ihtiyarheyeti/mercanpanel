            </div> <!-- main-content end -->
        </div> <!-- content-wrapper end -->
    </div> <!-- wrapper end -->
    
    <!-- JavaScript Dosyaları -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/script.js"></script>
    
    <script>
        // Bootstrap tooltips'i aktifleştir
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Alert'leri otomatik kapat
        var alertList = document.querySelectorAll('.alert');
        alertList.forEach(function (alert) {
            setTimeout(function() {
                var closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            }, 5000);
        });
    </script>
</body>
</html> 
 