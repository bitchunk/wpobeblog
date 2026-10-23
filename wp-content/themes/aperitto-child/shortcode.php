<?php
define('OBEGUU_LATEST_FILES_COUNT', 5);
add_shortcode('obeguu_list', 'obeguu_list');

add_action(
  'wp_enqueue_scripts',
  'obeguu_common_script'
);

function obeguu_list() {
  $list = load_date_list();
  // optionフォームを取得
  list($year_options, $month_options) = obeguu_date_options();
  ob_start();
  ?>
  <style>
  table td .date {
    font-size: 0.9rem;
  }
  span.dashicons {
    display: inline-block;
    float: right;
    margin-left: 0.5rem;
  }
  </style>
  <!-- TODOセレクトイベント発火 -->
  <form id="obeguu-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <div class="tablenav top">
      <div class="alignleft actions bulkactions">
        <div class="pagaing year" style="float: left; display: block;">
          <select name="obeguu-year">
            <?php echo $year_options; ?>
          </select>
        </div>
        <div class="pagaing month" style="float: left; display: block;">
          <select name="obeguu-month">
            <?php echo $month_options; ?>
          </select>
        </div>
      </div>
    </div>
  </form>
  <div class="clear" style="clear:both;"></div>
  <table class="wp-list-table">
    <thead>
      <th>生ログ</th><th>作業カットログ</th><th>追加日</th><th>更新日</th>
    </thead>
    <tbody>
      <?php
      foreach($list as $i => $v) {
        ?><tr><?php
        $link_raw = trailingslashit(get_obeguu_link($v[0]). 'raw'). $v[0];
        $link_nz = trailingslashit(get_obeguu_link($v[0]). 'normalize'). $v[1];
        echo "<td><a href=\"{$link_raw}\" target=”_blank”>{$v[0]}</a><a href=\"{$link_raw}\" download><span class=\"dashicons dashicons-download\"></span></a></td>";
        echo "<td><a href=\"{$link_nz}\" target=”_blank”>{$v[1]}</a><a href=\"{$link_nz}\" download><span class=\"dashicons dashicons-download\"></span></a></td>";
        echo "<td><p class=\"date\">{$v[2]}</p></td>";
        echo "<td><p class=\"date\">{$v[3]}</p></td>";
        ?></tr><?php
      }
      ?>
    </tbody>
  </table>
  <?php
  return ob_get_clean();
}

// 最近のファイル
add_shortcode('obeguu_recent', 'obeguu_recent');
function obeguu_recent(){
  $files_url = obeguu_resent_files(OBEGUU_LATEST_FILES_COUNT);
  $update_title = '最近の更新ログ';
  $upload_title = '最近の追加ログ';
  $titles = [
    'normalize' => $update_title,
    'raw' => $upload_title,
  ];
  ob_start();
  ?>
  <style>
  .indent {
    margin-left: 15px;
  }
  </style>
  <?php
  foreach($files_url as $type => $urls) {
    echo "<div class=\"widget widget_nav_menu indent\">";
      echo "<p class=\"wtitle\">{$titles[$type]}</p>";
      echo '<ul>';
        foreach($urls as $url) {
          ?><li><a href="<?php echo $url; ?>" target=”_blank” /><?php echo basename($url); ?></a></li><?php
        }
        echo '</ul>';
        echo "</div>";
      }
      return ob_get_clean();
    }


    // add_shortcode('obeguu_latest_upload', 'obeguu_latest_upload');
    // function obeguu_latest_upload() {
    //   $upload_dir = wp_upload_dir();
    //
    //   'latest.txt';
    // }
    //
    // add_shortcode('obeguu_latest_update', 'obeguu_latest_update');
    // function obeguu_latest_update() {
    //
    // }
