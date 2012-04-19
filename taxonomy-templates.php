<?php
/*
Plugin Name: Taxonomy Templates
Plugin URI: http://jacksonwhelan.com/plugins/taxonomy-templates/
Author: Jackson Whelan
Version: 0.3.1
Description: Allows user selection of theme taxonomy templates, much like page templates.
Author URI: http://jacksonwhelan.com

This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
that you can use any other version of the GPL.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

$TaxonomyTemplates = new TaxonomyTemplates;

class TaxonomyTemplates {

	function TaxonomyTemplates() {
		if ( is_admin() && isset( $_GET['taxonomy'] ) ) 
			add_action( $_GET['taxonomy'] . '_edit_form_fields', array( &$this,'taxonomy_template_select' ), 10, 2 );
	
		add_action( 'template_redirect', array( &$this,'custom_taxonomy_template' ) );
		add_action( 'edit_term', array( &$this, 'save_taxonomy' ) );
		add_filter( 'body_class', array( &$this,'custom_taxonomy_template_class' ) );		
	}

	function custom_taxonomy_template() {
		if ( is_category() && $template = $this->get_custom_taxonomy_template() ) :
		elseif ( is_tag() && $template = $this->get_custom_taxonomy_template() ) :
		elseif ( is_tax() && $template = $this->get_custom_taxonomy_template() ) :
		endif;
		
		if ( !empty( $template ) ) {
			include( apply_filters( 'template_include', $template ) );
			exit;
		}
	}

	function get_custom_taxonomy_template( $return = '' ) {
		$term = get_queried_object();
		$tid = $term->term_id;
		$pid = $term->parent;		
		$tax_templates = get_option( 'tax_templates' );

		if ( is_array( $tax_templates ) && ( array_key_exists( $tid, $tax_templates ) || array_key_exists( $pid, $tax_templates ) ) ) {
			$tax_template = ( array_key_exists( $tid, $tax_templates ) ) ? $tax_templates[$tid] : $tax_templates[$pid] ;
			$templates = array();
			$templates[] = $tax_template;
			if( 'file' == $return )
				return $tax_template;
			else
				return get_query_template( 'taxonomy', $templates );
		} else {
			return false;
		}
	}

	function save_taxonomy( $term_id ) {
		if ( isset( $_POST['tax_template'] ) && 'default' != $_POST['tax_template'] ) {
			$tax_templates = get_option( 'tax_templates' );
			$tax_templates[$term_id] = $_POST['tax_template'];
			update_option( 'tax_templates', $tax_templates );
		}
	}

	function taxonomy_template_select( $tag, $tax ) {
		if( count( $this->get_taxonomy_templates() ) == 0 )
			return;
			
		$tax_templates = get_option( 'tax_templates' );

		if ( is_array( $tax_templates ) && array_key_exists( $tag->term_id, $tax_templates ) ) {
			$tax_template = $tax_templates[$tag->term_id] ;
		} else {
			$tax_template = '';
		}
		?><tr class="form-field">
	        <th scope="row" valign="top"><label for="tax_template">Template</label></th>
	        <td>
	        	<select name="tax_template" id="tax_template">
	        		<option value="default"><?php _e('Default Template'); ?></option>
		        	<?php
						$this->taxonomy_template_dropdown( $tax_template );
					?>
	        	</select>
	            <span class="description"><?php _e('Template to use for this taxonomy.'); ?></span>
	        </td>
	    </tr><?php
	}

	function get_taxonomy_templates() {
		$themes = get_themes();
		$theme = get_current_theme();
		$templates = $themes[$theme]['Template Files'];
		$tax_templates = array();

		if ( is_array( $templates ) ) {
			$base = array( trailingslashit(get_template_directory()), trailingslashit(get_stylesheet_directory()) );

			foreach ( $templates as $template ) {
				$basename = str_replace($base, '', $template);

				// don't allow template files in subdirectories
				if ( false !== strpos($basename, '/') )
					continue;

				if ( 'functions.php' == $basename )
					continue;

				$template_data = implode( '', file( $template ));

				$name = '';
				if ( preg_match( '|Taxonomy Template Name:(.*)$|mi', $template_data, $name ) )
					$name = _cleanup_header_comment($name[1]);

				if ( !empty( $name ) ) {
					$tax_templates[trim( $name )] = $basename;
				}
			}
		}

		return $tax_templates;
	}

	function taxonomy_template_dropdown( $default = '' ) {
		$templates = $this->get_taxonomy_templates();
		ksort( $templates );
		foreach (array_keys( $templates ) as $template )
			: if ( $default == $templates[$template] )
				$selected = " selected='selected'";
			else
				$selected = '';
			echo "\n\t<option value='".$templates[$template]."' $selected>$template</option>";
		endforeach;
	}

	function custom_taxonomy_template_class( $classes ) {
		if ( is_category() && $template = $this->get_custom_taxonomy_template( 'file' ) ) :
		elseif ( is_tag() && $template = $this->get_custom_taxonomy_template( 'file' ) ) :
		elseif ( is_tax() && $template = $this->get_custom_taxonomy_template( 'file' ) ) :
		endif;

		if( isset( $template ) )
			$classes[] = 'tax-template-' . sanitize_html_class( str_replace( '.', '-', $template ), '' );

		return $classes;
	}
}