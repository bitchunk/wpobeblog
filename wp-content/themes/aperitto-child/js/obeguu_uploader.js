document.addEventListener('DOMContentLoaded', () => {
  const dropzone = document.querySelector('#log-drop-area');
  const result = document.querySelector('#log-upload-result');

  if (!dropzone) {
    return;
  }

  dropzone.addEventListener('dragover', (event) => {
    event.preventDefault();

    dropzone.classList.add('is-dragover');
  });

  dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('is-dragover');
  });

  dropzone.addEventListener('drop', async (event) => {
    event.preventDefault();

    dropzone.classList.remove('is-dragover');

    // ここでドロップされたファイルを取得する
    const files = Array.from(event.dataTransfer.files);

    if (files.length === 0) {
      return;
    }

    result.textContent = 'アップロード中です。';

    const formData = new FormData();

    formData.append(
      'action',
      'obelin_log_upload'
    );

    formData.append(
      'nonce',
      obeguuUploader.nonce
    );

    // filesはここで定義済みなので使用できる
    for (const file of files) {
      formData.append('files[]', file);
    }

    try {
      const response = await fetch(
        obeguuUploader.ajaxUrl,
        {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        }
      );

      const data = await response.json();

      if (!data.success) {
        throw new Error(
          data.data?.message ||
          'アップロードに失敗しました。'
        );
      }

      result.textContent =
      `${data.data.passfiles.length}件アップロードしました。`;

      // リロード
      setTimeout(() => {
        window.location.reload();
      }, 1000);

    } catch (error) {
      result.textContent = error.message;
    }
  });

  const submit_button = document.querySelector('#obeguu-form');
  submit_button.addEventListener('submit', async (event) => {
    const action = document.querySelector('#log-bulk-action-select')?.value;

    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    formData.append('action', 'obelin_log_upload');
    formData.append('nonce', obeguuUploader.nonce);

    // 何もしない
    if (!formData.get('log-action')) {
      return;
    }
    try {
      const actions = {
        'normalize': '更新',
        'delete': '削除',
      }
      const response = await fetch(
        obeguuUploader.ajaxUrl,
        {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        }
      );
      const data = await response.json();

      if (!data.success) {
        throw new Error(
          data.data?.message ||
          `${actions}に失敗しました。`
        );
      }
      // 更新・削除結果に応じて一覧を更新
      debugger
      const message = (actions[formData.get('log-action')] ?? '処理') + 'しました。';
      result.textContent =
      `${data.data.passfiles.length}件${message}`;

      // リロード
      setTimeout(() => {
        window.location.reload();
      }, 1000);
    } catch (error) {
      result.textContent = error.message;
    }
  });
});
