<?php

birch_ns( 'birchschedule.ccode', function( $ns ) {

		global $birchschedule;

		$_ns_data = new stdClass();

		$ns->get_tab_name = function() {
			return 'custom_code';
		};

		$ns->is_tab_custom_code = function( $tab ) use( $ns ) {
			return $tab['tab'] === $ns->get_tab_name();
		};

		$ns->is_module_ccode = function( $module ) {
			return $module['module'] === 'ccode';
		};

		$ns->init = function() use( $ns, $_ns_data, $birchschedule ) {
			$_ns_data->SAVE_ACTION_NAME = "birchschedule_save_options_custom_code";

			add_action( 'init', array( $ns, 'wp_init' ) );
			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

			$birchschedule->view->settings->init_tab->when( $ns->is_tab_custom_code, $ns->init_tab );

			add_filter( 'birchschedule_view_settings_get_tabs', array( $ns, 'add_tab' ) );

			add_action( 'birchschedule_init_packages_after', array( $ns, 'run_custom_code' ) );
		};

		$ns->run_custom_code = function() use ( $ns ) {
			if ( !isset( $_REQUEST['birs_custom_code_switch'] ) ||
				$_REQUEST['birs_custom_code_switch'] != 'off' ) {
				$code = $ns->get_custom_code_php();
				$ns->execute_custom_code_php( $code );
				if ( is_admin() ) {
					add_action( 'admin_print_footer_scripts',
						array( $ns, 'inject_javascripts' ), 100 );
				} else {
					add_action( 'wp_print_footer_scripts',
						array( $ns, 'inject_javascripts' ), 100 );
				}
			}
		};

		$ns->wp_init = function() use( $ns ) {
			add_filter( 'birchschedule_view_get_custom_code_css',
				array( $ns, 'get_custom_code_css' ), 10, 2 );
		};

		$ns->wp_admin_init = function() use( $ns, $_ns_data ) {
			add_action( 'admin_post_' . $_ns_data->SAVE_ACTION_NAME, array( $ns, 'save_options' ) );
		};

		$ns->add_tab = function( $tabs ) use( $ns ) {
			$tabs[$ns->get_tab_name()] = array(
				'title' => __( 'Custom Code', 'birchschedule' ),
				'action' => array( $ns, 'render_custom_code_page' ),
				'order' => 50
			);

			return $tabs;
		};

		$ns->init_tab = function() use ( $ns, $birchschedule, $_ns_data ) {
			$product_version = $birchschedule->get_product_version();
			wp_register_script( 'codemirror',
				$birchschedule->plugin_url() . '/lib/assets/js/codemirror/lib/codemirror.js',
				array(), '3.13'
			);
			wp_register_script( 'codemirror-matchbrackets',
				$birchschedule->plugin_url() .
				'/lib/assets/js/codemirror/addon/edit/matchbrackets.js',
				array( 'codemirror' ), '3.13'
			);
			wp_register_script( 'codemirror-css',
				$birchschedule->plugin_url() . '/lib/assets/js/codemirror/mode/css/css.js',
				array( 'codemirror', 'codemirror-matchbrackets' ), '3.13'
			);
			wp_register_script( 'codemirror-xml',
				$birchschedule->plugin_url() . '/lib/assets/js/codemirror/mode/xml/xml.js',
				array( 'codemirror' ), '3.13'
			);
			wp_register_script( 'codemirror-javascript',
				$birchschedule->plugin_url() . '/lib/assets/js/codemirror/mode/javascript/javascript.js',
				array( 'codemirror' ), '3.13'
			);
			wp_register_script( 'codemirror-clike',
				$birchschedule->plugin_url() . '/lib/assets/js/codemirror/mode/clike/clike.js',
				array( 'codemirror' ), '3.13'
			);
			wp_register_script( 'codemirror-htmlmixed',
				$birchschedule->plugin_url() . '/lib/assets/js/codemirror/mode/htmlmixed/htmlmixed.js',
				array( 'codemirror' ), '3.13'
			);
			wp_register_script( 'codemirror-php',
				$birchschedule->plugin_url() . '/lib/assets/js/codemirror/mode/php/php.js',
				array(
					'codemirror-htmlmixed', 'codemirror-clike' , 'codemirror-javascript',
					'codemirror-xml', 'codemirror-matchbrackets'
				),
				'3.13'
			);
			wp_register_script( 'birchschedule_custom_code',
				$birchschedule->plugin_url() . '/modules/ccode/assets/js/custom-code.js',
				array( 'codemirror-css', 'codemirror-php', 'birchschedule_view_admincommon' ), $product_version
			);
			wp_register_style( 'codemirror',
				$birchschedule->plugin_url() . '/lib/assets/js/codemirror/lib/codemirror.css',
				array(), '3.13'
			);
			wp_register_style( 'codemirror-neat',
				$birchschedule->plugin_url() . '/lib/assets/js/codemirror/theme/neat.css',
				array( 'codemirror' ), '3.13'
			);
			wp_enqueue_style( 'codemirror-neat' );
			wp_enqueue_style( 'birchschedule_admincommon' );
			wp_enqueue_script( 'birchschedule_custom_code' );
			$params = array(
				'shortcodes' => $birchschedule->view->get_shortcodes()
			);
			wp_localize_script( 'birchschedule_custom_code', 'birs_custom_code_params', $params );
		};

		$ns->execute_custom_code_php = function( $code ) {
			if ( !$code ) {
				return;
			} else {
				ob_start();
				eval( $code );
				ob_end_clean();
			}
		};

		$ns->inject_javascripts = function() use ( $ns ) {
			$option = $ns->get_option_custom_code();
			$code = $option['javascript'];
?>
        <script type="text/javascript">
        <?php echo $code; ?>
        </script>
<?php
		};

		$ns->get_custom_code_php = function() use ( $ns ) {
			$option = $ns->get_option_custom_code();
			$code = trim( $option['php'] );
			$php_tag = substr( $code, 0, 5 );
			if ( strcmp( $php_tag, '<?php' ) === 0 ) {
				$code = substr( $code, 5 );
			}
			return $code;
		};

		$ns->get_custom_code_css = function( $css, $shortcode ) use ( $ns ) {
			$option = $ns->get_option_custom_code();
			if ( isset( $option['css'][$shortcode] ) ) {
				return $option['css'][$shortcode];
			} else {
				return '';
			}
		};

		$ns->get_option_custom_code = function() use( $ns ) {
			$option = get_option( 'birchschedule_options_custom_code',
				$ns->upgrader->get_default_options_custom_code() );
			return $option;
		};

		$ns->render_custom_code_page = function() use ( $ns, $birchschedule, $_ns_data ) {
			$option = $ns->get_option_custom_code();
			$css = $option['css'];
			$php = $option['php'];
			$javascript = $option['javascript'];
			$shortcodes = $birchschedule->view->get_shortcodes();
?>
        <style type="text/css">
            #birs_custom_code_box .CodeMirror {
                height: 30em;
                border: 1px solid #DDD;
            }
            #birs_custom_code_box .wp-tab-panel {
                max-height: none;
                border-style: none;
            }
            #birs_custom_code_box h3 {
                margin-top: 10px;
            }
        </style>
        <div id="birs_custom_code_box">
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <?php wp_nonce_field( $_ns_data->SAVE_ACTION_NAME ); ?>
                <input type="hidden" name="action" value="<?php echo $_ns_data->SAVE_ACTION_NAME; ?>" />
                <h3>CSS</h3>
                <div>
                    <ul class="wp-tab-bar">
                        <?php foreach ( $shortcodes as $shortcode ) {
				$shortcode_title = sprintf( '[%s]', $shortcode );
				if ( $shortcode == 'bpscheduler_booking_form' ) {
					$tab_class = "wp-tab-active";
				} else {
					$tab_class = "";
				}
				$block_id = "birs_css_" . $shortcode;
?>
                            <li class="<?php echo $tab_class; ?>">
                                <a href="#<?php echo $block_id; ?>">
                                    <?php echo $shortcode_title; ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                    <?php foreach ( $shortcodes as $shortcode ) {
				$block_id = "birs_css_" . $shortcode;
				$css_content = '';
				if ( isset( $css[$shortcode] ) ) {
					$css_content = $css[$shortcode];
				} else {
					$css_content = ' ';
				}
?>
                        <div id="<?php echo $block_id; ?>"
                             class="wp-tab-panel" style="">
                            <textarea id="birs_custom_code_css_<?php echo $shortcode; ?>"
                                name="birchschedule_options_custom_code[css][<?php echo $shortcode; ?>]"><?php echo $css_content; ?></textarea>
                        </div>
                    <?php } ?>
                </div>
                <h3>PHP</h3>
                <textarea id="birs_custom_code_php"
                    name="birchschedule_options_custom_code[php]"><?php echo $php; ?></textarea>
                <h3>JavaScript</h3>
                <textarea id="birs_custom_code_javascript"
                    name="birchschedule_options_custom_code[javascript]"><?php echo $javascript; ?></textarea>
                <input name="birs_custom_code_switch" type="hidden" value="off" />
                <p class="submit">
                    <input id="birs_custom_code_submit" name="Submit" type="submit"
                        class="button-primary" value="<?php _e( 'Save changes', 'birchschedule' );  ?>">
                </p>
            </form>
        </div>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready( function($) {
                <?php
			$custom_code_info = get_transient( "birchschedule_custom_code_info" );
			if ( false !== $custom_code_info ) {
?>
                $.jGrowl('<?php echo esc_js( $custom_code_info ); ?>', {
                        life: 1000,
                        position: 'center',
                        header: '<?php _e( '&nbsp', 'birchschedule' ); ?>'
                    });
                <?php
				delete_transient( "birchschedule_custom_code_info" );
			}
?>
            });
            //]]>
        </script>
<?php
		};

		$ns->save_options = function() use ( $ns, $birchschedule, $_ns_data ) {
			check_admin_referer( $_ns_data->SAVE_ACTION_NAME );
			if ( isset( $_POST['birchschedule_options_custom_code'] ) ) {
				$options = stripslashes_deep( $_POST['birchschedule_options_custom_code'] );
				$default_options = $ns->upgrader->get_default_options_custom_code();
				$options['version'] = $default_options['version'];
				update_option( "birchschedule_options_custom_code", $options );
			}
			set_transient( "birchschedule_custom_code_info", __( "Custom Code Updated", 'birchschedule' ), 60 );
			$orig_url = $_POST['_wp_http_referer'];
			wp_redirect( $orig_url );
			exit;
		};

	} );
