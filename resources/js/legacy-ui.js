// resources/js/legacy-ui.js
document.addEventListener("DOMContentLoaded", () => {
  const tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipEls.forEach((el) => new bootstrap.Tooltip(el))

  const popoverEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
  popoverEls.forEach((el) => new bootstrap.Popover(el))
})
