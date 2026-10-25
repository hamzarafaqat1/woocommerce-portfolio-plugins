<?php
/**
 * Extra checkout fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCCFE_Plugin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'fields' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_display' ) );
		add_filter( 'woocommerce_email_order_meta_fields', array( __CLASS__, 'email_fields' ), 10, 3 );
	}

	/**
	 * Render fields.
	 *
	 * @param WC_Checkout $checkout Checkout.
	 */
	public static function fields( $checkout ) {
		echo '<div id="wccfe_extra_fields"><h3>' . esc_html__( 'Extra details', 'wc-checkout-field-extras' ) . '</h3>';

		woocommerce_form_field(
			'wccfe_vat_number',
			array(
				'type'        => 'text',
				'class'       => array( 'form-row-wide' ),
				'label'       => __( 'VAT / Tax ID', 'wc-checkout-field-extras' ),
				'placeholder' => __( 'Optional', 'wc-checkout-field-extras' ),
				'required'    => false,
			),
			$checkout->get_value( 'wccfe_vat_number' )
		);

		woocommerce_form_field(
			'wccfe_delivery_note',
			array(
				'type'        => 'textarea',
				'class'       => array( 'form-row-wide' ),
				'label'       => __( 'Delivery instructions', 'wc-checkout-field-extras' ),
				'placeholder' => __( 'Gate code, preferred time, etc.', 'wc-checkout-field-extras' ),
				'required'    => false,
			),
			$checkout->get_value( 'wccfe_delivery_note' )
		);

		echo '</div>';
	}

	/**
	 * Optional length validation.
	 */
	public static function validate() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$vat = isset( $_POST['wccfe_vat_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wccfe_vat_number'] ) ) : '';
		if ( strlen( $vat ) > 64 ) {
			wc_add_notice( __( 'VAT / Tax ID is too long.', 'wc-checkout-field-extras' ), 'error' );
		}
	}

	/**
	 * Persist to order meta.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function save( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['wccfe_vat_number'] ) ) {
			$order->update_meta_data( '_wccfe_vat_number', sanitize_text_field( wp_unslash( $_POST['wccfe_vat_number'] ) ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['wccfe_delivery_note'] ) ) {
			$order->update_meta_data( '_wccfe_delivery_note', sanitize_textarea_field( wp_unslash( $_POST['wccfe_delivery_note'] ) ) );
		}
		$order->save();
	}

	/**
	 * Admin order screen.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function admin_display( $order ) {
		$vat  = $order->get_meta( '_wccfe_vat_number' );
		$note = $order->get_meta( '_wccfe_delivery_note' );
		if ( $vat ) {
			echo '<p><strong>' . esc_html__( 'VAT / Tax ID', 'wc-checkout-field-extras' ) . ':</strong> ' . esc_html( $vat ) . '</p>';
		}
		if ( $note ) {
			echo '<p><strong>' . esc_html__( 'Delivery instructions', 'wc-checkout-field-extras' ) . ':</strong> ' . esc_html( $note ) . '</p>';
		}
	}

	/**
	 * Email meta.
	 *
	 * @param array    $fields Fields.
	 * @param bool     $sent_to_admin Admin.
	 * @param WC_Order $order Order.
	 * @return array
	 */
	public static function email_fields( $fields, $sent_to_admin, $order ) {
		$vat = $order->get_meta( '_wccfe_vat_number' );
		if ( $vat ) {
			$fields['wccfe_vat'] = array(
				'label' => __( 'VAT / Tax ID', 'wc-checkout-field-extras' ),
				'value' => $vat,
			);
		}
		$note = $order->get_meta( '_wccfe_delivery_note' );
		if ( $note ) {
			$fields['wccfe_note'] = array(
				'label' => __( 'Delivery instructions', 'wc-checkout-field-extras' ),
				'value' => $note,
			);
		}
		return $fields;
	}
}
