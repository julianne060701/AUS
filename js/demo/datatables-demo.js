// Call the dataTables jQuery plugin
$(document).ready(function() {
  // Wait for DataTable plugin to be available
  function initializeDataTable() {
    if (typeof $.fn.DataTable !== 'undefined' && $('#dataTable').length > 0) {
      // Only initialize if not already initialized and if it's a basic table (not custom)
      if (!$.fn.DataTable.isDataTable('#dataTable') && $('#dataTable').hasClass('basic-datatable')) {
        $('#dataTable').DataTable();
      }
    } else if (typeof $.fn.DataTable === 'undefined') {
      // If DataTable is not loaded yet, wait a bit and try again
      setTimeout(initializeDataTable, 100);
    }
  }
  
  initializeDataTable();
});
