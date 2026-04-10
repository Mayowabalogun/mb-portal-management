(function () {
  function renderAlerts(alerts) {
    const wrapper = document.querySelector('[data-alert-ticker]');
    if (!wrapper) {
      return;
    }

    const inner = wrapper.querySelector('.ticker-inner');
    if (!inner || !Array.isArray(alerts) || alerts.length === 0) {
      return;
    }

    inner.innerHTML = '';
    alerts.forEach((alert) => {
      const li = document.createElement('li');
      const type = (alert.type || 'Alert').toString();
      const message = (alert.message || '').toString();
      const priority = (alert.priority || 'info').toString();
      const icon = priority === 'critical'
        ? 'bi-exclamation-octagon-fill text-danger'
        : (priority === 'warning' ? 'bi-exclamation-triangle-fill text-warning' : 'bi-info-circle-fill text-info');

      li.innerHTML = '<i class="bi ' + icon + '"></i> ' +
        '<strong>' + type.replace(/[<>]/g, '') + ':</strong> ' +
        message;
      inner.appendChild(li);
    });
  }

  async function loadAlerts() {
    try {
      const response = await fetch(BASE_URL + '/public/api/dashboard-alerts.php', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      if (!response.ok) {
        return;
      }
      const alerts = await response.json();
      renderAlerts(alerts);
    } catch (err) {
      // intentionally silent for non-blocking dashboard UX
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    loadAlerts();
    window.setInterval(loadAlerts, 30000);
  });
})();
