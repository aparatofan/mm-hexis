<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hexis_Ajax {
    public static function init() {
        add_action( 'wp_ajax_hexis_save_assessment', array( __CLASS__, 'save_assessment' ) );
        add_action( 'wp_ajax_hexis_complete_review', array( __CLASS__, 'complete_review' ) );
    }

    public static function save_assessment() {
        check_ajax_referer( 'hexis_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in.' ), 403 );
        }

        $year = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : (int) wp_date( 'Y' );
        $virtue_key = isset( $_POST['virtue_key'] ) ? sanitize_key( $_POST['virtue_key'] ) : '';
        $direction = isset( $_POST['direction'] ) && $_POST['direction'] !== '' ? intval( $_POST['direction'] ) : null;
        $is_focus = ! empty( $_POST['is_focus'] ) ? 1 : 0;
        $note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

        if ( $direction !== null && ( $direction < -3 || $direction > 3 ) ) {
            wp_send_json_error( array( 'message' => 'Invalid direction.' ), 400 );
        }

        $valid = false;
        foreach ( Hexis_DB::get_virtues() as $group ) {
            foreach ( $group as $virtue ) {
                if ( $virtue['key'] === $virtue_key ) {
                    $valid = true;
                    break 2;
                }
            }
        }
        if ( ! $valid ) {
            wp_send_json_error( array( 'message' => 'Unknown virtue.' ), 400 );
        }

        global $wpdb;
        $review = Hexis_DB::get_or_create_review( get_current_user_id(), $year );
        $table = Hexis_DB::assessments_table();
        $now = current_time( 'mysql' );

        $existing_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE review_id=%d AND virtue_key=%s",
            $review->id,
            $virtue_key
        ) );

        $data = array(
            'review_id'   => $review->id,
            'virtue_key'  => $virtue_key,
            'direction'   => $direction,
            'is_focus'    => $is_focus,
            'note'        => $note,
            'updated_at'  => $now,
        );

        if ( $existing_id ) {
            $wpdb->update( $table, $data, array( 'id' => $existing_id ) );
        } else {
            $wpdb->insert( $table, $data );
        }

        $wpdb->update(
            Hexis_DB::reviews_table(),
            array( 'updated_at' => $now, 'status' => 'draft', 'completed_at' => null ),
            array( 'id' => $review->id )
        );

        wp_send_json_success( array( 'saved' => true ) );
    }

    public static function complete_review() {
        check_ajax_referer( 'hexis_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in.' ), 403 );
        }

        $year = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : (int) wp_date( 'Y' );
        $review = Hexis_DB::get_or_create_review( get_current_user_id(), $year );
        $now = current_time( 'mysql' );

        global $wpdb;
        $wpdb->update(
            Hexis_DB::reviews_table(),
            array( 'status' => 'complete', 'updated_at' => $now, 'completed_at' => $now ),
            array( 'id' => $review->id )
        );

        wp_send_json_success( array( 'completed_at' => $now ) );
    }
}
