<?php

birch_ns( 'birchschedule.view.appointments.edit.clientlist', function( $ns ) {

	global $birchschedule;

	$ns->init = function() use ( $ns ) {
		add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
		add_action( 'birchschedule_view_register_common_scripts_after',
					array( $ns, 'register_scripts' ) );
	};

	$ns->wp_admin_init = function() use( $ns ) {
		add_action( 'birchschedule_view_appointments_edit_add_meta_boxes_after',
					array( $ns, 'add_meta_boxes' ) );

		add_action( 'birchschedule_view_enqueue_scripts_post_edit_after',
					array( $ns, 'enqueue_scripts_post_edit' ) );
	};

	$ns->register_scripts = function() use ( $ns ) {
		global $birchschedule;

		$version = $birchschedule->get_product_version();

		wp_register_script( 'birchschedule_view_appointments_edit_clientlist',
							$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/clientlist/base.js',
							array( 'birchschedule_view_admincommon', 'birchschedule_view', 'jquery-ui-datepicker' ), "$version" );
	};

	$ns->enqueue_scripts_post_edit = function( $arg ) {
		if ( $arg['post_type'] != 'birs_appointment' ) {
			return;
		}

		global $birchschedule;

		$birchschedule->view->register_3rd_scripts();
		$birchschedule->view->register_3rd_styles();
		$birchschedule->view->enqueue_scripts(
			array(
				'birchschedule_view_appointments_edit_clientlist'
			)
		);
	};

	$ns->get_clients_meta_box_title = function() {
		return __( 'Client Info', 'birchschedule' );
	};

	$ns->add_meta_boxes = function() use ( $ns ) {
		$title = $ns->get_clients_meta_box_title();
		add_meta_box( 'birs_appointment_clients_meta_box', $title,
					  array( $ns, 'render_clients' ), 'birs_appointment', 'normal', 'high' );
	};

	$ns->render_clients = function( $post ) {
		$testListTable = new Birchschedule_View_Appointments_Edit_Clientlist_Table( $post->ID );
		$testListTable->prepare_items();
		$testListTable->display();
	};

	$ns->get_item_actions = function( $item ) {
		return array();
	};

	$ns->get_construct_args = function( $wp_list_table ) {
		return array(
			'singular'=> 'birs_client',
			'plural' => 'birs_clients',
			'ajax'  => true,
			'screen' => 'post.php'
		);
	};

	$ns->column_title = function( $wp_list_table, $item ) use ( $ns ) {
		$actions = $ns->get_item_actions( $item );
		$client_name_html = sprintf( '<a href="javascript:void(0);" data-client-id="%s" class="row-title">%s</a>',
									 $item['ID'], $item['_birs_client_name'] );
		return $client_name_html . $wp_list_table->row_actions( $actions );
	};

	$ns->column_default = function( $wp_list_table, $item, $column_name ) {
		if ( $column_name == 'title' ) {
			return $item['_birs_client_name'];
		} else {
			$meta_key = '_birs_' . $column_name;
			return $item[$meta_key];
		}
	};

	$ns->get_columns = function( $wp_list_table ) {
		global $birchschedule;

		$labels = $birchschedule->view->bookingform->get_fields_labels();
		return $columns = array(
			'title' => __( "Client Name", 'birchschedule' ),
			'client_email' => $labels['client_email'],
			'client_phone' => $labels['client_phone']
		);
	};

	$ns->get_sortable_columns = function( $wp_list_table ) {
		return $sortable = array(
			// 'title' => array( 'client_name_first', false ),
			// 'client_name_last' => array( 'client_name_last', false ),
			// 'client_email' => array( 'client_email', false ),
			// 'client_phone' => array( 'client_phone', false )
		);
	};

	$ns->single_row = function( $wp_list_table, $item ) use ( $ns ) {
		$column_count = $wp_list_table->get_column_count();
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' alternate' : '' );
?>
                <tr class="<?php echo $row_class; ?> birs_row"
                    id="birs_client_list_row_<?php echo $item['ID']; ?>"
                    data-item-id = "<?php echo $item['ID']; ?>">
<?php
		$wp_list_table->single_row_columns( $item );
?>
                </tr>
<?php
		$ns->render_more_rows( $wp_list_table, $item, $row_class );
	};

		$ns->render_more_rows = function( $wp_list_table, $item, $row_class ) {};

		$ns->get_item_count_per_page = function() {
			return 10;
		};

		$ns->prepare_items = function( $wp_list_table ) use ( $ns ) {
			global $birchschedule;

			$perpage = $ns->get_item_count_per_page();
			$columns = $wp_list_table->get_columns();
			$hidden = array();
			$sortable = $wp_list_table->get_sortable_columns();

			$wp_list_table->_column_headers = array( $columns, $hidden, $sortable );

			$screen = get_current_screen();

			$appointment1on1s = $birchschedule->model->query(
				array(
					'post_type' => 'birs_appointment1on1',
					'post_status' => array( 'publish' ),
					'meta_query' => array(
						array(
							'key' => '_birs_appointment_id',
							'value' => $wp_list_table->appointment_id
						)
					)
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_client_id' )
				)
			);
			$client_ids = array();
			if ( $appointment1on1s ) {
				$appointment1on1s = array_values( $appointment1on1s );
				foreach ( $appointment1on1s as $appointment1on1 ) {
					$client_ids[] = $appointment1on1['_birs_client_id'];
				}
			}
			$totalitems = count( $client_ids );

			$order = 'ASC';
			if ( isset( $_GET["order"] ) && $_GET['order'] == 'desc' ) {
				$order = 'DESC';
			}
			$wp_list_table->order = $order;

			$totalpages = ceil( $totalitems / $perpage );
			$wp_list_table->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
			) );

			$paged = $wp_list_table->get_pagenum();
			$query = array(
				'post_type' => 'birs_client',
				'post__in' => $client_ids + array( 0 ),
				'post_status' => 'publish',
				'nopaging' => false,
				'order' => $order,
				'orderby' => 'title',
				'posts_per_page' => $perpage,
				'paged' => $paged
			);
			$config = array(
				'base_keys' => array(
					'post_title'
				),
				'meta_keys' => array(
					'_birs_client_name_first', '_birs_client_name_last',
					'_birs_client_email', '_birs_client_phone'
				)
			);
			$items = $birchschedule->model->query( $query, $config );

			$items = array_values( $items );

			$wp_list_table->items = $items;
		};

} );

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Birchschedule_View_Appointments_Edit_Clientlist_Table extends WP_List_Table {

	var $appointment_id;

	var $order;

	var $orderby;

	var $package;

	function __construct( $appointment_id ) {
		global $birchschedule;

		$this->package = $birchschedule->view->appointments->edit->clientlist;
		$args = $this->package->get_construct_args( $this );
		parent::__construct( $args );
		$this->appointment_id = $appointment_id;
	}

	function column_title( $item ) {
		global $birchschedule;
		return $this->package->column_title( $this, $item );
	}

	function column_default( $item, $column_name ) {
		global $birchschedule;
		return $this->package->column_default( $this, $item, $column_name );
	}

	function get_columns() {
		global $birchschedule;
		return $this->package->get_columns( $this );
	}

	function get_sortable_columns() {
		global $birchschedule;
		return $this->package->get_sortable_columns( $this );
	}

	function single_row( $item ) {
		global $birchschedule;
		$this->package->single_row( $this, $item );
	}

	function prepare_items() {
		global $birchschedule;
		return $this->package->prepare_items( $this );
	}
}

?>
