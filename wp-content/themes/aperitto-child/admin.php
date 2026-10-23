<?php
// $talk_reg = '/^\(+.+\)+\:\s/';
$talk_reg = '/^\(+[^()]+\)+:\s/';
$login_reg = '/^Welcome\./';
$normalize_words = [
  // 作業開始
  ['/^You\sstart\s([\w\s]+)\s.+/', 'start'], // Mining / lumberjucking
  ['/^You\sbegin\s.+/', 'start'], // Fishing/Making/Crafting/Cooking/Carving/Mixing

  // 作業結果
  ['/^You\ssuccessfully\s([\w\s(+)]+)\s.+/', 'result'], // Making/Crafting/Cooking/Carving/Mixing

  ['/^You\scan\'t\ssee\syour\starget\./', 'result'], // Taming
  ['/^You\sfail\sto\smine\sany\sone\./', 'result'], // Mining
  ['/^You\sfail\sto\sharvest\sany\slogs\./', 'result'], // Lumberjucking
  ['/^You\sfail\sto\scatch\sany\sfish\./', 'result'], // Fishing

  ['/^You\sfail\sto\scraft\s([\w\s(+)]+)\s?.+/', 'result'], // Crafting
  ['/^You\sfail\sto\scook\sthe\sfood\.+/', 'result'], // Cooking
  ['/^You\sfail\sto\scarve\sthe\sshaft\.+/', 'result'], // Carving
  ['/^You\sfail\sto\smix\s([\w\s]+)\s?.+/', 'result'], // Mixing

  ['/^You\scontinue\s([\w\s]+)\s.+/', 'result'],  // minning/lumberjucking

  ['/^You\smust\starget\sa\srock\sfor\sore\./', 'result'], // Mining
  ['/^You\smust\starget\sa\stree\sto\sharvest\swood\./', 'result'], // Lumberjucking
  ['/^You\smust\sbe\sstanding\snear\swater\sto\sfish\./', 'result'], // Fishing
  ['/^You\smust\sbe\sstanding\snear\sa\sforge\./', 'result'], // Blacksmithing
  ['/^You\smust\sbe\sstanding\snear\sa\sfire\./', 'result'], // Cooking
  ['/^You\sdon\'t\shave\senough\smana\sto\scast\sthat\sspell\./', 'result'],  // Mixing
  ['/^You\sdon\'t\shave\senough\sreagents\./', 'result'],  // Mixing

  ['/^There\sis\snothing\s([\w\s]+)\s.+/', 'result'],  // hervesting

  //アクション、その場でまとめて表示
  ['/^You\sfail\sto\s([\w\s]+)\s?.+/', 'action'], // any
  ['/^That\screature\s([\w\s]+)\s.+/', 'action'], // Anatomy/Taming/Healing
  ['/^You\smust\starget\sa\screature\s([\w\s]+)\s?\.$/', 'action'], // Anatomy/Taming/Healing/Identifing
  ['/^You\sare\s([\w\s]+)\s?\.$/', 'action'], // Hidding/hunger

  ['/^You\sapply\sthe\sbandages\./', 'action'], // healing
  ['/^Your\swounds\sare\sbeing\sbandaged\./', 'action'], // healing
  ['/^You\sdon\'t\shave\sany\sbandages\./', 'action'],  // Healing

  ['/^You\sare\sunable\sto\s([\w\s]+)\s.+/', 'action'], // Anatomy

  ['/^You\senter\sa\smeditative\strance\.\.\./', 'action'], // Meditation

  ['/^Hidden\screatures\sare\srevealed\./', 'action'], // Detect hiddeing
  ['/^No\shidden\screatures\sare\sdetected\./', 'action'], // Detect hiddeing

  ['/^You\shave\s.+/', 'action'], // Lock picking
  ['/^That\scontainer\s([\w\s]+)\s.+/', 'action'], // Lock picking/Removing trap
  ['/^You\sare\sbeginning\s([\w\s]+)\s.+/', 'action'], // Eating

  ['/^You\smust\sbe\sstanding\son\sa\s([\w\s]+)\scontainer\./', 'action'], // Lock picking/Removing trap

  ['/^The\sspell\sfizzles\./', 'action'], // Spelling

  ['/^Dropped:\s+([\w\s(+):]+)$/', 'action'], //Dropping
  ['/^Sold:\s+([\w\s(+):]+)$/', 'action'], // Selling
  ['/^Taken([\w\s]+)?:\s+([\w\s(+)]+)+/', 'action'], // Taking
  ['/^Given([\w\s]+)?:\s+([\w\s(+)]+)+/', 'action'], // Giving
  ['/^Bought:\s+([\w\s(+):]+)$/', 'action'], //Buying
  ['/^Paid:\s+([\w\s]+)$/', 'action'], //Paying
  ['/^Caught:\s+([\w\s]+)$/', 'action'], //Fishing

  //インベントリ、その場でまとめて表示
];
//Workを停止したと思われる会話のカウント数
define('OBELIN_LOG_NORMALIZE_TALK_COUNT', 8);

