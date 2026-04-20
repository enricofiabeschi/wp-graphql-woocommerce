<?php
/**
 * WPObject Type - Shipping_Package_Type
 *
 * Registers ShippingPackage WPObject type
 *
 * @package WPGraphQL\WooCommerce\Type\WPObject
 * @since   0.3.2
 */

namespace WPGraphQL\WooCommerce\Type\WPObject;

/**
 * Class Shipping_Package_Type
 */
class Shipping_Package_Type {
	/**
	 * Registers type.
	 *
	 * @return void
	 */
	public static function register() {
		register_graphql_object_type(
			'ShippingPackageParcel',
			[
				'description' => __( 'Shipping package parcel', 'wp-graphql-woocommerce' ),
				'fields'      => [
					'sku'    => [ 'type' => 'String' ],
					'length' => [ 'type' => 'Float' ],
					'width'  => [ 'type' => 'Float' ],
					'height' => [ 'type' => 'Float' ],
					'weight' => [ 'type' => 'Float' ],
				],
			]
		);

		register_graphql_object_type(
			'ShippingPackage',
			[
				'description' => static function () {
					return __( 'Shipping package object', 'wp-graphql-woocommerce' );
				},
				'fields'      => [
					'packageDetails'             => [
						'type'        => 'String',
						'description' => static function () {
							return __( 'Shipping package details', 'wp-graphql-woocommerce' );
						},
						'resolve'     => static function ( $source ) {
							$product_names = [];
							foreach ( $source['contents'] as $item_id => $values ) {
								$product_names[ $item_id ] = html_entity_decode( $values['data']->get_name() . ' &times;' . $values['quantity'] );
							}

							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
							$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $source );

							return implode( ', ', $product_names );
						},
					],
					'rates'                      => [
						'type'        => [ 'list_of' => 'ShippingRate' ],
						'description' => static function () {
							return __( 'Shipping package rates', 'wp-graphql-woocommerce' );
						},
						'resolve'     => static function ( $source ) {
							return ! empty( $source['rates'] ) ? $source['rates'] : null;
						},
					],
					'parcels' => [
						'type'        => [ 'list_of' => 'ShippingPackageParcel' ],
						'description' => __( 'Flat list of product parcels contained in this shipping package.', 'wp-graphql-woocommerce' ),
						'resolve'     => static function ( $source ) {
							$parcels = [];

							if ( empty( $source['contents'] ) || ! is_array( $source['contents'] ) ) {
								return [];
							}

							foreach ( $source['contents'] as $values ) {
								if ( empty( $values['data'] ) || ! $values['data'] instanceof \WC_Product ) {
									continue;
								}

								/** @var \WC_Product $product */
								$product = $values['data'];
								$qty     = isset( $values['quantity'] ) ? (int) $values['quantity'] : 0;

								if ( $qty <= 0 ) {
									continue;
								}

								$sku = (string) $product->get_sku();
								$length = (float) wc_format_decimal( $product->get_length() ?: 0 );
								$width  = (float) wc_format_decimal( $product->get_width() ?: 0 );
								$height = (float) wc_format_decimal( $product->get_height() ?: 0 );
								$weight = (float) wc_format_decimal( $product->get_weight() ?: 0 );

								for ( $i = 0; $i < $qty; $i++ ) {
									$parcels[] = [
										'sku' 	 => $sku,
										'length' => $length,
										'width'  => $width,
										'height' => $height,
										'weight' => $weight,
									];
								}
							}

							return $parcels;
						},
					],
					'supportsShippingCalculator' => [
						'type'        => 'Boolean',
						'description' => static function () {
							return __( 'This shipping package supports the shipping calculator.', 'wp-graphql-woocommerce' );
						},
						'resolve'     => static function ( $source ) {
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
							return apply_filters( 'woocommerce_shipping_show_shipping_calculator', true, $source['index'], $source );
						},
					],
				],
			]
		);
	}
}
