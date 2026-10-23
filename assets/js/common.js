// Shared page helpers.
document.addEventListener('click', event => {
  const control = event.target.closest('[data-confirm]');
  if (control && !window.confirm(control.dataset.confirm || 'Are you sure?')) event.preventDefault();
});
function printPage() { window.print(); }
