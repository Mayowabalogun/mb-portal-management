(function () {
  const modalElement = document.getElementById('vacateModal');
  if (!modalElement || typeof bootstrap === 'undefined') {
    return;
  }

  const modal = new bootstrap.Modal(modalElement);
  const rentIdInput = document.getElementById('vacate-rent-id');
  const tenantNode = document.getElementById('vacate-tenant');
  const propertyNode = document.getElementById('vacate-property');
  const unitNode = document.getElementById('vacate-unit');

  document.querySelectorAll('.js-open-vacate').forEach((button) => {
    button.addEventListener('click', () => {
      rentIdInput.value = button.dataset.rentId || '';
      tenantNode.textContent = button.dataset.tenantName || '—';
      propertyNode.textContent = button.dataset.property || '—';
      unitNode.textContent = button.dataset.unit || '—';
      modal.show();
    });
  });
})();
