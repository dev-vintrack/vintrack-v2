@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/vintrack/datatables-1.13.6.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/vintrack/datatables-buttons-2.4.2.bootstrap5.min.css') }}">
<style>.vintrack-table-toolbar .dt-buttons{display:inline-flex;flex-wrap:wrap;gap:.5rem}.vintrack-table-toolbar .dt-button{border:0!important;border-radius:.375rem!important;color:#fff!important;font-weight:600}.vintrack-table-toolbar .buttons-excel{background:#198754!important}.vintrack-table-toolbar .buttons-pdf{background:#dc3545!important}.vintrack-table-toolbar .dataTables_filter input{min-width:220px}</style>
@endpush
@push('scripts')
<script src="{{ asset('vendor/vintrack/jquery-3.7.1.min.js') }}"></script><script src="{{ asset('vendor/vintrack/datatables-1.13.6.min.js') }}"></script><script src="{{ asset('vendor/vintrack/datatables-1.13.6.bootstrap5.min.js') }}"></script><script src="{{ asset('vendor/vintrack/datatables-buttons-2.4.2.min.js') }}"></script><script src="{{ asset('vendor/vintrack/datatables-buttons-2.4.2.bootstrap5.min.js') }}"></script><script src="{{ asset('vendor/vintrack/jszip-3.10.1.min.js') }}"></script><script src="{{ asset('vendor/vintrack/pdfmake-0.2.7.min.js') }}"></script><script src="{{ asset('vendor/vintrack/pdfmake-0.2.7-vfs_fonts.js') }}"></script><script src="{{ asset('vendor/vintrack/datatables-buttons-2.4.2.html5.min.js') }}"></script>
@endpush
