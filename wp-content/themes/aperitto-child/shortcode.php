<?php
add_shortcode('obegu_list', 'obegu_list');
function obegu_list() {
  $list = load_date_list();

  // optionフォームを取得
  list($year_options, $month_options) = obegu_date_options();

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
  <form id="obegu-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <div class="tablenav top">
      <div class="alignleft actions bulkactions">
        <div class="pagaing year" style="float: left; display: block;">
            <select name="obegu-year">
              <?php echo $year_options; ?>
          </select>
        </div>
        <div class="pagaing month" style="float: left; display: block;">
          <select name="obegu-month">
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
        $link_raw = trailingslashit(get_obegu_link($v[0]). 'raw'). $v[0];
        $link_nz = trailingslashit(get_obegu_link($v[0]). 'normalize'). $v[1];
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
}

define('OBEGU_LATEST_UPLOAD_COUNT', 5);
add_shortcode('obegu_latest_upload', 'obegu_latest_upload');
function obegu_latest_upload() {
  $upload_dir = wp_upload_dir();

  'latest.txt';
}

define('OBEGU_LATEST_UPDATE_COUNT', 5);
add_shortcode('obegu_latest_update', 'obegu_latest_update');
function obegu_latest_update() {

}
