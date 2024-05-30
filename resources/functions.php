<?php // phpcs:ignore PSR1.Files.SideEffects.FoundWithSymbols

define('FM_VERSION', '0.1.1');
define('FM_ROOT', str_replace(ABSPATH, '/', dirname(__FILE__, 2)));
define('FM_PATH', dirname(__FILE__, 2));
define('FM_URI', home_url(FM_ROOT));
define('FM_HMR_HOST', 'http://localhost:5173');
define('FM_HMR_URI', FM_HMR_HOST . FM_ROOT);
define('FM_ASSETS_PATH', FM_PATH . '/dist');
define('FM_ASSETS_URI', FM_URI . '/dist');

require_once(FM_PATH . '/inc/bootstrap.php');

/**
 * Wraps the_content in e-content
 */
add_filter('the_content', function($content) {
  if ( is_feed() ) {
      return $content;
  }
  $wrap = '<div class="entry-content e-content">';
  if ( empty( $content ) ) {
      return $content;
  }
  return $wrap . $content . '</div>';
});


/**
 * Wraps the_excerpt in p-summary
 */
add_filter('the_excerpt', function($content) {
  if ( is_feed() ) {
      return $content;
  }
  $wrap = '<div class="entry-summary p-summary">';
  if ( ! empty( $content ) ) {
      return $wrap . $content . '</div>';
  }
  return $content;
}, 10);


/**
 * Code that improves theme support for various plugins
 */
add_action('init', function() {
	/*
 	 * Removes automated Syndication Links code from applying to main content section
   * I'm manually adding the same in the entry meta file
 	 */
  if ( class_exists( 'Syn_Meta' ) && has_filter( 'the_content', array( 'Syn_Config', 'the_content' ) ) ) {
    remove_filter( 'the_content', array( 'Syn_Config', 'the_content' ), 30 );
  }
  /*
   * Adds support for Simple Location
   */
  if ( class_exists( 'Loc_View' ) && has_filter( 'the_content', array( 'Loc_View', 'location_content' ) ) ) {
    remove_filter( 'the_content', array( 'Loc_View', 'location_content' ), 12 );
  }
}, 11 );


/**
 * Register navigation menus
 * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
 */
register_nav_menus([
    'primary_navigation' => __('Primary Navigation')
]);


add_action('after_setup_theme', function () {
  /**
   * Enable post thumbnails
   * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
   */
  add_theme_support('post-thumbnails');

  /**
   * Add default posts and comments RSS feed links to head
   */
  add_theme_support( 'automatic-feed-links' );

  add_theme_support('soil', [
    'clean-up',
    'nav-walker',
    'nice-search',
    'relative-urls',
  ]);

  add_theme_support('html5', ['caption', 'comment-form', 'comment-list', 'gallery', 'search-form']);
});
