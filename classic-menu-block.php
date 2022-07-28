<?php
/**
 * Plugin Name:       Classic Menu Block
 * Plugin URI:        https://github.com/spacedmonkey/classic-menu-block
 * Description:       A gutenberg block to use classic menus.
 * Requires at least: 5.9
 * Requires PHP:      5.6
 * Version:           0.1.5
 * Author:            Jonathan Harris
 * Author URI:        https://www.spacedmonkey.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       classic-menu-block
 *
 * @package           spacedmonkey/classic-menu-block
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 *
 * @since 0.1.0
 */
function create_block_classic_menu_block_block_init() {
	register_block_type(
		__DIR__,
		[
			'render_callback' => 'render_block_classic_menu',
		]
	);
}
add_action( 'init', 'create_block_classic_menu_block_block_init' );

/**
 * Allows to display the language switcher menu in the Site Editor.
 *
 * @since 0.1
 *
 * @param stdClass[] $items Menu items.
 * @return stdClass[] Modified menu items.
 */
function pll_nav_menu_items( $items ) {
	$new_items = array();

	$offset = 0;

	if ( empty( PLL()->options['default_lang'] ) || ! is_string( PLL()->options['default_lang'] ) ) {
		return $items;
	}
	$def_lang = PLL()->model->get_language( sanitize_key( PLL()->options['default_lang'] ) );

	foreach ( $items as $item ) {
		if ( $options = get_post_meta( $item->ID, '_pll_menu_item', true ) ) {
			/** This filter is documented in include/switcher.php */
			$options = apply_filters( 'pll_the_languages_args', $options ); // Honor the filter here for 'show_flags', 'show_names' and 'dropdown'.

			$switcher = new PLL_Switcher();
			$args = array_merge( array( 'raw' => 1 ), $options );

			/** @var array */
			$the_languages = $switcher->the_languages( PLL()->links, $args );

			// parent item for dropdown
			if ( ! empty( $options['dropdown'] ) ) {
				$name = isset( $options['display_names_as'] ) && 'slug' === $options['display_names_as'] ? $def_lang->slug : $def_lang->name;
				$item->title = pll_get_item_title( $def_lang->get_display_flag(), $name, $options );
				$item->attr_title = '';
				$item->classes = array( 'pll-parent-menu-item' );
				$item->menu_order += $offset;
				$new_items[] = $item;
				$offset++;
			}

			$i = 0; // for incrementation of menu order only in case of dropdown
			foreach ( $the_languages as $lang ) {
				$i++;
				$lang_item = clone $item;
				$lang_item->ID = $lang_item->ID . '-' . $lang['slug']; // A unique ID
				$lang_item->title = pll_get_item_title( $lang['flag'], $lang['name'], $options );
				$lang_item->attr_title = '';
				$lang_item->url = $lang['url'];
				$lang_item->lang = $lang['locale']; // Save this for use in nav_menu_link_attributes
				$lang_item->classes = $lang['classes'];
				if ( ! empty( $options['dropdown'] ) ) {
					$lang_item->menu_order = $item->menu_order + $i;
					$lang_item->menu_item_parent = $item->db_id;
					$lang_item->db_id = 0; // to avoid recursion
				} else {
					$lang_item->menu_order += $offset;
				}
				$new_items[] = $lang_item;
				$offset++;
			}
			$offset--;
		} else {
			$item->menu_order += $offset;
			$new_items[] = $item;
		}
	}

	return $new_items;
}

/**
 * Format a language switcher menu item title based on options
 *
 * @since 0.1
 *
 * @param string $flag    Formatted flag
 * @param string $name    Language name
 * @param array  $options Language switcher options
 * @return string Formatted menu item title
 */
function pll_get_item_title( $flag, $name, $options ) {
	if ( $options['show_flags'] ) {
		if ( $options['show_names'] ) {
			$title = sprintf( '%1$s<span style="margin-%2$s:0.3em;">%3$s</span>', $flag, is_rtl() ? 'right' : 'left', esc_html( $name ) );
		} else {
			$title = $flag;
		}
	} else {
		$title = esc_html( $name );
	}
	return $title;
}

/**
 * Block render callback.
 *
 * @since 0.1
 * @param array $attrs Block attributes.
 *
 * @return string
 */
function render_block_classic_menu( $attrs ) {
	$is_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;

	$attrs      = wp_parse_args(
		$attrs,
		[
			'className' => '',
			'anchor'    => '',
			'menu'      => 0,
		]
	);
	$menu_attrs = [
		'echo'            => false,
		'container_class' => 'wp-classic-menu-block ' . $attrs['className'],
		'container_id'    => $attrs['anchor'],
		'menu'            => $attrs['menu'],
	];
	$lang       = $attrs['language'];

	if ( ! $is_rest && ( PLL()->curlang->slug !== $lang || 'all' === $lang ) ) {
		// Display the block only for the chosen language on frontend. Or for All.
		return;
	}

	/**
	 * Filters menu attributes.
	 *
	 * @since 0.1
	 *
	 * @param array $menu_attrs Menu attributes.
	 * @param array $attrs Block attributes.
	 */
	$menu_attrs = apply_filters( 'classic_menu_block_attributes', $menu_attrs, $attrs );

	if ( $is_rest ) {
		// Filter the menu items only in the Site Editor, let Polylang do its stuff otherwise.
		add_filter( 'wp_get_nav_menu_items', 'pll_nav_menu_items' );
	}

	return (string) wp_nav_menu( $menu_attrs );
}
