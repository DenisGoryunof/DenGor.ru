document.addEventListener('DOMContentLoaded', () => {
  const t = document.querySelector('.nav-toggle');
  if (t) t.addEventListener('click', () => document.querySelector('.main-nav').classList.toggle('open'));
});