// ログを開封処理
function obelin_log_unpack($files, $i) {
  return [
    $files['name'][$i],
    $files['type'][$i],
    $files['tmp_name'][$i],
    $files['error'][$i],
    $files['size'][$i],
  ];
}

// ログを正規化(utf-8)処理
function obelin_log_normalize($text) {
  //SJIS -> UTF-8
  $text = mb_convert_encoding($text, 'UTF-8', 'SJIS');
  // $from_encoding = mb_detect_encoding($text, 'ASCII, ISO-2022-JP, UTF-8, EUC-JP, SJIS, UTF-8', true);
  // var_dump($from_encoding);
  $text = preg_replace("/\r\n|\r|\n/u", "\n", $text);

  $textl = explode("\n", $text);
  $text_normalized = [];
  $work_text_stack = []; // 会話まじり作業
  $action_text_stack = []; // 即決作業
  $work_start = [];// 会話まじり作業の連続開始情報
  $action_start = [];// 即時結果作業の連続開始情報
  $lasti = count($textl);
  $talk_count = 0;

  // 一行ずつ処理
  global $normalize_words;
  global $login_reg;
  foreach($textl as $i => $t) {
    $last_line = $i +1 == $lasti;
    $is_login = preg_match($login_reg, $t);
    $is_action = false;

    //作業内容
    $is_normalize = false;
    foreach($normalize_words as $v) {
      list($word, $type) = $v;
      if (preg_match($word, $t, $match)) {
        $start_line = count($text_normalized); // 作業開始行
        if (isset($match[0])) {
          if ($type == 'start') {
            //作業開始を記録
            if (empty($work_start[$t])){
              $work_start[$t] = $start_line;
            }
            $work_text_stack[] = $t; // 作業のスタック
          } elseif ($type == 'result') {
            $work_text_stack[] = $t; // 結果のスタック
          } elseif ($type == 'action'){
            if (empty($action_start[$t])){
              $action_start[$t] = $start_line;
              // var_dump($start_line, $t);
            }
            $action_text_stack[] = $t; // 行動のスタック
            $is_action = true;

          }
        }
        $talk_count = 0;
        $is_normalize = true;
        break;
      }
    }
    if ($is_normalize) {
      continue;
    }

    // 会話内容
    if (!empty($work_text_stack)) {
      //作業中に発生した会話をカウント
      $talk_count += 1;
    }
    $force_output = $is_login ||  $last_line; // 強制出力

    // アクションをストップしたと思われる会話のカウントにて結果を出力
    if (!empty($action_text_stack) && ($talk_count == OBELIN_LOG_NORMALIZE_TALK_COUNT || $force_output)) {
      // $inserts = ['[**Actions result**]'];
      $inserted = 0; // 挿入した行をカウント
      foreach(array_count_values($action_text_stack) as $w => $num) {
        $wn = $num > 1 ? "- $w (x $num)" : "- $w";
        // $inserts[] = $wn;
        array_splice($text_normalized, $action_start[$w] + $inserted, 0, $wn);
        $inserted += 1;
      }
      $action_start = [];
      $action_text_stack = []; //作業スタックをリセット
    }

    //作業をストップしたと思われる会話のカウントにて結果を出力
    if (!empty($work_text_stack) && ($talk_count == OBELIN_LOG_NORMALIZE_TALK_COUNT || $force_output)){
      $insert_start = [];
      $insert_result = [];
      $insert_index_start = null;
      foreach(array_count_values($work_text_stack) as $w => $num) {
        $wn = $num > 1 ? "- $w (x $num)" : "- $w";
        if (isset($work_start[$w])) {
          $insert_start[] = $wn;
          $insert_index_start = $insert_index_start ?? ($work_start[$w] + 1);
        } else {
          $insert_result[] = $wn;
        }
        $inserts = [];
      }
      if (!empty($insert_start)) {
        // var_dump('start', $insert_start);
        array_unshift($insert_start, '[**Works start**]');
        array_splice($text_normalized, $insert_index_start, 0, $insert_start);
      }
      if (!empty($insert_result)) {
        // var_dump('result',$insert_result);
        array_unshift($insert_result, '[**Works result**]');
        array_splice($text_normalized, count($text_normalized), 0, $insert_result);
      }

      $work_start = [];
      $work_text_stack = []; //作業スタックをリセット
      $talk_count = 0;
    }
    $text_normalized[] = $t;
  }

  return implode("\n", $text_normalized);
}

