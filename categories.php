<?php
/**
 * Categories Management Screen
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_auth();

$pageTitle = "Custom Categories";
$user = current_user();
$userId = $user['id'];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="btn-icon-circle text-decoration-none">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h3 class="fw-bold mb-0" style="letter-spacing: -0.4px;">Categories</h3>
    </div>
    <button class="btn btn-sm btn-primary-smart px-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fa-solid fa-plus me-1"></i> Add Category
    </button>
</div>

<!-- Type Filter Pills -->
<div class="filter-pills-row mb-3">
    <div class="pills-group">
        <button class="filter-pill cat-type-pill active" data-type="">All</button>
        <button class="filter-pill cat-type-pill" data-type="expense">Expense</button>
        <button class="filter-pill cat-type-pill" data-type="income">Income</button>
    </div>
</div>

<!-- Category Grid Container -->
<div id="categoriesGridContainer" class="row g-2 mb-4">
    <div class="col-12 text-center py-5 text-muted">
        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        <span class="ms-2">Loading categories...</span>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-tags text-warning me-2"></i> Create Custom Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Gym & Fitness, Client Retainer" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category Type</label>
                        <select name="type" class="form-select">
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">FontAwesome Icon</label>
                            <input type="text" name="icon" class="form-control" value="fa-tag" placeholder="fa-dumbbell">
                            <small class="text-muted" style="font-size: 0.7rem;">e.g. fa-tag, fa-dumbbell, fa-book</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Color Theme</label>
                            <input type="color" name="color" class="form-control form-control-color w-100" value="#FFA726">
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" id="saveCatBtn" class="btn btn-primary-smart py-2">
                            <i class="fa-solid fa-plus me-1"></i> Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentType = '';

    $('.cat-type-pill').on('click', function() {
        $('.cat-type-pill').removeClass('active');
        $(this).addClass('active');
        currentType = $(this).data('type');
        loadCategories();
    });

    function loadCategories() {
        const container = $('#categoriesGridContainer');
        container.html(`
            <div class="col-12 text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                <span class="ms-2">Loading categories...</span>
            </div>
        `);

        $.getJSON('api/categories.php', { action: 'list', type: currentType }, function(res) {
            if (!res.success) {
                container.html(`<div class="col-12 alert alert-danger">${res.message}</div>`);
                return;
            }

            let html = '';
            for (const cat of res.categories) {
                const isCustom = (cat.user_id !== null);
                const deleteBtn = isCustom 
                    ? `<button class="btn btn-link text-danger p-0 delete-cat-btn" data-id="${cat.id}"><i class="fa-solid fa-trash-can small"></i></button>`
                    : `<span class="badge bg-light text-muted border" style="font-size: 0.65rem;">System</span>`;

                html += `
                    <div class="col-12 col-sm-6 col-md-4 col-xl-3" id="cat-card-${cat.id}">
                        <div class="app-card p-3 mb-0 h-100 d-flex align-items-center justify-content-between" style="border-radius: 18px;">
                            <div class="d-flex align-items-center gap-2 text-truncate">
                                <div class="category-icon-box flex-shrink-0" style="background-color: ${cat.bg_color || '#FFF3E0'}; color: ${cat.color || '#FF9F43'}; width: 42px; height: 42px; font-size: 1rem;">
                                    <i class="fa-solid ${cat.icon || 'fa-tag'}"></i>
                                </div>
                                <div class="text-truncate">
                                    <div class="fw-bold small text-truncate text-capitalize">${escapeHtml(cat.name)}</div>
                                    <span class="badge ${cat.type === 'expense' ? 'badge-soft-danger' : 'badge-soft-success'}" style="font-size: 0.65rem;">${cat.type.toUpperCase()}</span>
                                </div>
                            </div>
                            <div class="ms-2">
                                ${deleteBtn}
                            </div>
                        </div>
                    </div>
                `;
            }

            container.html(html);
        });
    }

    // Save Category
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#saveCatBtn');
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

        const formData = new FormData(this);
        formData.append('action', 'add');

        $.ajax({
            url: 'api/categories.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#addCategoryModal').modal('hide');
                    $('#categoryForm')[0].reset();
                    loadCategories();
                } else {
                    toastr.error(res.message);
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-plus me-1"></i> Save Category');
            }
        });
    });

    // Delete Custom Category
    $(document).on('click', '.delete-cat-btn', function() {
        const id = $(this).data('id');
        confirmAction('Delete Custom Category?', 'Transactions under this category will become Uncategorized.').then(res => {
            if (res.isConfirmed) {
                $.post('api/categories.php', { action: 'delete', id: id }, function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        $(`#cat-card-${id}`).remove();
                    } else {
                        toastr.error(resp.message);
                    }
                });
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"'`=\/]/g, function (s) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;',
                '`': '&#x60;',
                '=': '&#x3D;'
            }[s];
        });
    }

    loadCategories();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
