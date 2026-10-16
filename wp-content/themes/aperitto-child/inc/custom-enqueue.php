<?php
function aperitto_enqueue_style_and_script() {

  // STYLES
  wp_enqueue_style( 'aperitto-style', get_stylesheet_uri(), array(), true );

  // SCRIPTS
  // **Duplicate**
  // wp_enqueue_script( 'aperitto-html5shiv', get_template_directory_uri() . '/js/html5shiv.min.js', array(), '3.7.3', true );
  // wp_script_add_data( 'aperitto-html5shiv', 'conditional', 'lt IE 9' );


  // wp_enqueue_script( 'aperitto-scripts', get_template_directory_uri() . '/js/functions.js', array( 'jquery' ), true, true );
  wp_enqueue_script(
    'aperitto-scripts',
    get_template_directory_uri() . '/js/functions.js',
    array( 'jquery' ),
    '1.0.0', // 実際のテーマのバージョンに合わせる
    true
);

  // 不適切
  // if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
  //   wp_enqueue_script( 'comment-reply', false, true, true );
  // }

  if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
      wp_enqueue_script( 'comment-reply' );
  }
}
