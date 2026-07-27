@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<style>
    .customer-datatable-toolbar .dt-buttons {
        display: inline-flex !important;
        flex-wrap: wrap;
        gap: .5rem;
        width: auto !important;
    }
    .customer-datatable-toolbar .dt-buttons .customer-export-button {
        flex: 0 0 auto !important;
        width: auto !important;
        min-width: 105px;
        padding: .375rem .75rem;
        color: #fff !important;
        font-weight: 600;
        line-height: 1.25;
    }
    .customer-datatable-toolbar .dt-buttons .customer-export-excel {
        background-color: #198754 !important;
        border-color: #198754 !important;
    }
    .customer-datatable-toolbar .dt-buttons .customer-export-excel:hover,
    .customer-datatable-toolbar .dt-buttons .customer-export-excel:focus {
        background-color: #146c43 !important;
        border-color: #146c43 !important;
        color: #fff !important;
    }
    .customer-datatable-toolbar .dt-buttons .customer-export-pdf {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    .customer-datatable-toolbar .dt-buttons .customer-export-pdf:hover,
    .customer-datatable-toolbar .dt-buttons .customer-export-pdf:focus {
        background-color: #b02a37 !important;
        border-color: #b02a37 !important;
        color: #fff !important;
    }
    .customer-datatable-toolbar .dataTables_filter { margin: 0; }
    .customer-datatable-toolbar .dataTables_filter input { min-width: 220px; }
    .dataTables_wrapper .dataTables_paginate .pagination { margin-bottom: 0; }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script>
$(function () {
    const table = $('#{{ $tableId }}').DataTable({
        responsive: true,
        ordering: true,
        searching: true,
        paging: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
        order: @json($order),
        autoWidth: false,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
            emptyTable: @json($emptyMessage),
            search: 'Búsqueda rápida:'
        },
        dom: "<'customer-datatable-toolbar row g-3 align-items-center px-3 pt-3'<'col-lg-6'B><'col-lg-6'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row g-3 align-items-center px-3 py-3'<'col-md-5'i><'col-md-7 d-flex justify-content-md-end'p>>",
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                className: 'customer-export-button customer-export-excel btn-sm',
                title: @json($exportTitle),
                filename: @json($exportFilename),
                exportOptions: { columns: @json($exportColumns) }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                className: 'customer-export-button customer-export-pdf btn-sm',
                title: @json($exportTitle),
                filename: @json($exportFilename),
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: { columns: @json($exportColumns) }
            }
        ]
    });

    $('[data-refresh-table="#{{ $tableId }}"]').on('click', function () {
        window.location.reload();
    });
});
</script>
@endpush
