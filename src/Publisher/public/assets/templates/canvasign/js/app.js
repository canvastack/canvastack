/* Canvasign App — shared interactions */

// Global error handler for Bootstrap JSON parse errors
window.addEventListener('error', function(event) {
    if (event.error && 
        event.error.message && 
        event.error.message.includes('JSON.parse') &&
        event.error.stack && 
        event.error.stack.includes('bootstrap')) {
        
        console.warn('⚠️ Caught Bootstrap JSON parse error (non-critical)');
        event.preventDefault();
        return true;
    }
});

document.addEventListener('DOMContentLoaded', () => {

  /* ── Bootstrap tooltips ─────────────────────────────────────────────── */
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });

  /* ── Toggle password visibility ─────────────────────────────────────── */
  document.querySelectorAll('[data-toggle-password]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.querySelector(btn.dataset.togglePassword);
      if (!target) return;
      const isPwd = target.type === 'password';
      target.type = isPwd ? 'text' : 'password';
      const icon = btn.querySelector('i');
      if (icon) icon.className = isPwd ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });

  /* ── Bootstrap form validation ──────────────────────────────────────── */
  document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', e => {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

});
