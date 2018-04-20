<?php

birch_ns( 'birchschedule.cmanager', function( $ns ) {

		global $birchschedule;

		$ns->init = function() use( $ns ) {
			add_action( 'init', array( $ns, 'wp_init' ) );
			add_action( 'admin_init', array( $ns, 'wp_admin_init' ) );
		};

		$ns->wp_admin_init = function() use( $ns ) {
			add_action( 'birchschedule_view_clients_add_meta_boxes_after',
						array( $ns, 'add_meta_boxes' ) );
		};

		$ns->wp_init = function() {};

		$ns->add_meta_boxes = function() use( $ns ) {
			add_meta_box( 'birs_client_appointments_meta_box', __( 'Appointments', 'birchschedule' ),
						  array( $ns, 'render_appointments' ), 'birs_client', 'normal', 'high' );
		};

		$ns->render_appointments = function( $post ) use( $ns ) {
				$testListTable = new Birchschedule_Cmanager_Appointmentlist_Table( $post->ID );
				$testListTable->prepare_items();
				$testListTable->display();
?>
        <script type='text/javascript'>
            jQuery(function($){
                $('#birs_client_appointments_meta_box #_wpnonce').remove();
            });
        </script>
<?php
		};

		$ns->get_item_actions = function( $item ) use( $ns ) {
			$view_url = admin_url( sprintf( 'post.php?post=%s&action=edit', $item['ID'] ) );
			return array(
				'view' => sprintf( $action_html, $view_url, __( 'View', 'birchschedule' ) )
			);
		};

		$ns->get_construct_args = function( $wp_list_table ) {
			return array(
				'singular'=> 'birs_appointment',
				'plural' => 'birs_appointments',
				'ajax'  => true,
				'screen' => 'post.php'
			);
		};

		$ns->column_title = function( $wp_list_table, $item ) use( $ns ) {
			$view_url = admin_url( sprintf( 'post.php?post=%s&action=edit', $item['ID'] ) );
			$action_html = '<a href="%s">%s</a>';
			return sprintf( $action_html, $view_url, $item['ID'] );
		};

		$ns->column_default = function( $wp_list_table, $item, $column_name ) use( $ns ) {
			if ( $column_name == 'title' ) {
				return $item['ID'];
			}
			$meta_key = '_birs_' . $column_name;
			return $item[$meta_key];
		};

		$ns->get_columns = function( $wp_list_table ) use( $ns, $birchschedule ) {
			$labels = $birchschedule->view->bookingform->get_fields_labels();
			return $columns = array(
				'title' => '#',
				'appointment_datetime' => $labels['time'],
				'service_name' => $labels['service'],
				'staff_name' => $labels['service_provider'],
				'location_name' => $labels['location']
			);
		};

		$ns->get_sortable_columns = function( $wp_list_table ) {
			return $sortable = array(
				'appointment_datetime' => array( 'appointment_timestamp', false )
			);
		};

		$ns->single_row = function( $wp_list_table, $item ) use( $ns ) {
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
		};

		$ns->get_item_count_per_page = function() {
			return 10;
		};

		$ns->prepare_items = function( $wp_list_table ) use( $ns, $birchschedule ) {
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
							'key' => '_birs_client_id',
							'value' => $wp_list_table->client_id
						)
					)
				),
				array(
					'base_keys' => array(),
					'meta_keys' => array( '_birs_appointment_id' )
				)
			);
			$appointment_ids = array();
			if ( $appointment1on1s ) {
				$appointment1on1s = array_values( $appointment1on1s );
				foreach ( $appointment1on1s as $appointment1on1 ) {
					$appointment_ids[] = $appointment1on1['_birs_appointment_id'];
				}
			}
			$totalitems = count( $appointment_ids );

			$order = 'DESC';
			if ( isset( $_GET["order"] ) && $_GET['order'] == 'asc' ) {
				$order = 'ASC';
			}
			$wp_list_table->order = $order;

			$orderby = 'appointment_timestamp';
			if ( isset( $_GET["orderby"] ) && !empty( $_GET['orderby'] ) ) {
				$orderby = $_GET["orderby"];
			}
			$wp_list_table->orderby = $orderby;

			$totalpages = ceil( $totalitems / $perpage );
			$wp_list_table->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
			) );

			$paged = $wp_list_table->get_pagenum();
			$query = array(
				'post_type' => 'birs_appointment',
				'post__in' => $appointment_ids + array( 0 ),
				'post_status' => 'publish',
				'nopaging' => false,
				'order' => $order,
				'orderby' => 'meta_value',
				'meta_key' => '_birs_' . $orderby,
				'posts_per_page' => $perpage,
				'paged' => $paged
			);
			$config = array(
				'base_keys' => array(),
				'meta_keys' => array(
				)
			);
			$items = $birchschedule->model->query( $query, $config );

			$wp_list_table->items = array();
			foreach ( $items as $item ) {
				$appointment = $birchschedule->model->mergefields->get_appointment_merge_values( $item['ID'] );
				$wp_list_table->items[] = $appointment;
			}
		};

} );

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Birchschedule_Cmanager_Appointmentlist_Table extends WP_List_Table {

	var $client_id;

	var $order;

	var $orderby;

	var $package;

	function __construct( $client_id ) {
		global $birchschedule;

		$this->package = $birchschedule->cmanager;
		$args = $this->package->get_construct_args( $this );
		parent::__construct( $args );
		$this->client_id = $client_id;
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
