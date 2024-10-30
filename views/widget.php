<?php
/**
 * Widget template. This template can be overriden using the "sp_template_ads-widget_widget.php" filter.
 * See the readme.txt file for more info.
 */

// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');

echo $before_widget;

if ( !empty( $title ) ) { echo $before_title . $title . $after_title; }

echo '<div class="ks-ads '.$instance['hoverstyle'].'"><figure>'.$this->get_image_html( $instance, true ).'</figure></div>';

if ( !empty( $description ) ) {
	if  ( !empty( $instance['color'] ) ){
		echo '<div class="'.$this->widget_options['classname'].'-description" style="color: '.$instance['color'].'" >';
		echo wpautop( $description );
		echo "</div>";
	}else {
		echo '<div class="'.$this->widget_options['classname'].'-description" >';
		echo wpautop( $description );
		echo "</div>";
	}
}

echo $after_widget;
?>