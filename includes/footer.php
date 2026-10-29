<?php
/**
 * Global App Footer
 */
?>

<?php if ($currentUser && !isset($hideAppShell)): ?>
            </main> <!-- /.app-content-body -->
        </div> <!-- /.app-main-content -->
    </div> <!-- /.app-shell -->

    <?php require_once __DIR__ . '/nav.php'; ?>
    <?php require_once __DIR__ . '/modals.php'; ?>
<?php endif; ?>

<!-- jQuery & Bootstrap 5 Bundle JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.all.min.js"></script>

<!-- Chart.js 4 JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- FullCalendar JS (Global) -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<!-- Custom App JS -->
<script src="assets/js/app.js"></script>

</body>
</html>
