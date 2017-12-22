<?php
/**
 * Custom Menu Walker for Responsive Menus
 *
 * Creates a <select> menu instead of the default
 * Unordered list menus.
 *
 **/

class ATM_Custom_Nav_Walker extends Walker_Nav_Menu {

    function start_el( &$output, $item, $depth = 0 , $args = array() , $id = 0 ) {

        global $wp_query;       

        // Create a visual indent in the list if we have a child item.
        $visual_indent = ( $depth ) ? str_repeat( '&nbsp;&nbsp;', $depth ) : '';

        // Load the item URL
        $attributes = '';
        $item_output = '';
        $prepend = '';
        $attributes .= ! empty( $item->url ) ? ' value="' . $item->ID . '"' : '';

        // If we have hierarchy for the item, add the indent, if not, leave it out.
        // Loop through and output each menu item as this.

        if( !empty( $item->url ) ){

            if($depth != 0) {
                $item_output .= '<option ' . $attributes .' class="atm_children atm_children_' . $depth . '">'. $visual_indent . '- ' . apply_filters( 'the_title', $item->title, $item->ID ) . '</option>';
            } else {
                $item_output .= '<option ' . $attributes .'>'.$prepend.apply_filters( 'the_title', $item->title, $item->ID ).'</option>';
            }

        }

        // Make the output happen.
        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args, $id );

    }

}