// 正規化ログの保存処理
function obelin_log_save($name, $text_raw, $text_nz) {
  $obeguu_path = get_obeguu_path($name);
  // var_dump($obeguu_path, $log_date);

  $raw_dir = trailingslashit($obeguu_path). 'raw';
  $nz_dir = trailingslashit($obeguu_path). 'normalize';

  if (!(wp_mkdir_p($raw_dir) && wp_mkdir_p($nz_dir))) {
    wp_send_json_error(array('message' => '保存先を作成できませんでした。'), 500);
    return false;
  }

  $raw_hta = trailingslashit($raw_dir). '.htaccess';
  if (!file_exists($raw_hta) && !file_put_contents($raw_hta, 'AddCharset SJIS .txt')) {
    wp_send_json_error(array('message' => '.htaccessを作成できませんでした。'), 500);
    return false;
  }
  $nz_hta = trailingslashit($nz_dir). '.htaccess';
  if (!file_exists($nz_hta) && !file_put_contents($nz_hta, 'AddCharset UTF-8 .txt')) {
    wp_send_json_error(array('message' => '.htaccessを作成できませんでした。'), 500);
    return false;
  }


  $raw_path = trailingslashit($raw_dir). $name;
  $nz_path = trailingslashit($nz_dir). $name;
  if (!preg_match('/\.txt$/i', $nz_path)) {
    $nz_path = $nz_path. '.txt';
  }
  if (!(file_put_contents($raw_path, $text_raw) && file_put_contents($nz_path, $text_nz))) {
    wp_send_json_error(array('message' => 'ファイルを保存できませんでした。'), 500);
    return false;
  }
  return [$name, basename($raw_path), basename($nz_path)];
}

