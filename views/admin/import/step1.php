<?php


namespace StylePress;

defined( 'STYLEPRESS_VERSION' ) || exit;


$remote_style_slug = isset( $_GET['remote_style_slug'] ) ? $_GET['remote_style_slug'] : 0;
if(!$remote_style_slug){
	wp_die('Invalid style ID');
}

$remote_style = Remote_Styles::get_instance()->get_remote_style_data($remote_style_slug);
if(!$remote_style){
	wp_die('Invalid style');
}
