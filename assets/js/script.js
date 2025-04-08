// Sayfa yüklendiğinde çalışacak fonksiyonlar
$(document).ready(function() {
    // Tooltip'leri aktifleştir
    $('[data-toggle="tooltip"]').tooltip();
    
    // Popover'ları aktifleştir
    $('[data-toggle="popover"]').popover();
    
    // Otomatik kapanan alert'leri kapat
    $('.alert').not('.alert-permanent').delay(5000).fadeOut(500);
    
    // Form doğrulama
    $('form').on('submit', function() {
        var form = $(this);
        if (form[0].checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.addClass('was-validated');
    });
    
    // Dosya yükleme önizleme
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
    
    // Tablo sıralama
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
            },
            "responsive": true,
            "autoWidth": false
        });
    }
    
    // Summernote editör
    if ($.fn.summernote) {
        $('.summernote').summernote({
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview']]
            ]
        });
    }
    
    // Select2
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4'
        });
    }
    
    // Tarih seçici
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'dd.mm.yyyy',
            language: 'tr',
            autoclose: true,
            todayHighlight: true
        });
    }
    
    // Zaman seçici
    if ($.fn.timepicker) {
        $('.timepicker').timepicker({
            showMeridian: false,
            minuteStep: 5
        });
    }
    
    // Modal kapatma
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form').trigger('reset');
        $(this).find('form').removeClass('was-validated');
        if ($.fn.summernote) {
            $(this).find('.summernote').summernote('reset');
        }
        if ($.fn.select2) {
            $(this).find('.select2').val(null).trigger('change');
        }
    });
    
    // Sidebar toggle
    $('#sidebarToggle').on('click', function(e) {
        e.preventDefault();
        $('.sidebar').toggleClass('active');
        $('.main-content').toggleClass('active');
    });
    
    // Sayfa yükleme göstergesi
    $(window).on('beforeunload', function() {
        showLoading();
    });
});

// AJAX istekleri için genel ayarlar
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Dosya yükleme fonksiyonu
function uploadFile(file, url, progressCallback, successCallback, errorCallback) {
    var formData = new FormData();
    formData.append('file', file);
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var percent = Math.round((e.loaded / e.total) * 100);
                    progressCallback(percent);
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            successCallback(response);
        },
        error: function(xhr, status, error) {
            errorCallback(error);
        }
    });
}

// Bildirim gösterme fonksiyonu
function showNotification(message, type = 'success') {
    var icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    var title = type === 'success' ? 'Başarılı!' : 'Hata!';
    
    var toast = `
        <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
            <div class="toast-header">
                <i class="fas fa-${icon} mr-2 text-${type}"></i>
                <strong class="mr-auto">${title}</strong>
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    $('.toast-container').append(toast);
    $('.toast').toast('show');
}

// Sayfa yenileme fonksiyonu
function refreshPage() {
    window.location.reload();
}

// Modal açma fonksiyonu
function openModal(modalId) {
    $('#' + modalId).modal('show');
}

// Modal kapatma fonksiyonu
function closeModal(modalId) {
    $('#' + modalId).modal('hide');
}

// Form temizleme fonksiyonu
function clearForm(formId) {
    $('#' + formId).trigger('reset');
    $('#' + formId).removeClass('was-validated');
    if ($.fn.summernote) {
        $('#' + formId + ' .summernote').summernote('reset');
    }
    if ($.fn.select2) {
        $('#' + formId + ' .select2').val(null).trigger('change');
    }
}

// Tarih formatlama fonksiyonu
function formatDate(date) {
    if (!date) return '';
    var d = new Date(date);
    return ('0' + d.getDate()).slice(-2) + '.' +
           ('0' + (d.getMonth() + 1)).slice(-2) + '.' +
           d.getFullYear();
}

// Zaman formatlama fonksiyonu
function formatTime(date) {
    if (!date) return '';
    var d = new Date(date);
    return ('0' + d.getHours()).slice(-2) + ':' +
           ('0' + d.getMinutes()).slice(-2);
}

// Para formatlama fonksiyonu
function formatMoney(amount, currency = '₺') {
    return parseFloat(amount).toLocaleString('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' ' + currency;
}

// Dosya boyutu formatlama fonksiyonu
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// URL parametrelerini alma fonksiyonu
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// Sayfa yükleme göstergesi
function showLoading() {
    if (!$('.loading').length) {
        $('body').append('<div class="loading"><div class="loading-spinner"></div></div>');
    }
    $('.loading').fadeIn(200);
}

function hideLoading() {
    $('.loading').fadeOut(200);
}

// Hata yakalama
window.onerror = function(msg, url, line, col, error) {
    console.error('Hata:', {
        message: msg,
        url: url,
        line: line,
        column: col,
        error: error
    });
    return false;
}; 
 