// ログの更新日など記録処理（エラーでメッセージを返す）
function obelin_log_date_update($name, $raw_name, $nz_name) {
  if (!isset($raw_name) && !isset($nz_name)) {
    return false;
  }

  // // すでに作成済みのはず
  $obeguu_path = get_obeguu_path($name);
  $date_path = $obeguu_path. 'logdate.csv';
  $date_pre_path = $obeguu_path. 'logdate_pre.csv';
  // ./oberin-logs/2005/logdate.txt

  // 読み込み元ファイルがなければ生成
  if (!file_exists($date_path) && file_put_contents($date_path, '') === false) {
    return '読み込み元CSVファイルの生成失敗';
  }

  // リネームに失敗したファイルの削除
  if (file_exists($date_pre_path) && unlink($date_pre_path) === false) {
    return '(未処理)書き込み先CSVファイルの削除失敗';
  }

  // 書き込み先ファイルを生成
  if (file_put_contents($date_pre_path, '') === false) {
    return '書き込み先CSVファイルの生成失敗';
  }
  // 現在の日付取得
  $upload_date = date('Y-m-d H:i:s');
  $update_date = date('Y-m-d H:i:s');

  $input = fopen($date_path, 'r');
  if ($input === false) {
    return '読み込み先CSVファイルの読み込み失敗';
  }
  $output = fopen($date_pre_path, 'w');
  if ($output === false) {
    return '書き込みCSVファイルの読み込み失敗';
  }

  // $is_update = false;
  $rows = [];
  while (($row = fgetcsv($input)) !== false) { // date_path
    if ($row[0] == $name) {
      if (empty($nz_name)) {
        $nz_name = $row[1]; // 正規化ファイル名を保持
        $upload_date = $row[2]; // 追加日を保持
      }
      // 更新・削除するときも該当行は一旦空にしておく
      $row = [];
    }
    // それ以外で記載のある行を追加
    if (!empty($row[0])) {
      $rows[] = $row; // date_pre_path
    }
  }

  // ログファイルを日付を追記（更新含む）
  if ($raw_name && $nz_name) {
    $rows[] = [
      isset($raw_name) ? $raw_name : '-',
      isset($nz_name) ? $nz_name : '-',
      $upload_date, // upload_date
      $update_date, // update_date
      obeguu_date_parse($name)["month"],// month
    ];
  }
  // var_dump($rows);

  usort($rows, function($a, $b) {
    if ($a[0] === false || $b[0] === false) {
      return strnatcasecmp($a[0] ?? '', $b[0] ?? '');
    }
    $da = obeguu_date_parse($a[0]);
    $db = obeguu_date_parse($b[0]);

    $dateA = strtotime("{$da['year']}-{$da['month']}-{$da['day']} 0:0:0" );
    $dateB = strtotime("{$db['year']}-{$db['month']}-{$db['day']} 0:0:0");
    return $dateA <=> $dateB;
  });
  // var_dump($rows);
  // raw_file_name, normalize_file_name, upload_date, update_date, month
  foreach($rows as $row) {
    fputcsv($output, $row);
  }

  // logdateから対象の日付を書き換えるor追記する
  fclose($input);
  fclose($output);

  if (unlink($date_path) === false) {
    return '読み込み元CSVファイルの削除失敗';
  }
  if (rename($date_pre_path, $date_path) === false) {
    return '書き込み先CSVファイルのリネーム失敗';
  }

  // var_dump($rows, $obeguu_path);
  // 年代フォルダが空だったらファルダごと消す
  if (empty($rows)) {
    if (rrmdir($obeguu_path) === false) {
      return '年代フォルダの削除失敗';
    }
  }

  return false;
}

// ログアップローダー
add_action(
  'admin_enqueue_scripts',
  'obeguu_enqueue_uploader'
);

function obeguu_enqueue_uploader($hook_suffix) {
  // 固定ページとして登録した管理画面の例
  if ($hook_suffix !== 'toplevel_page_oberin_logs_manage') {
    return;
  }
  wp_enqueue_script(
    'obeguu-uploader',
    get_stylesheet_directory_uri() . '/js/obeguu_ajax.js',
    [],
    '1.0.0',
    true
  );

  wp_localize_script(
    'obeguu-uploader',
    'obeguuUploader',
    [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('obeguu_ajax'),
    ]
  );
}

function obeguu_process($name, $path, $update = null) {
  // var_dump($file);
  $text = file_get_contents($path);
  if ($text === false) {
    return '（アップロード・保管）ファイルの読み込み失敗';
  }
  // ログを正規化(utf-8)
  $utf_text = obelin_log_normalize($text);
  if ($utf_text === false) {
    return 'ログの正規化失敗';
  }
  // var_dump($utf_text);
  // 正規化ログを保存
  $filenames = obelin_log_save($name, $text, $utf_text);
  if ($filenames === false) {
    return 'ログの保存失敗';
  }
  list($name, $raw_name, $nz_name) = $filenames;
  // ログの更新日など記録
  $error = obelin_log_date_update($name, $raw_name, !$update ? $nz_name : null);
  if ($error !== false) {
    return $error;
  }
  // var_dump($error);
  return false;
}

