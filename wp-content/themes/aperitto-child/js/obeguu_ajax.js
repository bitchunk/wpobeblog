document.addEventListener('DOMContentLoaded', () => {
  // ドロップボックスアップローダー設定
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
      'obeguu_ajax'
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

  // プルダウンアクション設定
  const submit_button = document.querySelector('#obeguu-form');
  submit_button.addEventListener('submit', (event) => {
    const action = document.querySelector('#log-bulk-action-select')?.value;
    const actions = {
      'normalize': '更新',
      'delete': '削除',
    }
      obeguu_ajax_async(event, action, actions[action]);
  });

  // 最近のログリスト更新ボタン
  const current_anchor = document.querySelector('#obeguu-current');
  current_anchor.addEventListener('submit', (event) => {
    obeguu_ajax_async(event, 'recent', '最近のログリストの更新')
  });

  // Ajax送信共通処理
  async function obeguu_ajax_async(form, action, message) {
  // async function obeguu_ajax_async(event,message, requires) {
    try {
      event.preventDefault();
      const formData = new FormData(event.currentTarget);
      formData.append('action', 'obeguu_ajax');
      formData.append('nonce', obeguuUploader.nonce);

      formData.get('log-action').value = action;

      // if (requires) {
        // for (const req of requires.isArray ? requires : [requires]) {
      // if (!formData.get('log-action')) {
      // if (!formData.get(req)) {
        // return;
      // }
        // }
      // }
      // 更新・削除結果に応じて一覧を更新
      message = (message ?? '処理') + '中…';
      result.textContent =
      `${message}`;

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
          `${message}に失敗しました。`
        );
      }
      // 更新・削除結果に応じて一覧を更新
      message = (message ?? '処理') + 'しました。';
      result.textContent =
      `${data.data.passfiles.length}件${message}`;

      // リロード
      setTimeout(() => {
        window.location.reload();
      }, 1500);
    } catch (error) {
      result.textContent = error.message;
    }
  }
});
