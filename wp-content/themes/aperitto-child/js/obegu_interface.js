document.addEventListener('DOMContentLoaded', () => {
  // const paging_year = document.querySelector('[name=obegu-year]');
  const form = document.querySelector('#obegu-form');
  const select = document.querySelectorAll('[name=obegu-year],[name=obegu-month]');
  select.forEach((select) => select.addEventListener('change', (event) => {
    const formData = new FormData(form);
    let monthQuery = '&obegu-month=' + (formData.get('obegu-month') ?? '1');

    window.location.href = 'admin.php?page=oberin_logs_manage&obegu-year=' + formData.get('obegu-year') + monthQuery;
  }));
});
