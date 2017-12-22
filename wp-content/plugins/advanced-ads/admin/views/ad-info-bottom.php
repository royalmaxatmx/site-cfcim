<div id="advads-wizard-controls" class="hidden">
    <button type="button" id="advads-wizard-controls-prev" class="button button-secondary button-large"><span class="dashicons dashicons-controls-back"></span>&nbsp;<?php _ex( 'previous', 'wizard navigation', 'advanced-ads' ); ?></button>
    <input type="submit" id="advads-wizard-controls-save" class="button button-primary button-large" value="<?php 
    // display different message depending on a new ad (filter = raw) or existing one
    echo ( isset( $post->filter ) && 'raw' === $post->filter ) ? _x( 'next', 'wizard navigation', 'advanced-ads' ) : _x( 'save', 'wizard navigation', 'advanced-ads' ); ?>"/>
    <button type="button" id="advads-wizard-controls-next" class="button button-primary button-large"><?php _ex( 'next', 'wizard navigation', 'advanced-ads' ); ?>&nbsp;<span class="dashicons dashicons-controls-forward"></span></button>
    <p><a href="javascript:void(0)" class="advads-stop-wizard"><?php _e( 'Stop Wizard and show all options', 'advanced-ads' ); ?></a></p>
</div>
<?php // start the wizard if it was never closed
if( $this->start_wizard_automatically() ) : ?>
<script>jQuery( document ).ready(function ($) { advads_wizard.start() });</script>
<?php endif;