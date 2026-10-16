<?php
// 親テーマのスタイルシートを読み込む
add_action( 'wp_enqueue_scripts', function() {
  wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
});

// 親テーマのDuplicateを修正
require_once get_stylesheet_directory() . '/inc/custom-enqueue.php';

// Dashiconsを表示
add_action( 'wp_enqueue_scripts', function () {
  wp_enqueue_style( 'dashicons' );
} );

add_action(
  'admin_enqueue_scripts',
  'obegu_common_script'
);
function obegu_common_script() {// 共通JS
  wp_enqueue_script(
    'obegu-interface',
    get_stylesheet_directory_uri() . '/js/obegu_interface.js',
    [],
    '1.0.0',
    true
  );
}
// ショートコード
require_once 'shortcode.php';

// 管理ページを表示
if (is_admin()) {
  require_once 'admin.php';
}

function isPost($key, $value = null) {
  if (isset($_POST[$key])) {
    return $value === null ? $_POST[$key] : $_POST[$key] === $value;
  }
  return false;
}

function isGet($key, $value = null) {
  if (isset($_GET[$key])) {
    return $value === null ? $_GET[$key] : $_GET[$key] === $value;
  }
  return false;
}

$log_year = isGet('obegu-year');
$log_month = isGet('obegu-month');
$log_date = false;
if ($log_year && $log_month) {
  $log_date = "1-$log_month-$log_year";
  // var_dump($log_month,$log_year);
}

// ディレクトリ完全削除
function rrmdir($dir) {
  $files = glob($dir. '/{,.}[!.,!..]*', GLOB_BRACE);
  // var_dump($files);
  foreach ($files as $file) {
    if (is_file($file)) {
      if (unlink($file) === false) {
        return false;
      }
    } else {
      if (rrmdir($file) === false) {
        return false;
      }
    }
  }
  if (rmdir($dir) === false) {
    return false;
  }
  return true;
}

// Wordpressのアップロード階層ディレクトリ
function get_obegu_upload_dir() {
  $upload_dir = wp_upload_dir();
  if (!empty($upload_dir['error'])) {
    wp_send_json_error(array('message' => $upload_dir['error']), 500);
    return false;
  }
  return $upload_dir;
}

// ログの入った階層までのURL
function get_obegu_current_url() {
  $dir = get_obegu_upload_dir();
  // $date : 'Log 1-1-2002'
  $base_url = trailingslashit($dir['baseurl']) . 'oberin-logs';
  return $base_url;
}

function get_obegu_current_dir() {
  $dir = get_obegu_upload_dir();
  // $date : 'Log 1-1-2002'
  $base_dir = trailingslashit($dir['basedir']) . 'oberin-logs';
  return $base_dir;
}

function obegu_default_year($name = null) {
  $base_path = get_obegu_current_dir();
  if (empty($name)) {
    $year_dirs = glob($base_path. '/*', GLOB_ONLYDIR);
    $name = !empty($year_dirs) ? '1-1-'. basename($year_dirs[0]) : '1-1-2002';
  }
  return $name;
}

function obegu_date_parse($date) {
  return date_parse_from_format('n-j-Y', preg_replace('/_/', '-', $date) ?? null);
}

function get_obegu_link($name = null) {
  $base_url = get_obegu_current_url();
  if (empty($name)) {
    $name = obegu_default_year();
  }
  $year = obegu_date_parse($name)["year"];
  // oberin-logs/2002
  $year_dir = trailingslashit($base_url). $year;
  // var_dump();
  return trailingslashit($year_dir);
}

function get_obegu_path($name = null) {
  if (empty($name)) {
    $name = obegu_default_year();
  }
  $year = obegu_date_parse($name)["year"];
  $base_dir = get_obegu_current_dir();
  $year_dir = trailingslashit($base_dir). $year;

  $path = trailingslashit($year_dir);

  return $path;
}

// 年代フォルダを取得
function get_obegu_dirs() {
  $base_dir = get_obegu_current_dir();
  $year_dirs = glob($base_dir. '/*', GLOB_ONLYDIR);
  $dirs = [];
  foreach($year_dirs as $dir) {
    $year = basename($dir);
    $dirs[$year] = $dir;
  }
  return $dirs;
}

$month_list = [];
// ログファイルのリストを取得
function load_date_list($year = null, $month = null) {
  global $log_date, $log_month, $month_list;
  $obegu_path = get_obegu_path($log_date);

  if (is_dir($obegu_path) === false) {
    return []; // 'ディレクトリがありません'
  }

  $date_path = $obegu_path. 'logdate.csv';
  // var_dump($log_date,$date_path);
  if (file_exists($date_path) === false) {
    return []; // 'CSVがありません'
  }
  $input = fopen($date_path, 'r');
  if ($input === false) {
    return '読み込み先CSVファイルの読み込み失敗';
  }
  $csv = [];
  $date_list = [];
  $current_month = $log_month ?? false;
  while (($row = fgetcsv($input)) !== false) {
    if (!empty($row[0])) {
      $csv[] = $row; // 年代に含まれるすべてをリストアップ
      if ($row[4] === $current_month) {
        $date_list[] = $row; // 指定月だけリストアップ
        $current_month = $row[4];
      }
      $month_list[] = $row[4]; // 年代に含まれる月のリスト
    }
  }

  // 月のリストが見つからなかったら見つかった月で絞り直し
  if (empty($date_list)) {
    $current_month = false;
    foreach($csv as $row) {
      if ($row[4] === $current_month || empty($current_month)) {
        $date_list[] = $row;
        $current_month = $row[4];
      }
    }
  }

  // それでも見つからなかった。
  if (empty($current_month)) {
    $current_month = '1';
  }

  $log_month = $current_month; // globalを更新
  $month_list = array_unique($month_list); // 月リストから重複の月を除去
  // var_dump($month_list);

  fclose($input);
  return $date_list;
}

// ログの日付選択フォームを生成
function obegu_date_options() {
  global $log_year, $log_month, $month_list;
  $dirs = get_obegu_dirs();
  $year_options = [];
  $month_options = [];
  // var_dump($log_year, $log_month);
  foreach($dirs as $year => $dir) {
    $selected = $log_year == $year ? 'selected' : '';
    $year_options[] = "<option value=\"{$year}\" $selected>{$year}年</option>";
  }
  $year_options = implode("\n", $year_options);

  // $months  = [1,2,3,4,5,6,7,8,9,10,11,12];
  foreach($month_list as $month) {
    $selected = $log_month == $month ? 'selected' : '';
    $month_options[] = "<option value=\"{$month}\" $selected>{$month}月</option>";
  }
  $month_options = implode("\n", $month_options);
  return [$year_options, $month_options];
}
