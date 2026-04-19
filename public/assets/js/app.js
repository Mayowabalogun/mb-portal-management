document.addEventListener('DOMContentLoaded', function () {
  if (window.jQuery && $.fn.DataTable) {
    const table = document.querySelector('table.data-table');
    if (table) {
      $(table).DataTable();
    }
  }
});
