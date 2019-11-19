<?php
/*
Plugin Name: Afer Last Page Fix
Description: Remove JS and CSS types. Изменение заголовков виджетов на span. Стандартные редиректы от SEO для главной страницы. Отключение перехода по неполным url. Корректировка url с цифрами на конце. Строчные url. Для подключения (отключения) функций смотри код плагина.
Author: Nikita Makuha
Version: 1.0 
*/

//Remove JS and CSS types
add_action( 'template_redirect', function(){
    ob_start( function( $buffer ){
        $buffer = str_replace( array( 'type="text/javascript"', "type='text/javascript'", 'type="text/css"', "type='text/css'" ), '', $buffer );

        return $buffer;
    });
});

/*
// Изменение заголовков виджетов на span (при подключении требуется доработка стилей)
add_filter( 'dynamic_sidebar_params', 'my_edit_widget_func' );
function my_edit_widget_func( $params ) {
        $params[0]['before_title'] = '<span class="title">' ;
        $params[0]['after_title'] = '</span>' ;
    return $params;
}
*/


/*
// Стандартные редиректы от SEO для главной страницы
add_action( 'template_redirect', function() {
    if (  $_SERVER['REQUEST_URI'] === '/index.php'  ) {
        wp_redirect( home_url(), 301 );
        exit;
    }
    
    if ( preg_match( "@^[\/]+[\/]$@i", $_SERVER['REQUEST_URI'] ) ) {
        wp_redirect( home_url(), 301 );
        exit;} 

} );
*/

// Отключаем переход по неполным url 
function remove_redirect_guess_404_permalink( $redirect_url ) {
    if ( is_404() )
        return false;
    return $redirect_url;
}
add_filter( 'redirect_canonical', 'remove_redirect_guess_404_permalink' );



// Корректировка url с цифрами на конце
add_action( 'template_redirect', 'alpf_template_redirect' );
function alpf_template_redirect() {
	if ( is_singular() ) {
		global $post, $wp_query;
		$page = get_query_var('page');
		if ( $page < 2 ) return;
		$pages = explode('<!--nextpage-->', $post->post_content);
		$numpages = count($pages);
		if ($numpages < $page) {
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			$wp_query->post_count = 0;
		}
	}
}
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', 'alpf_rel_canonical' );
function alpf_rel_canonical() {
	if ( !is_singular() )
		return;

	global $wp_the_query, $wp_rewrite;
	if ( !$id = $wp_the_query->get_queried_object_id() )
		return;
	if ( ( $page = get_query_var('page') ) > 1 ) {
		if ( '' == get_option('permalink_structure') ) 
			$link = add_query_arg( 'page', $page, get_permalink() );
		elseif ( 'page' == get_option('show_on_front') && get_option('page_on_front') == get_the_ID() )
			$link = trailingslashit(get_permalink()) . user_trailingslashit("$wp_rewrite->pagination_base/" . $page, 'single_paged');
		else
			$link = trailingslashit(get_permalink()) . user_trailingslashit($page, 'single_paged');
	}
	else {
		$link = get_permalink( $id );
	}

	if ( $page = get_query_var('cpage') )
		$link = get_comments_pagenum_link( $page );

	echo "<link rel='canonical' href='$link' />\n";
}

remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
add_action( 'wp_head', 'alpf_shortlink_wp_head', 10, 0);
function alpf_shortlink_wp_head() {
	$shortlink = wp_get_shortlink( 0, 'query' );

	if ( empty( $shortlink ) )
		return;

	if ( is_singular() and ( $page = get_query_var('page') ) > 1 ) {
		if ($shortlink == home_url('/')) {
			$shortlink .= '?page=' . $page;
		}
		else {
			$shortlink .= '&amp;page=' . $page;
		}
	}

	echo "<link rel='shortlink' href='" . esc_url( $shortlink ) . "' />\n";
}





//Строчные url

if ( !defined('ABSPATH') ) exit; // Exit if accessed directly
if ( !class_exists('WPForceLowercaseURLs') ) {
  class WPForceLowercaseURLs {
    /**
     * Initialize plugin
     */
    public static function init() {
      // If page is non-admin, force lowercase URLs
      if ( !is_admin() ) {
        add_action( 'init', array('WPForceLowercaseURLs', 'toLower') );
      }
    }

    /**
     * Changes the requested URL to lowercase and redirects if modified
     */
    public static function toLower() {
      // Grab requested URL
      $url = $_SERVER['REQUEST_URI'];
      $params = $_SERVER['QUERY_STRING'];
      // If URL contains a period, halt (likely contains a filename and filenames are case specific)
      if ( preg_match('/[\.]/', $url) ) {
        return;
      }
      // If URL contains a capital letter
      if ( preg_match('/[A-Z]/', $url) ) {
        // Convert URL to lowercase
        $lc_url = empty($params)
          ? strtolower($url)
          : strtolower(substr($url, 0, strrpos($url, '?'))).'?'.$params;
        // if url was modified, re-direct
        if ($lc_url !== $url) {
          // 301 redirect to new lowercase URL
          header('Location: '.$lc_url, TRUE, 301);
          exit();
        }
      }
    }
  }
  WPForceLowercaseURLs::init();
}