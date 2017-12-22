<tr class="advads-group-row">
    <td>
        <input type="hidden" class="advads-group-id" name="advads-groups[<?php echo $group->id; ?>][id]" value="<?php echo $group->id; ?>"/>
        <strong><a class="row-title" href="#"><?php echo $group->name; ?></a></strong>
        <p class="description"><?php echo $group->description; ?></p>
        <?php echo $this->render_action_links( $group ); ?>
        <div class="hidden advads-usage">
            <label><?php _e( 'shortcode', 'advanced-ads' ); ?>
                <code><input type="text" onclick="this.select();" style="width: 200px;" value='[the_ad_group id="<?php echo $group->id; ?>"]'/></code>
            </label><br/>
            <label><?php _e( 'template', 'advanced-ads' ); ?>
                <code><input type="text" onclick="this.select();" value="the_ad_group(<?php echo $group->id; ?>);"/></code>
            </label>
            <p><?php printf( __( 'Learn more about using groups in the <a href="%s" target="_blank">manual</a>.', 'advanced-ads' ), ADVADS_URL . 'manual/ad-groups/#utm_source=advanced-ads&utm_medium=link&utm_campaign=groups' ); ?></p>
        </div>
    </td>
    <td>
        <ul><?php $_type = isset($this->types[$group->type]['title']) ? $this->types[$group->type]['title'] : 'default'; ?>
            <li><strong><?php printf( __( 'Type: %s', 'advanced-ads' ), $_type ); ?></strong></li>
            <li><?php printf( __( 'ID: %s', 'advanced-ads' ), $group->id ); ?></li>
        </ul>
    </td>
    <td class="advads-ad-group-list-ads"><?php $this->render_ads_list( $group ); ?></td>
</tr>