// ログのアップロード処理（JSからのAJAX）
add_action(
  'wp_ajax_obeguu_ajax'
  , 'obeguu_ajax'
);
function obeguu_ajax() {
  // 権限チェック
  if ( ! current_user_can('manage_options') ) {
    wp_die('権限がありません。');
  }

  // Nonceチェック
  check_admin_referer(
    'obeguu_ajax'
    , 'nonce'
  );


  if (isPost('log-action', 'current')) {
    obelin_log_current_update();
    return;
  }

  if (isPost('log-action', 'normalize')) {
    obelin_log_bulk_normalize();
    return;
  }

  if (isPost('log-action', 'delete')) {
    obelin_log_bulk_delete();
    return;
  }

  $files = $_FILES['files'];
  $errors = [];
  $passfiles = [];
  foreach($files['name'] as $i => $v) {
    // ログを展開
    // list($name, $text, $error) = obelin_log_unpack($file);
    list($name, $type, $tmp_name, $error, $size) = obelin_log_unpack($files, $i);
    // var_dump($name, $type, $tmp_name, $error, $size);
    if ($error !== UPLOAD_ERR_OK) {
      $errors[] = [$id, $error];
      continue;
    }

    $id = $name ? $name : ('file-no.' . $i);
    $error = obeguu_process($name, $tmp_name);
    if ($error !== false) {
      $errors[] = [$id, $error];
      continue;
    }

    // PASSしたファイル
    $passfiles[] = [$id, 'PASS'];
  }

  wp_send_json_success(
    [
      'message' => count($passfiles). '件のファイルを保存しました。',
      'passfiles' => $passfiles,
      'error' => $errors,
    ]
  );
}

function obelin_log_bulk_normalize() {
  $errors = [];
  $passfiles = [];
  foreach(@$_POST['logs'] as $name) {
    $id = $name ? $name : ('file-no.' . $i);
    $obeguu_path = get_obeguu_path($name);
    $raw_dir = trailingslashit($obeguu_path). 'raw';
    // var_dump($raw_dir. $name, $path);

    // var_dump($name, trailingslashit($raw_dir). $name);
    $error = obeguu_process($name, trailingslashit($raw_dir). $name, true); // oberin_logs/2002/raw/Log 1_1_2002
    if ($error !== false) {
      $errors[] = [$id, $error];
      continue;
    }

    // PASSしたファイル
    $passfiles[] = [$id, 'PASS'];

  }
  wp_send_json_success(
    [
      'message' => count($passfiles). '件のファイルを再変換しました。',
      'passfiles' => $passfiles,
      'error' => $errors,
    ]
  );
    // var_dump($path, $obeg_path, $name);
}

// ログの削除処理
function obelin_log_bulk_delete() {
  $deleted = 0;
  $errors = [];
  $passfiles = [];
  foreach(@$_POST['logs'] as $name) {
    // ログファイルを削除
    $obeguu_path = get_obeguu_path($name);

    $raw_dir = trailingslashit($obeguu_path). 'raw';
    $nz_dir = trailingslashit($obeguu_path). 'normalize';
    $raw_path = trailingslashit($raw_dir). $name;
    $nz_path = trailingslashit($nz_dir). $name;
    if (!preg_match('/\.txt$/i', $nz_path)) {
      $nz_path = $nz_path. '.txt';
    }
    // var_dump($dir. $v);
    if (unlink($raw_path) === false || unlink($nz_path) === false) {
      $errors[] = [$name, 'ログの削除失敗'];
    }

    // ログの記録を削除
    if (obelin_log_date_update($name, false, false) !== false) {
      $errors[] = [$name, 'ログの記録除去失敗'];
    }
    $passfiles[] = $name;

    $deleted += 1;
  }

  wp_send_json_success(
    [
      'message' => $deleted. '件のファイルを削除しました。',
      'passfiles' => $passfiles,
      'error' => $errors,
    ]
  );
}

