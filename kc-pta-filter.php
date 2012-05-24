<?php
/*
Plugin Name: KC Post Type Archive Filter
Description: Filter post type archive index with a defined taxonomy term
Author: Dzikri Aziz
Author URI: http://kucrut.org/
Version: 0.1
*/


class kcArchiveFilter {
	public static $post_types = array();


	public static function init() {
		$post_types = apply_filters('kcArchiveFilter_post_types', array() );
		if ( !$post_types )
			return;

		self::$post_types = $post_types;
		add_filter( 'rewrite_rules_array', array(__CLASS__, 'rewrite_rules') );
		add_filter( 'query_vars', array(__CLASS__, 'query_vars') );
		add_action( 'parse_query', array(__CLASS__, 'parse_query'), 12 );
	}


	public static function rewrite_rules( $rules ) {
		$new = array();
		foreach ( self::$post_types as $pt ) {
			$new["{$pt}/filter/([^/]+)/([^/]+)?$"] = 'index.php?post_type='.$pt.'&filter=$matches[1],$matches[2]';
			$new["{$pt}/filter/([^/]+)/([^/]+)/page/([0-9]{1,})/?$"] = 'index.php?post_type='.$pt.'&filter=$matches[1],$matches[2]&paged=$matches[3]';
		}

		$rules = $new + $rules;
		return $rules;
	}


	public static function query_vars( $vars ) {
		$vars[] = 'filter';
		return $vars;
	}


	public static function parse_query( $query ) {
		$q =& $query->query;
		$qv =& $query->query_vars;

		if (
			is_admin()
			|| !is_post_type_archive()
			|| !in_array($q['post_type'], self::$post_types)
			|| !isset($q['filter'])
			|| !$q['filter']
		)
			return;

		$filter = explode( ',', $qv['filter'] );
		if ( count($filter) !== 2 )
			return;

		$qv['tax_query'] = array(
			array(
				'taxonomy' => $filter[0],
				'terms'    => $filter[1],
				'field'    => 'slug'
			)
		);
	}


	public static function get_filtered_archive_title( $prefix = '', $sep = '/' ) {
		global $wp_query;
		if ( empty($wp_query->posts) )
			return $prefix;

		$title = $prefix;
		if ( isset($wp_query->query_vars['tax_query']) && !empty($wp_query->query_vars['tax_query']) ) {
			$tq = $wp_query->query_vars['tax_query'][0];
			$term = get_term_by( $tq['field'], $tq['terms'], $tq['taxonomy'] );
			if ( !empty($sep) )
				$title .= " {$sep} ";
			$title .= apply_filters( 'single_term_title', $term->name );
		}

		return $title;
	}


}

add_action( 'plugins_loaded', array('kcArchiveFilter', 'init'), 99 );

?>
