<?php
/**
 * The template for displaying the footer
 *
 * Contains footer content and the closing of the #main and #page div elements.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */
?>
	</div><!-- #main .wrapper -->
	<footer id="colophon" role="contentinfo">
		<div class="site-info">
			<?php do_action( 'twentytwelve_credits' ); ?>
			<a href="<?php echo esc_url( __( 'https://wordpress.org/', 'twentytwelve' ) ); ?>" title="<?php esc_attr_e( 'Semantic Personal Publishing Platform', 'twentytwelve' ); ?>"><?php printf( __( 'Proudly powered by %s', 'twentytwelve' ), 'WordPress' ); ?></a>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
<script>jQuery(document).ready(function(){
    /*search box*/
    jQuery('#q').focus(function(){
        jQuery('form.search').addClass('selected');
    });
    jQuery('#q').blur(function(){
        jQuery('form.search').removeClass('selected');
    });
    jQuery('html').click(function(e){
        if(jQuery(e.target).is('#q')){}else if(jQuery('.srch form').hasClass('selected')){jQuery('.srch form').removeClass('selected');}
    });
    jQuery('.srch form button[type="submit"]').click(function(e){
        if(jQuery('.srch form').hasClass('selected') && (jQuery('#q').val().length != 0)){}else{
            e.preventDefault();
            e.stopPropagation();
            jQuery('.srch form').addClass('selected');
            if(jQuery('#q').val().length != 0){}else{jQuery('#q').focus();}
        }
    });
});</script>
</html>