// 管理ページメニューにカスタムページ項目追加
add_action('admin_menu', 'custom_menu_page');
function custom_menu_page() {
  add_menu_page('oberin_logsの管理', 'ログの管理', 'manage_options', 'oberin_logs_manage', 'add_custom_menu_page', 'dashicons-carrot', 4);
}

function add_custom_menu_page() {
  // ページジング（年代と月）
  global $log_month, $log_year, $month_list;

  // $dateList = load_date_list($log_date);
  $dateList = load_date_list($log_year, $log_month);
  $loglist = [];

  // ログファイルを抽出
  foreach($dateList as $k => $v) {
    if (!empty($v[0])) {
      list($raw, $normalized, $create_date, $update_date) = $v;
      $checkbox = "<td class=\"check-column\"><input type=\"checkbox\" name=\"logs[]\" value=\"{$raw}\" /></td>";
      $raw_cell = "<th class=\"title column-title\">$raw</th>";
      $nz_cell = "<td>$normalized</td>";
      $create_cell = "<td class=\"date column-date\">$create_date</td>";
      $update_cell = "<td class=\"date column-date\">$update_date</td>";
      $loglist[] = "<tr>$checkbox$raw_cell$nz_cell$create_cell$update_cell</tr>";
    }
  }

  $loglist = implode("\n", $loglist);
  // optionフォームを取得
  list($year_options, $month_options) = obeguu_date_options();

  ?>
  <style>
    .tablenav, .bulkactions {
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 5px;
    }
  </style>
  <div class="wrap">
    <div>
      <input type="file" id="my-file-input" multipleaccept=".log,.txt" hidden />
    </div>

    <div>
      <form id="obeguu-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <!-- <input type="hidden" name="action" value="obelin_log_normalize"> -->
        <?php
        wp_nonce_field(
          'admin_post_obelin_log_manage'
          , 'obelin_log_nonce'
        );
        ?>
        <div id="log-drop-area" style="padding: 3rem; border: 0.2rem #999999; border-style: dashed dotted; margin: 2rem 0;">
          <p id="log-upload-result" style="font-size: 1.2rem; text-align: center;">Logファイルをドロップしてアップロード</p>
        </div>
        <div class="tablenav top">
          <div class="alignleft actions bulkactions">
            <p style="float: left;">選択したログを</p>
            <select name="log-action" id="log-bulk-action-select" style="text-align: center;">
              <option value="">-- 選択 --</option>
              <option value="normalize">まとめる</option>
              <strong><option value="delete">！！けしちゃう！！</option></strong>
            </select>
            <?php submit_button('実行する', 'primary', 'log-bulk-action', false); ?>
          </div>
          <div class="pagaing year">
            <select name="obeguu-year">
              <?php echo $year_options; ?>
            </select>
          </div>
          <div class="pagaing month">
            <select name="obeguu-month">
              <?php echo $month_options; ?>
            </select>
          </div>
          <!-- <a id="obeguu-resent" class="button" href="">最近のログリストを更新</a> -->
          <a href="/loglist" class="button-link">ログの一覧ページ</a>
        </div>

        <table class="wp-list-table widefat fixed striped table-view-list posts"><thead><tr>
          <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox">
            <label for="cb-select-all-1"><span class="screen-reader-text">すべて選択</span></label></td>
            <th>生ログ（アップロード）</th>
            <th>作業カットログ（正規化）</th>
            <th class="manage-column column-date sorted desc">アップロード日</th>
            <th class="manage-column column-date sorted desc">更新日</th>
          </tr></thead><tbody>
            <?php echo $loglist; ?>
          </tbody></table>
        </form>
      </div>
    </div>

    <?php
  }
