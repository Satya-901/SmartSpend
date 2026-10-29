/**
 * SmartSpend - Frontend Application Script
 * Powered by jQuery, Bootstrap 5, Toastr, SweetAlert2, and Chart.js
 */

// Toastr Global Settings
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": true,
    "showDuration": "300",
    "hideDuration": "500",
    "timeOut": "3500",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

// Global CSRF Token Header for all jQuery AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Helper for SweetAlert confirmation
function confirmAction(title, text, confirmButtonText = 'Yes, Proceed!') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FF9F43',
        cancelButtonColor: '#A39B92',
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-4'
        }
    });
}

$(document).ready(function() {

    // Initialize Flatpickr if inputs exist
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y"
        });
    }

    // -------------------------------------------------------------
    // Quick Add Expense Form
    // -------------------------------------------------------------
    $('#addExpenseForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: 'api/transactions.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#quickAddModal').modal('hide');
                    $('#addExpenseForm')[0].reset();
                    // Refresh data or reload if needed
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON;
                toastr.error(res ? res.message : 'Error adding expense.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-plus me-1"></i> Save Expense');
            }
        });
    });

    // -------------------------------------------------------------
    // Quick Add Income Form
    // -------------------------------------------------------------
    $('#addIncomeForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: 'api/transactions.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#quickAddModal').modal('hide');
                    $('#addIncomeForm')[0].reset();
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON;
                toastr.error(res ? res.message : 'Error adding income.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-plus me-1"></i> Save Income');
            }
        });
    });

    // -------------------------------------------------------------
    // Quick Pay EMI Form
    // -------------------------------------------------------------
    $('#quickEmiSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const defaultAmt = selected.data('amount');
        if (defaultAmt) {
            $('#quickEmiAmount').val(defaultAmt);
        }
    });

    $('#quickPayEmiForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'record_payment');

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: 'api/emis.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#quickAddModal').modal('hide');
                    $('#quickPayEmiForm')[0].reset();
                    setTimeout(() => location.reload(), 900);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON;
                toastr.error(res ? res.message : 'Error recording EMI payment.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Record EMI Payment');
            }
        });
    });

    // -------------------------------------------------------------
    // Receipt Modal Viewer
    // -------------------------------------------------------------
    $(document).on('click', '.view-receipt-btn', function(e) {
        e.preventDefault();
        const fileUrl = $(this).data('file');
        const modalBody = $('#receiptModalBody');
        
        if (fileUrl.toLowerCase().endsWith('.pdf')) {
            modalBody.html(`<iframe src="${fileUrl}" width="100%" height="500px" frameborder="0"></iframe>`);
        } else {
            modalBody.html(`<img src="${fileUrl}" class="img-fluid rounded-3 shadow-sm" alt="Receipt">`);
        }

        $('#receiptModal').modal('show');
    });

    // -------------------------------------------------------------
    // Delete Transaction Action
    // -------------------------------------------------------------
    $(document).on('click', '.delete-tx-btn', function(e) {
        e.preventDefault();
        const txId = $(this).data('id');

        confirmAction('Delete Transaction?', 'Are you sure you want to delete this transaction record?').then((result) => {
            if (result.isConfirmed) {
                $.post('api/transactions.php', { action: 'delete', id: txId }, function(res) {
                    if (res.success) {
                        toastr.success(res.message);
                        $(`#tx-item-${txId}`).slideUp(300, function() { $(this).remove(); });
                    } else {
                        toastr.error(res.message);
                    }
                }).fail(function(xhr) {
                    toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Deletion failed.');
                });
            }
        });
    });

    // -------------------------------------------------------------
    // Layout Mode Switcher (Mobile centered frame vs Expanded)
    // -------------------------------------------------------------
    $('#layoutToggleBtn').on('click', function(e) {
        e.preventDefault();
        $('body').toggleClass('container-expanded');
        const isExpanded = $('body').hasClass('container-expanded');
        document.cookie = "app_layout_mode=" + (isExpanded ? 'expanded' : 'standard') + ";path=/;max-age=" + (30*86400);
        toastr.info(isExpanded ? "Expanded desktop view enabled." : "Mobile app view enabled.");
    });
});

