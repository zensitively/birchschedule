<?php

birch_ns( 'birchschedule.view.staff', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use ( $ns, $birchschedule ) {
			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );

			add_action( 'init', array( $ns, 'wp_init' ) );

			$birchschedule->view->load_page_edit->when( $ns->is_post_type_staff, $ns->load_page_edit );

			$birchschedule->view->enqueue_scripts_edit->when( $ns->is_post_type_staff, $ns->enqueue_scripts_edit );

			$birchschedule->view->enqueue_scripts_list->when( $ns->is_post_type_staff, $ns->enqueue_scripts_list );

			$birchschedule->view->save_post->when( $ns->is_post_type_staff, $ns->save_post );

			$birchschedule->view->pre_save_post->when( $ns->is_post_type_staff, $ns->pre_save_post );
		};

		$ns->wp_init = function() use ( $ns ) {
			$ns->register_post_type();
		};

		$ns->wp_admin_init = function() use ( $ns ) {
			add_action( 'wp_ajax_birchschedule_view_staff_new_schedule',
				array( $ns, 'ajax_new_schedule' ) );
			add_filter( 'manage_edit-birs_staff_columns', array( $ns, 'get_edit_columns' ) );
			add_action( 'manage_birs_staff_posts_custom_column', array( $ns, 'render_custom_columns' ), 2 );
		};

		$ns->is_post_type_staff = function( $post ) {
			return $post['post_type'] === 'birs_staff';
		};

		$ns->register_post_type = function() {
			register_post_type( 'birs_staff', array(
					'labels' => array(
						'name' => __( 'Providers', 'birchschedule' ),
						'singular_name' => __( 'Provider', 'birchschedule' ),
						'add_new' => __( 'Add Provider', 'birchschedule' ),
						'add_new_item' => __( 'Add New Provider', 'birchschedule' ),
						'edit' => __( 'Edit', 'birchschedule' ),
						'edit_item' => __( 'Edit Provider', 'birchschedule' ),
						'new_item' => __( 'New Provider', 'birchschedule' ),
						'view' => __( 'View Provider', 'birchschedule' ),
						'view_item' => __( 'View Provider', 'birchschedule' ),
						'search_items' => __( 'Search Providers', 'birchschedule' ),
						'not_found' => __( 'No Providers found', 'birchschedule' ),
						'not_found_in_trash' => __( 'No Providers found in trash', 'birchschedule' ),
						'parent' => __( 'Parent Provider', 'birchschedule' )
					),
					'description' => __( 'This is where provider are stored.', 'birchschedule' ),
					'public' => false,
					'show_ui' => true,
					'capability_type' => 'birs_staff',
					'map_meta_cap' => true,
					'publicly_queryable' => false,
					'exclude_from_search' => true,
					'show_in_menu' => 'edit.php?post_type=birs_appointment',
					'hierarchical' => false,
					'show_in_nav_menus' => false,
					'rewrite' => false,
					'query_var' => true,
					'supports' => array( 'title', 'editor' ),
					'has_archive' => false
				)
			);
		};

		$ns->load_page_edit = function( $arg ) use ( $ns ) {
			add_action( 'add_meta_boxes', array( $ns, 'add_meta_boxes' ) );
			add_filter( 'post_updated_messages', array( $ns, 'get_updated_messages' ) );
		};

		$ns->enqueue_scripts_edit = function( $arg ) use ( $ns ) {
			global $birchschedule;

			$birchschedule->view->register_3rd_scripts();
			$birchschedule->view->register_3rd_styles();
			$birchschedule->view->enqueue_scripts(
				array(
					'birchschedule_view_staff_edit', 'birchschedule_model',
					'birchschedule_view_admincommon', 'birchschedule_view'
				)
			);
			$birchschedule->view->enqueue_styles(
				array(
					'birchschedule_admincommon', 'birchschedule_staff_edit',
					'jquery-ui-no-theme'
				)
			);
		};

		$ns->enqueue_scripts_list = function( $arg ) use ( $ns, $birchschedule ) {
			$birchschedule->view->enqueue_styles( 'birchschedule_admincommon' );
		};

		$ns->get_edit_columns = function( $columns ) {
			$columns = array();

			$columns["cb"] = "<input type=\"checkbox\" />";
			$columns["title"] = __( "Provider Name", 'birchschedule' );
			$columns["description"] = __( "Description", 'birchschedule' );
			return $columns;
		};

		$ns->render_custom_columns = function( $column ) {
			global $post;

			if ( $column == 'description' ) {
				the_content();
				return;
			}
			$value = get_post_meta( $post->ID, '_' . $column, true );

			echo $value;
		};

		$ns->get_updated_messages = function( $messages ) {
			global $post, $post_ID;

			$messages['birs_staff'] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => __( 'Provider updated.', 'birchschedule' ),
				2 => __( 'Custom field updated.', 'birchschedule' ),
				3 => __( 'Custom field deleted.', 'birchschedule' ),
				4 => __( 'Provider updated.', 'birchschedule' ),
				5 => isset( $_GET['revision'] ) ? sprintf( __( 'Provider restored to revision from %s', 'birchschedule' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => __( 'Provider updated.', 'birchschedule' ),
				7 => __( 'Provider saved.', 'birchschedule' ),
				8 => __( 'Provider submitted.', 'birchschedule' ),
				9 => sprintf( __( 'Provider scheduled for: <strong>%1$s</strong>.', 'birchschedule' ), date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ) ),
				10 => __( 'Provider draft updated.', 'birchschedule' )
			);

			return $messages;
		};

		$ns->add_meta_boxes = function() use ( $ns ) {
			remove_meta_box( 'slugdiv', 'birs_staff', 'normal' );
			remove_meta_box( 'postcustom', 'birs_staff', 'normal' );
			add_meta_box( 'birchschedule-work-schedule', __( 'Work Schedule', 'birchschedule' ),
				array( $ns, 'render_work_schedule' ), 'birs_staff', 'normal', 'default' );
			add_meta_box( 'birchschedule-staff-services', __( 'Services', 'birchschedule' ),
				array( $ns, 'render_assign_services' ), 'birs_staff', 'side', 'default' );
		};

		$ns->get_default_new_schedule = function() {
			return array(
				'minutes_start' => 540,
				'minutes_end' => 1020,
				'weeks' => array(
					1 => 'on',
					2 => 'on',
					3 => 'on',
					4 => 'on',
					5 => 'on'
				)
			);
		};

		$ns->ajax_new_schedule = function() use ( $ns ) {
			$location_id = $_POST['birs_location_id'];
			$uid = uniqid();
			$schedule = $ns->get_default_new_schedule();
			$ns->render_schedule_block( $location_id, $uid, $schedule );
			die;
		};

		$ns->pre_save_post = function( $post_data, $post_attr ) {

			$email = $_POST['birs_staff_email'];
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				$post_data['post_author'] = $user->ID;
			} else {
				$post_data['post_author'] = 1;
			}
			return $post_data;
		};

		$ns->save_post = function( $post ) {
			global $birchschedule;

			$config = array(
				'base_keys' => array(),
				'meta_keys' => array(
					'_birs_staff_schedule', '_birs_assigned_services'
				)
			);
			$staff_data =
			$birchschedule->view->merge_request( $post, $config, $_POST );
			if ( !isset( $_POST['birs_assigned_services'] ) ) {
				$staff_data['_birs_assigned_services'] = array();
			}
			$birchschedule->model->save( $staff_data, $config );
			$birchschedule->model->update_model_relations( $post['ID'], '_birs_assigned_services',
				'birs_service', '_birs_assigned_staff' );
			$birchschedule->model->booking->async_recheck_fully_booked_days();
		};

		$ns->get_schedule_interval = function() {
			return 5;
		};

		$ns->render_schedule = function( $location_id, $uid, $schedule ) use ( $ns ) {
			global $birchschedule, $birchpress;

			$interval = $ns->get_schedule_interval();
			$time_options = $birchpress->util->get_time_options( $interval );
			$start = $schedule['minutes_start'];
			$end = $schedule['minutes_end'];
			$weeks = $birchpress->util->get_weekdays_short();
			$start_of_week = $birchpress->util->get_first_day_of_week();
?>
		<ul>
			<li>
				<span class="birs_schedule_field_label"><?php _e( 'From', 'birchschedule' ); ?></span>
				<div class="birs_schedule_field_content">
					<select
						name="birs_staff_schedule[<?php echo $location_id; ?>][schedules][<?php echo $uid; ?>][minutes_start]">
							<?php $birchpress->util->render_html_options( $time_options, $start ); ?>
					</select>
					<a href="javascript:void(0);"
						data-schedule-id="<?php echo $uid; ?>"
						class="birs_schedule_delete">
						<?php echo "Delete"; ?>
					</a>
				</div>
			</li>
			<li>
				<span class="birs_schedule_field_label"><?php _e( 'To', 'birchschedule' ); ?></span>
				<div class="birs_schedule_field_content">
					<select
						name="birs_staff_schedule[<?php echo $location_id; ?>][schedules][<?php echo $uid; ?>][minutes_end]">
							<?php $birchpress->util->render_html_options( $time_options, $end ); ?>
					</select>
				</div>
			</li>
			<li>
				<span class="birs_schedule_field_label"></span>
				<div class="birs_schedule_field_content">
<?php
			foreach ( $weeks as $week_value => $week_name ) {
				if ( $week_value < $start_of_week ) {
					continue;
				}
				if ( isset( $schedule['weeks'] ) && isset( $schedule['weeks'][$week_value] ) ) {
					$checked_attr = ' checked="checked" ';
				} else {
					$checked_attr = '';
				}
?>
					<label>
						<input type="checkbox"
							name="birs_staff_schedule[<?php echo $location_id; ?>][schedules][<?php echo $uid; ?>][weeks][<?php echo $week_value; ?>]"
							<?php echo $checked_attr; ?>/>
							<?php echo $week_name; ?>
					</label>
<?php
			}

			foreach ( $weeks as $week_value => $week_name ) {
				if ( $week_value >= $start_of_week ) {
					continue;
				}
				if ( isset( $schedule['weeks'] ) && isset( $schedule['weeks'][$week_value] ) ) {
					$checked_attr = ' checked="checked" ';
				} else {
					$checked_attr = '';
				}
?>
					<label>
						<input type="checkbox"
							name="birs_staff_schedule[<?php echo $location_id; ?>][schedules][<?php echo $uid; ?>][weeks][<?php echo $week_value; ?>]"
							<?php echo $checked_attr; ?>/>
							<?php echo $week_name; ?>
					</label>
<?php
			}
?>
				</div>
			</li>
		</ul>
<?php
		};

		$ns->render_schedule_block = function( $location_id, $uid, $schedule ) use ( $ns ) {
			$schedule_dom_id = 'birs_schedule_' . $uid;
?>
		<div id="<?php echo $schedule_dom_id; ?>" class="birs_schedule_item">
<?php
			$ns->render_schedule( $location_id, $uid, $schedule );
?>
			<script type="text/javascript">
				jQuery(function($) {
					var scheduleId = '<?php echo $schedule_dom_id; ?>';
					$('#' + scheduleId + ' .birs_schedule_delete').click(function() {
						$('#' + scheduleId).remove();
					});
				});
			</script>
		</div>
<?php
		};

		$ns->render_timetable = function( $staff_id, $location_id ) use ( $ns ) {
			global $birchschedule;

			$location_schedule =
			$birchschedule->model->get_staff_schedule_by_location( $staff_id, $location_id );
			if ( isset( $location_schedule['schedules'] ) ) {
				$schedules = $location_schedule['schedules'];
			} else {
				$schedules = array();
			}
?>
		<div style="margin-bottom:20px;">
			<div id="<?php echo 'birs_schedule_' . $location_id ?>">
<?php
			foreach ( $schedules as $uid => $schedule ) {
				$ns->render_schedule_block( $location_id, $uid, $schedule );
			}
?>
			</div>
			<div class="birs_schedule_new_box">
				<a href="javascript:void(0);"
					class="birs_schedule_new"
					data-location-id="<?php echo $location_id; ?>">
					<?php _e( '+ Add Schedule', 'birchschedule' ); ?>
				</a>
			</div>
		</div>
<?php
		};

		$ns->render_work_schedule = function( $post ) use ( $ns ) {
			global $birchpress, $birchschedule;

			$weeks = $birchpress->util->get_weekdays_short();
			$locations = get_posts(
				array(
					'post_type' => 'birs_location',
					'nopaging' => true,
					'orderby' => 'post_title'
				)
			);
			$schedule = get_post_meta( $post->ID, '_birs_staff_schedule', true );
			if ( !isset( $schedule ) ) {
				$schedule = array();
			} else {
				$schedule = unserialize( $schedule );
			}
?>
				<div class="panel-wrap birchschedule">
<?php
			if ( sizeof( $locations ) > 0 ) {
?>
			<div id="location_list">
				<ul>
<?php
				$index = 0;
				foreach ( $locations as $location ) {
?>
					<li data-location-id="<?php echo $location->ID; ?>" <?php if ( $index++ === 0 ) echo ' class="current" '; ?>>
						<a><?php echo $location->post_title; ?></a>
					</li>
<?php
				}
?>
				</ul>
			</div>
			<div id="timetable">
<?php
				$index = 0;
				foreach ( $locations as $location ) {
					if ( !isset( $schedule[$location->ID] ) ) {
						$location_schedule = array();
					} else {
						$location_schedule = $schedule[$location->ID];
					}
?>
				<div data-location-id="<?php echo $location->ID; ?>"
					 <?php if ( $index++ !== 0 ) echo 'style="display:none;"'; ?>>
<?php
					$ns->render_timetable( $post->ID, $location->ID );
?>
				</div>
<?php
				}
?>
					</div>
				   <div class="clear"></div>
<?php
			} else {
?>
			<p>
<?php
				printf( __( 'There is no locations. Click %s here %s to add one.', 'birchschedule' ),
					'<a href="post-new.php?post_type=birs_location">', '</a>' );
?>
			</p>
<?php
			}
?>
		</div>
<?php
		};

		$ns->render_service_checkboxes = function( $services, $assigned_services ) {
			foreach ( $services as $service ) {
				if ( array_key_exists( $service->ID, $assigned_services ) ) {
					$checked = 'checked="checked"';
				} else {
					$checked = '';
				}
				echo '<li><label>' .
				"<input type=\"checkbox\" " .
				"name=\"birs_assigned_services[$service->ID]\" $checked >" .
				$service->post_title .
				'</label></li>';
			}
		};

		$ns->render_assign_services = function( $post ) use ( $ns ) {
			$services = get_posts(
				array(
					'post_type' => 'birs_service',
					'nopaging' => true
				)
			);
			$assigned_services = get_post_meta( $post->ID, '_birs_assigned_services', true );
			$assigned_services = unserialize( $assigned_services );
			if ( $assigned_services === false ) {
				$assigned_services = array();
			}
?>
		<div class="panel-wrap birchschedule">
			<?php if ( sizeof( $services ) > 0 ): ?>
				<p><?php _e( 'Assign services that this provider can perform:', 'birchschedule' ); ?></p>
				<div>
					<ul>
						<?php $ns->render_service_checkboxes( $services, $assigned_services ); ?>
					</ul>
				</div>
			<?php else: ?>
				<p>
<?php
				printf( __( 'There is no services to assign. Click %s here %s to add one.', 'birchschedule' ), '<a
							href="post-new.php?post_type=birs_service">', '</a>' );
?>
				</p>
			<?php endif; ?>
		</div>
<?php
		};

	} );
