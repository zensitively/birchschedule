<?php

birch_ns( 'birchpress.model', function( $ns ) {

		$ns->get = function( $post, $config = false ) use ( $ns ) {
			global $birchpress;

			$model = $birchpress->db->get( $post, $config );
			if ( $model ) {
				return $ns->post_get( $model );
			} else {
				return false;
			}
		};

		$ns->delete = function( $id ) {
			global $birchpress;
			return $birchpress->db->delete( $id );
		};

		$ns->query = function( $criteria, $config = false ) use ( $ns ) {
			global $birchpress;

			if ( !is_array( $config ) ) {
				$config = array();
			}
			if ( !isset( $config['fn_get'] ) ) {
				$config['fn_get'] = array( $ns, 'get' );
			}
			$models = $birchpress->db->query( $criteria, $config );
			return $models;
		};

		$ns->save = function( $model, $config = false ) use ( $ns ) {
			global $birchpress;

			$model = $ns->pre_save( $model, $config );
			$result = $birchpress->db->save( $model, $config );
			return $result;
		};

		$ns->pre_save = function( $model, $config ) {
			return $model;
		};

		$ns->post_get = function( $model ) {
			return $model;
		};

		$ns->is_valid_id = function( $id ) {
			global $birchpress;

			return $birchpress->db->is_valid_id( $id );
		};

		$ns->create_association = function( $association_def, $reverse ) {
			global $birchpress;

			$birchpress->db->create_association( $association_def, $reverse );

		};

		$ns->alter_association = function( $association_def, $new_assocation_def ) {
			global $birchpress;

			$birchpress->db->alter_association( $association_def, $new_assocation_def );
		};

		$ns->add_associated_models = function( $model_id, $association, $associated_models ) use ( $ns ) {
			global $birchpress;

			$birchpress->db->add_associated_models( $model_id, $association, $associated_models );
		};

		$ns->remove_associated_models = function( $model_id, $association, $associated_models ) use ( $ns ) {
			global $birchpress;

			$birchpress->db->remove_associated_models( $model_id, $association, $associated_models );
		};

	} );
