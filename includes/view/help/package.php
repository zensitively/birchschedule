<?php

birch_ns( 'birchschedule.view.help', function( $ns ) {

	global $birchschedule;

	$_ns_data = new stdClass();

	$ns->init = function() use ( $ns, $_ns_data ) {
		$_ns_data->RUN_ACTION_NAME = "birchschedule_help_action_run";

		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
	};

	$ns->wp_admin_init = function() use( $ns, $_ns_data ) {
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'birchschedule_help' ) {
			$php_code = get_transient( 'birchschedule_help_php_code' );
			$ns->run_php_code( $php_code );
			delete_transient( 'birchschedule_help_php_code' );
			$ns->load_page();
		}
		add_action( 'birchschedule_view_render_help_page_after', array( $ns, 'render_admin_page' ) );
		add_action( 'admin_post_' . $_ns_data->RUN_ACTION_NAME, array( $ns, 'cache_code' ) );
	};

	$ns->run_php_code = function( $code ) {
		if ( !$code ) {
			return;
		} else {
			ob_start();
			eval( $code );
			ob_end_clean();
		}
	};

	$ns->get_screen = function() use ( $ns ) {
		global $birchschedule;

		$hook_name = $birchschedule->view->get_page_hook( 'help' );
		$screen = $birchschedule->view->get_screen( $hook_name );
		return $screen;
	};

	$ns->load_page = function() use ( $ns ) {
		$screen = $ns->get_screen();
		add_meta_box( 'birs_help_general', __( 'Help and Support', 'birchschedule' ),
					  array( $ns, 'render_help_general' ),
					  $screen, 'main', 'default' );
		add_meta_box( 'birs_help_version', __( 'Versions', 'birchschedule' ),
					  array( $ns, 'render_help_version' ),
					  $screen, 'main', 'default' );

		if ( current_user_can( 'manage_options' ) ) {
			add_meta_box( 'birs_help_run_scripts', __( 'Tools', 'birchschedule' ),
						  array( $ns, 'render_run_scripts' ),
						  $screen, 'main', 'default' );
		}
	};

	$ns->render_help_version = function() use ( $ns ) {
		global $birchschedule, $wp_version, $wpdb;

		$version = $birchschedule->get_product_version();
		$product_name = $birchschedule->get_product_name();
?>
         <div class="wrap">
             <table class="form-table">
                 <tbody>
                     <tr>
                         <th><?php echo "$product_name"; ?></th>
                         <td><?php echo "$version" ?></td>
                     </tr>
                     <tr>
                         <th><?php echo "WordPress"; ?></th>
                         <td><?php echo "$wp_version" ?></td>
                     </tr>
                     <tr>
                         <th><?php echo "PHP"; ?></th>
                         <td><?php echo phpversion(); ?></td>
                     </tr>
                     <tr>
                         <th><?php echo "MySQL"; ?></th>
                         <td><?php echo $wpdb->db_version(); ?></td>
                     </tr>
                 </tbody>
             </table>
         </div>
<?php
	};

	$ns->render_help_general = function() {
?>
        <div class="padding">
            <p>If you have any questions, please refer to <a target="_blank" href="http://www.birchpress.com/support/documentation">documentation</a> first.</p>
            <p>If you are using a <a target="_blank" href="http://www.birchpress.com/">premium edition</a>, please submit a ticket <a target="_blank" href="http://www.birchpress.com/support/submit-a-ticket/">here</a>.</p>
            <p>If you are using a free version, please submit your question through our <a target="_blank" href="http://www.birchpress.com/support/forums">support forum</a>.</p>
            <p>If you find our product helpful, please <a target="_blank" href="http://wordpress.org/extend/plugins/birchschedule">rate it!</a></p>
        </div>
<?php
	};

	$ns->render_run_scripts = function() use( $ns, $birchschedule, $_ns_data ) {
?>
    <p>
        <a href="javascript:void(0);" id="birs_help_button_toggle_run"><?php _e( 'Run PHP scripts. This is only for advanced users.' ); ?></a>
    </p>
    <div id="birs_help_panel_run_scripts" style="display:none;">
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <?php wp_nonce_field( $_ns_data->RUN_ACTION_NAME ); ?>
            <input type="hidden" name="action" value="<?php echo $_ns_data->RUN_ACTION_NAME; ?>" />
            <textarea name="birchschedule_help_php_code" style="width:99%;display:block;margin:6px 0;" rows="10"></textarea>
            <input type="submit" class="button-primary" value="<?php _e( 'Run', 'birchschedule' ); ?>" />
        </form>
    </div>
    <script type="text/javascript">
        jQuery(function($){
            $('#birs_help_button_toggle_run').click(function(){
                $('#birs_help_panel_run_scripts').toggle();
            });
        });
    </script>
<?php
	};

	$ns->cache_code = function() use ( $ns, $birchschedule, $_ns_data ) {
		check_admin_referer( $_ns_data->RUN_ACTION_NAME );
		if ( isset( $_POST['birchschedule_help_php_code'] ) ) {
			$php_code = stripslashes_deep( $_POST['birchschedule_help_php_code'] );
			set_transient( "birchschedule_help_php_code", $php_code, 60 );
		}
		$orig_url = $_POST['_wp_http_referer'];
		wp_redirect( $orig_url );
		exit;
	};

	$ns->render_admin_page = function() use ( $ns ) {
		global $birchschedule;

		$screen = $ns->get_screen();
		$birchschedule->view->show_notice();
?>
        <div id="birchschedule_help" class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="postbox-container-1" class="postbox-container">
                        <?php do_meta_boxes( $screen, 'main', array() ) ?>
                    </div>
                </div>
                <br class="clear" />
            </div>
        </div>
<?php
	};

} );
