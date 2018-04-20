<?php

birch_ns( 'birchpress.db', function( $ns ) {

		$_ns_data = new stdclass();

		$ns->init = function() use ( $_ns_data ) {
		};

		$ns->get_base_keys = function( $keys ) {
			global $birchpress;

			$base_keys = array();
			foreach ( $keys as $key ) {
				if ( !$birchpress->util->starts_with( $key, '_' ) ) {
					$base_keys[] = $key;
				}
			}
			return $base_keys;
		};

		$ns->get_meta_keys = function( $keys ) {
			global $birchpress;

			$meta_keys = array();
			foreach ( $keys as $key ) {
				if ( $birchpress->util->starts_with( $key, '_' ) ) {
					$meta_keys[] = $key;
				}
			}
			return $meta_keys;
		};

		$ns->_preprocess_config = function( $config ) use ( $ns ) {
			if ( !is_array( $config ) ) {
				$config = array();
			}

			if ( !isset( $config['base_keys'] ) ) {
				$config['base_keys'] = array();
			}

			if ( !isset( $config['meta_keys'] ) ) {
				$config['meta_keys'] = array();
			}

			if ( isset( $config['keys'] ) ) {
				$keys = $config['keys'];
				$config['base_keys'] = array_merge( $config['base_keys'], $ns->get_base_keys( $keys ) );
				$config['meta_keys'] = array_merge( $config['meta_keys'], $ns->get_meta_keys( $keys ) );
				unset( $config['keys'] );
			}
			return $config;
		};

		$ns->get = function ( $post, $config ) use ( $ns ) {
			global $birchpress;

			$config = $ns->_preprocess_config( $config );

			if ( is_a( $post, 'WP_Post' ) ) {
				$id = $post->ID;
			} else {
				$id = $post;
				if ( !$ns->is_valid_id( $id ) ) {
					return false;
				}
				$post = get_post( $id );
				if ( $post === null ) {
					return false;
				}
			}

			$model = array(
				'ID' => $id
			);

			$base_keys = array_merge( array( 'post_type' ), $config['base_keys'] );

			foreach ( $base_keys as $key ) {
				if ( isset( $post->$key ) ) {
					$model[$key] = $post->$key;
				}
			}

			$meta_keys = $config['meta_keys'];
			foreach ( $meta_keys as $key ) {
				$value = get_post_meta( $id, $key, true );
				$model[$key] = $value;
			}

			return $model;
		};

		$ns->is_valid_id = function ( $id ) use ( $ns ) {
			return (string) (int) $id == $id && $id > 0;
		};

		$ns->delete = function ( $id ) use ( $ns ) {
			birch_assert( $ns->is_valid_id( $id ) );
			global $wpdb;

			$wpdb->delete( $wpdb->postmeta, array(
					'post_id' => $id
				) );
			return wp_delete_post( $id );
		};

		$ns->save = function ( $model, $config ) use ( $ns ) {
			birch_assert( is_array( $model ), 'Model should be an array.' );
			birch_assert( isset( $model['post_type'] ), 'Model should have post_type field.' );

			global $birchpress;

			$config = $ns->_preprocess_config( $config );
			$base_keys = $config['base_keys'];
			$meta_keys = $config['meta_keys'];

			$id = 0;
			if ( isset( $model['ID'] ) ) {
				if ( $ns->is_valid_id( $model['ID'] ) ) {
					$id = $model['ID'];
				} else {
					unset( $model['ID'] );
				}
			}
			$model_fields = array_keys( $model );
			foreach ( $model_fields as $field ) {
				if ( !in_array( $field, $base_keys ) &&
					!in_array( $field, $meta_keys ) &&
					$field != 'ID' && $field != 'post_type' ) {
					unset( $model[$field] );
				}
			}
			if ( $base_keys || !$id ) {
				if ( !isset( $model['post_status'] ) ) {
					$model['post_status'] = 'publish';
				}
				$id = wp_insert_post( $model );
			}
			if ( !$id ) {
				return false;
			}
			foreach ( $meta_keys as $key ) {
				if ( isset( $model[$key] ) ) {
					$value = $model[$key];
					update_post_meta( $id, $key, $value );
				}
			}
			return $id;
		};

		$ns->get_post_columns = function() {
			return array(
				'post_author', 'post_date', 'post_date_gmt', 'post_content',
				'post_title', 'post_excerpt', 'post_status', 'comment_status',
				'ping_status', 'post_password', 'post_name', 'to_ping',
				'pinged', 'post_modified', 'post_modified_gmt', 'post_parent',
				'guid', 'menu_order', 'post_type', 'post_mime_type',
				'comment_count'
			);
		};

		$ns->get_essential_post_columns = function( $post_type ) {
			return array(
				'post_author', 'post_date_gmt',
				'post_status',
				'post_modified_gmt',
				'post_type'
			);
		};

		$ns->convert_association_query = function( $queries, $post_type ) use ( $ns ) {
			$cleaned_query = array();
			$defaults = array(
				'taxonomy' => '',
				'terms' => array(),
				'field' => 'slug',
				'operator' => 'IN',
				'include_children' => false,
			);
			foreach ( $queries as $key => $query ) {
				if ( 'relation' === $key ) {
					$cleaned_query['relation'] = $ns->sanitize_relation( $query );
				} elseif ( $ns->is_first_order_clause( $query ) ) {
					$cleaned_clause = $defaults;
					if ( isset( $query['association'] ) ) {
						$association_fullname = $ns->get_association_fullname( $post_type, $query['association'] );
						$taxonomy = $ns->get_association_taxonomy( $association_fullname );
						$cleaned_clause['taxonomy'] = $taxonomy;
						if ( isset( $query['ids'] ) ) {
							if ( is_array( $query['ids'] ) ) {
								$ids = $query['ids'];
							} else {
								$ids = array( $query['ids'] );
							}
							$terms = array_map( function( $id ) use ( $ns, $taxonomy ) {
									return $ns->get_term_slug( $id, $taxonomy );
								}, $ids );
							$cleaned_clause['terms'] = $terms;
						}
					}
					if ( isset( $query['operator'] ) &&
						in_array( $query['operator'], array( 'IN', 'NOT IN', 'AND', 'EXISTS', 'NOT EXISTS' ) ) ) {
						$cleaned_clause['operator'] = $query['operator'];
					}
					$cleaned_query[] = $cleaned_clause;
				} elseif ( is_array( $query ) ) {
					$cleaned_subquery = $ns->convert_association_query( $query, $post_type );
					if ( ! empty( $cleaned_subquery ) ) {
						// All queries with children must have a relation.
						if ( ! isset( $cleaned_subquery['relation'] ) ) {
							$cleaned_subquery['relation'] = 'AND';
						}
						$cleaned_query[] = $cleaned_subquery;
					}
				}
			}
			return $cleaned_query;
		};

		$ns->sanitize_relation = function( $relation ) use ( $ns ) {
			if ( 'OR' === strtoupper( $relation ) ) {
				return 'OR';
			} else {
				return 'AND';
			}
		};


		$ns->is_first_order_clause = function( $query ) {
			return is_array( $query ) && ( empty( $query ) || array_key_exists( 'association', $query ) || array_key_exists( 'ids', $query ) );
		};

		$ns->query = function ( $criteria, $config = array() ) use ( $ns ) {
			$post_type = isset( $criteria['post_type'] ) ? $criteria['post_type'] : 'post';
			$association_query = isset( $criteria['association_query'] ) ? $criteria['association_query'] : array();
			$tax_query = isset( $criteria['tax_query'] ) ? $criteria['tax_query'] : array();

			if ( $association_query ) {
				$association_tax_query = $ns->convert_association_query( $association_query, $post_type );
				if ( $tax_query ) {
					$criteria['tax_query'] = array(
						'relation' => 'AND'
					);
					$criteria[] = $association_tax_query;
					$criteria[] = $tax_query;
				} else {
					$criteria['tax_query'] = $association_tax_query;
				}
			}

			$config = $ns->_preprocess_config( $config );

			$criteria = array_merge(
				array(
					'nopaging' => true,
					'post_status' => 'publish',
					'cache_results' => false,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false
				),
				$criteria
			);
			if ( $criteria['nopaging'] ) {
				$criteria['no_found_rows'] = true;
			}

			if ( isset( $config['fn_get'] ) ) {
				$fn_get = $config['fn_get'];
				unset( $config['fn_get'] );
			} else {
				$fn_get = array( $ns, 'get' );
			}

			$fn_filter_posts_fields = function( $fields, $query ) use ( $ns, &$config ) {
				global $wpdb;
				$new_fields = "$wpdb->posts.ID";
				$post_columns = $ns->get_post_columns();
				foreach ( $config['base_keys'] as $key ) {
					if ( in_array( $key, $post_columns ) ) {
						$new_fields .= ", $wpdb->posts.$key";
					}
				}

				return $new_fields;
			};

			$query = new WP_Query();

			$models = array();

			if ( $config['base_keys'] || $config['meta_keys'] ) {
				$essential_keys = $ns->get_essential_post_columns( $post_type );
				if ( $criteria['cache_results'] ) {
					$config['base_keys'] = array_merge( $essential_keys, $config['base_keys'] );
				} else {
					$config['base_keys'] = array_merge( array( 'post_type' ), $config['base_keys'] );
				}
				$criteria['fields'] = 'custom';
				add_filter( 'posts_fields', $fn_filter_posts_fields, 20, 2 );
				$posts = $query->query( $criteria );
				remove_filter( 'posts_fields', $fn_filter_posts_fields, 20, 2 );
				foreach ( $posts as $post ) {
					$model = call_user_func( $fn_get, $post, $config );
					$models[$post->ID] = $model;
				}
			} else {
				$criteria['fields'] = 'ids';
				$post_ids = $query->query( $criteria );
				foreach ( $post_ids as $post_id ) {
					$models[$post_id] = array(
						'ID' => $post_id,
						'post_type' => $post_type
					);
				}
			}
			// global $wpdb; birch_log( $wpdb->last_query );
			return $models;
		};

		$ns->get_term_slug = function( $model_id, $taxonomy ) {
			return $model_id . '_' . $taxonomy;
		};

		$ns->get_model_id_from_term_slug = function( $term_slug ) {
			return intval( $term_slug );
		};

		$ns->register_taxonomy = function( $taxonomy, $post_type ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, $post_type, array(
						'label' => $taxonomy,
						'public' => false,
						'show_ui' => false,
						'show_in_nav_menus' => false,
						'show_tagcloud' => false,
						'show_in_quick_edit' => false,
						'show_admin_column' => false,
						'hierarchical' => false,
						'query_var' => false,
						'rewrite' => false,
						'sort' => false
					) );
			}
		};

		$ns->new_association_taxonomy = function() {
			return uniqid( 'r' );
		};

		$ns->create_association = function( $association_def, $reverse ) use ( $ns ) {
			birch_assert( isset( $association_def['post_type'] ), 'Association should has post_type.' );
			birch_assert( isset( $association_def['association'] ), 'Association should has a name.' );
			birch_assert( isset( $reverse['post_type'] ), 'Reverse association should has post_type.' );
			birch_assert( isset( $reverse['association'] ), 'Reverse association should has a name.' );

			$association_fullname = $ns->get_association_fullname( $association_def['post_type'], $association_def['association'] );
			$reverse_fullname = $ns->get_association_fullname( $reverse['post_type'], $reverse['association'] );
			$definitions = get_option( 'birchpress_db_associations', array() );
			if ( isset( $definitions[$association_fullname] ) || isset( $definitions[$reverse_fullname] ) ) {
				throw new ErrorException( sprintf( '%s or %s has been defined.', $association_fullname, $reverse_fullname ) );
			}
			$association_tax = $ns->new_association_taxonomy();
			$reverse_tax = $ns->new_association_taxonomy();
			$definitions[$association_fullname] = array(
				'post_type' => $association_def['post_type'],
				'association' => $association_def['association'],
				'taxonomy' => $association_tax,
				'reverse' => $reverse_fullname
			);
			$definitions[$reverse_fullname] = array(
				'post_type' => $reverse['post_type'],
				'association' => $reverse['association'],
				'taxonomy' => $reverse_tax,
				'reverse' => $association_fullname
			);
			update_option( 'birchpress_db_associations', $definitions );
		};

		$ns->alter_association = function( $association_def, $new_assocation_def ) use ( $ns ) {
			birch_assert( isset( $association_def['post_type'] ), 'Association should has post_type.' );
			birch_assert( isset( $association_def['association'] ), 'Association should has a name.' );
			birch_assert( isset( $new_assocation_def['post_type'] ), 'New association should has post_type.' );
			birch_assert( isset( $new_assocation_def['association'] ), 'New association should has a name.' );
			birch_assert( $association_def['post_type'] === $new_assocation_def['post_type'], 'post_type of association cannot be altered.' );

			$definitions = get_option( 'birchpress_db_associations', array() );
			$association_fullname = $ns->get_association_fullname( $association_def['post_type'], $association_def['association'] );
			$new_association_fullname = $ns->get_association_fullname( $new_assocation_def['post_type'], $new_assocation_def['association'] );
			if ( !isset( $definitions[$association_fullname] ) ) {
				throw new ErrorException( sprintf( '%s has not been defined.', $association_fullname ) );
			}
			$definitions[$new_association_fullname] = $definitions[$association_fullname];
			$definitions[$new_association_fullname]['association'] = $new_assocation_def['association'];

			$reverse_fullname = $definitions[$association_fullname]['reverse'];
			$definitions[$reverse_fullname]['reverse'] = $new_association_fullname;

			unset( $definitions[$association_fullname] );
			update_option( 'birchpress_db_associations', $definitions );
		};

		$ns->get_association_fullname = function( $post_type, $association ) {
			return implode( '/', array( $post_type, $association ) );
		};

		$ns->get_association_definition = function( $association_fullname ) use ( $ns ) {
			$definitions = get_option( 'birchpress_db_associations', array() );
			if ( !isset( $definitions[$association_fullname] ) ) {
				throw new ErrorException( sprintf( '%s has not been defined.', $association_fullname ) );
			}
			return $definitions[$association_fullname];
		};

		$ns->get_association_taxonomy = function( $association_fullname ) use ( $ns ) {
			$association_def = $ns->get_association_definition( $association_fullname );
			return $association_def['taxonomy'];
		};

		$ns->uni_add_associated_models = function( $model_id, $association, $associated_models ) use ( $ns ) {
			$model = $ns->get( $model_id, array() );
			if ( !$model ) {
				return;
			}
			$post_type = $model['post_type'];
			$association_fullname = $ns->get_association_fullname( $post_type, $association );
			$taxonomy = $ns->get_association_taxonomy( $association_fullname );
			$ns->register_taxonomy( $taxonomy, $post_type );
			if ( empty( $associated_models ) ) {
				$terms = null;
			} else {
				$terms = array();
			}
			if ( !is_array( $associated_models ) ) {
				$associated_models = array( $associated_models );
			}
			foreach ( $associated_models as $associated_model ) {
				$term = $ns->get_term_slug( $associated_model, $taxonomy );
				$terms[] = $term;
			}
			wp_add_object_terms( $model_id, $terms, $taxonomy );
		};

		$ns->add_associated_models = function( $model_id, $association, $associated_models ) use ( $ns ) {
			$model = $ns->get( $model_id, array() );
			if ( !$model ) {
				return;
			}
			$post_type = $model['post_type'];
			$association_fullname = $ns->get_association_fullname( $post_type, $association );
			$association_def = $ns->get_association_definition( $association_fullname );
			$reverse_fullname = $association_def['reverse'];
			$reverse = $ns->get_association_definition( $reverse_fullname );
			$reverse_association = $reverse['association'];
			if ( !is_array( $associated_models ) ) {
				$associated_models = array( $associated_models );
			}
			$ns->uni_add_associated_models( $model_id, $association, $associated_models );
			foreach ( $associated_models as $associated_model ) {
				$ns->uni_add_associated_models( $associated_model, $reverse_association, $model_id );
			}
		};

		$ns->uni_remove_associated_models = function( $model_id, $association, $associated_models ) use ( $ns ) {
			$model = $ns->get( $model_id, array() );
			if ( !$model ) {
				return;
			}
			$post_type = $model['post_type'];
			$association_fullname = $ns->get_association_fullname( $post_type, $association );
			$taxonomy = $ns->get_association_taxonomy( $association_fullname );
			$ns->register_taxonomy( $taxonomy, $post_type );
			if ( empty( $associated_models ) ) {
				$terms = null;
			} else {
				$terms = array();
			}
			if ( !is_array( $associated_models ) ) {
				$associated_models = array( $associated_models );
			}
			foreach ( $associated_models as $associated_model ) {
				$term = $ns->get_term_slug( $associated_model, $taxonomy );
				$terms[] = $term;
			}
			wp_remove_object_terms( $model_id, $terms, $taxonomy );
		};

		$ns->remove_associated_models = function( $model_id, $association, $associated_models ) use ( $ns ) {
			$model = $ns->get( $model_id, array() );
			if ( !$model ) {
				return;
			}
			$post_type = $model['post_type'];
			$association_fullname = $ns->get_association_fullname( $post_type, $association );

			$association_def = $ns->get_association_definition( $association_fullname );
			$reverse_fullname = $association_def['reverse'];
			$reverse = $ns->get_association_definition( $reverse_fullname );
			$reverse_association = $reverse['association'];
			if ( !is_array( $associated_models ) ) {
				$associated_models = array( $associated_models );
			}
			$ns->uni_remove_associated_models( $model_id, $association, $associated_models );
			foreach ( $associated_models as $associated_model ) {
				$ns->uni_remove_associated_models( $associated_model, $reverse_association, $model_id );
			}
		};

	} );
