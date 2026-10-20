document.addEventListener('DOMContentLoaded', () => {
  // const paging_year = document.querySelector('[name=obeguu-year]');
  const form = document.querySelector('#obeguu-form');
  const select = document.querySelectorAll('[name=obeguu-year],[name=obeguu-month]');
  select.forEach((select) => select.addEventListener('change', (event) => {
    const formData = new FormData(form);
    let monthQuery = '&obeguu-month=' + (formData.get('obeguu-month') ?? '1');

    window.location.href = 'admin.php?page=oberin_logs_manage&obeguu-year=' + formData.get('obeguu-year') + monthQuery;
  }));
});
