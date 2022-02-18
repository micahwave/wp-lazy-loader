<?php

/**
 * Plugin Name: WP Lazy Loader
 * Description: Improve your site's Core Web Vitals score by lazy loading embeds and other elements that may not immediately be visible to the user.
 * Author: Micah Ernst
 */

namespace WPLazyLoader;

const PLUGIN_SLUG = 'wp-lazy-loader';
const PLUGIN_VERSION = '0.0.1';
const SUPPORTED_EMBEDS = [
	'twitter' => [
		'selector' => '.twitter-tweet',
		'script' => 'https://platform.twitter.com/widgets.js'
	],
	'tiktok' => [
		'selector' => '.tiktok-embed',
		'script' => 'https://www.tiktok.com/embed.js'
	]
];

/**
 * Remove scripts from HTML by src attribute.
 * 
 * @param String $html The HTML to remove scripts from.
 * @param Array  $urls The src attribute values to look for in a script tag.
 * @return String
 */
function remove_scripts( $html, $urls ) {
	\libxml_use_internal_errors( true );

	$doc = new \DOMDocument();
	$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

	\libxml_use_internal_errors( false );

	$scripts = $doc->getElementsByTagName( 'script' );

	for ( $i = $scripts->length - 1; $i >= 0; $i-- ) {
		$script = $scripts->item( $i );
		if ( in_array( $script->getAttribute( 'src' ), $urls, true ) ) {
			$script->parentNode->removeChild( $script );
		}
	}

	return $doc->saveHTML();
}

/**
 * Check content for lazy loadable embeds, modify them if they exists and inject our lazy loader.
 * 
 * @param String  $content The content of the page.
 * @return String $content
 */
function the_content( $content ) {
	global $post;

	$scripts = [];
	$active_embeds = [];
	$embeds = apply_filters( 'lazy_load_embeds', SUPPORTED_EMBEDS );

	if ( has_block( 'embed' ) ) {
		$blocks = parse_blocks( $post->post_content );

		foreach ( $blocks as $block ) {
			$slug = $block['attrs']['providerNameSlug'] ?? '';
			
			if ( ! empty( $slug ) && ! empty( $embeds[$slug] ) ) {
				$scripts[] = $embeds[$slug]['script'];
				$active_embeds[] = $embeds[$slug];
			}
		}
	}

	// Remove any duplicate scripts.
	$scripts = array_unique( $scripts );

	if ( count( $scripts ) ) {
		// Enable lazy loader.
		wp_enqueue_script( PLUGIN_SLUG, plugins_url( 'dist/app.min.js', __FILE__ ), [], PLUGIN_VERSION, true );

		// Add inline script with supported embeds.
		$inline_script = sprintf( 'var wpLazyLoaderEmbeds = %s;', wp_json_encode( $active_embeds ) );
		wp_add_inline_script( PLUGIN_SLUG, $inline_script, true );
		
		// Remove found script tags.
		$content = remove_scripts( $content, $scripts );
	}

	return $content;
}
add_filter( 'the_content', __NAMESPACE__ . '\the_content', PHP_INT_MAX );