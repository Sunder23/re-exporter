<?php
/**
 * Generates Realistimo XML export feed.
 *
 * Follows the Realistimo XML import specification.
 * All posts are exported into a single feed.xml file stored at a fixed path
 * (overwritten on each export).
 *
 * Format:
 *  - UTF-8, no BOM
 *  - XML 1.0, root element <feed>
 *  - Header: <updated>, <agency_uid>, <agency_name>, <agency_phone>, <agency_email>
 *  - <Brokers> with one <Broker> child (static broker from settings)
 *  - <offers> contains one <offer> per post
 *  - <images> semicolon-separated URLs, max 15
 *  - <description> HTML-stripped
 *  - Multi-value fields (heating_ids, parking_ids, exterior_ids): comma-separated
 *
 * Output path: {uploads}/re-exporter/realistimo/feed.xml  (fixed — overwritten on each export)
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Exporter_Realistimo
 */
class Exporter_Realistimo extends Abstract_Exporter {

	/** @var Settings */
	private $settings;

	/** @var Field_Resolver */
	private $resolver;

	/**
	 * All XML offer field names => human-readable labels.
	 *
	 * @var array<string,string>
	 */
	public static $field_labels = array(
		'offer_uid'               => 'offer_uid (auto: stable UUID per post)',
		'last_update'             => 'last_update (auto: post modified)',
		'offer_code'              => 'offer_code',
		'description'             => 'description',
		'images'                  => 'images (gallery, semicolon-separated)',
		'price'                   => 'price',
		'surface'                 => 'surface',
		'surface_parcel'          => 'surface_parcel',
		'currency'                => 'currency',
		'category'                => 'category',
		'subcategory'             => 'subcategory',
		'internal_id'             => 'internal_id (geo location)',
		'construction_type'       => 'construction_type',
		'floor'                   => 'floor',
		'floorAll'                => 'floorAll',
		'building_condition'      => 'building_condition',
		'build_year'              => 'build_year',
		'furnished'               => 'furnished',
		'heating_ids'             => 'heating_ids (multi, comma-separated)',
		'parking_ids'             => 'parking_ids (multi, comma-separated)',
		'new_construction'        => 'new_construction',
		'rooms_count'             => 'rooms_count',
		'bedroom_count'           => 'bedroom_count',
		'balcony_count'           => 'balcony_count',
		'bath_count'              => 'bath_count',
		'exterior_ids'            => 'exterior_ids (multi, comma-separated)',
		'land_category'           => 'land_category',
		'parcel_in_regulation'    => 'parcel_in_regulation',
		'broker_id'               => 'broker_id (from settings)',
		'youTube_link'            => 'youTube_link',
		'virtual_panorama_link'   => 'virtual_panorama_link',
		'Exclusive'               => 'Exclusive',
	);

	/**
	 * Enum fields: field name => [ value => 'value — label' ].
	 *
	 * @var array<string,array<string,string>>
	 */
	public static $enum_fields = array(
		'currency' => array(
			'eur' => 'eur — EUR',
			'лв'  => 'лв — BGN (лева)',
		),
		'category' => array(
			'продажби' => 'продажби — Sales',
			'наеми'    => 'наеми — Rentals',
		),
		'subcategory' => array(
			'СТАЯ'             => 'СТАЯ — Room',
			'1-СТАЕН'         => '1-СТАЕН — 1-room apartment',
			'2-СТАЕН'         => '2-СТАЕН — 2-room apartment',
			'3-СТАЕН'         => '3-СТАЕН — 3-room apartment',
			'4-СТАЕН'         => '4-СТАЕН — 4-room apartment',
			'МНОГОСТАЕН'      => 'МНОГОСТАЕН — Multi-room apartment',
			'МЕЗОНЕТ'         => 'МЕЗОНЕТ — Maisonette',
			'ОФИС'            => 'ОФИС — Office',
			'АТЕЛИЕ'          => 'АТЕЛИЕ — Studio',
			'АТЕЛИЕ, ТАВАН'   => 'АТЕЛИЕ, ТАВАН — Studio-loft',
			'ТАВАН'           => 'ТАВАН — Penthouse',
			'ГАРСОНИЕРА ЛУКС' => 'ГАРСОНИЕРА ЛУКС — Luxury studio',
			'ГАРСОНИЕРА'      => 'ГАРСОНИЕРА — Studio apartment',
			'ЕТАЖ ОТ КЪЩА'   => 'ЕТАЖ ОТ КЪЩА — Floor of a house',
			'КЪЩА'            => 'КЪЩА — House',
			'ВИЛА'            => 'ВИЛА — Villa',
			'МАГАЗИН'         => 'МАГАЗИН — Shop',
			'ЗАВЕДЕНИЕ'       => 'ЗАВЕДЕНИЕ — Restaurant/Bar',
			'СКЛАД'           => 'СКЛАД — Warehouse',
			'ГАРАЖ'           => 'ГАРАЖ — Garage',
			'ПРОМ. ПОМЕЩЕНИЕ' => 'ПРОМ. ПОМЕЩЕНИЕ — Industrial premises',
			'ХОТЕЛ'           => 'ХОТЕЛ — Hotel',
			'ПАРЦЕЛ'          => 'ПАРЦЕЛ — Plot',
			'ЗЕМЕДЕЛСКА ЗЕМЯ' => 'ЗЕМЕДЕЛСКА ЗЕМЯ — Agricultural land',
			'СГРАДИ'          => 'СГРАДИ — Buildings',
			'ЗАТВОРЕН КОМПЛЕКС' => 'ЗАТВОРЕН КОМПЛЕКС — Gated complex',
		),
		'construction_type' => array(
			'епк'                  => 'епк — EPC (large prefab)',
			'панел'                => 'панел — Panel',
			'тухла'                => 'тухла — Brick',
			'гредоред'             => 'гредоред — Timber/Beam',
			'метална конструкция'  => 'метална конструкция — Metal structure',
		),
		'building_condition' => array(
			'Completed'         => 'Completed',
			'UnderConstruction' => 'UnderConstruction',
			'PreConstruction'   => 'PreConstruction',
			'SemiCompleted'     => 'SemiCompleted',
			'NotCompleted'      => 'NotCompleted',
		),
		'furnished' => array(
			'0'  => '0 — Unfurnished',
			'14' => '14 — Furnished',
			'23' => '23 — Unfurnished (alt)',
			'65' => '65 — Semi-furnished',
		),
		'heating_ids' => array(
			'18' => '18 — Wood pellets',
			'35' => '35 — AC',
			'44' => '44 — Electricity',
			'46' => '46 — City central heating',
			'80' => '80 — Gas',
		),
		'parking_ids' => array(
			'52' => '52 — Garage',
			'93' => '93 — Parking space',
		),
		'new_construction' => array(
			'0' => '0 — No',
			'1' => '1 — Yes',
		),
		'exterior_ids' => array(
			'42' => '42 — Elevator',
			'10' => '10 — Asphalt road',
		),
		'land_category' => array(
			'0'  => '0 — Not available',
			'1'  => '1 — Category 1',
			'2'  => '2 — Category 2',
			'3'  => '3 — Category 3',
			'4'  => '4 — Category 4',
			'5'  => '5 — Category 5',
			'6'  => '6 — Category 6',
			'7'  => '7 — Category 7',
			'8'  => '8 — Category 8',
			'9'  => '9 — Category 9',
			'10' => '10 — Category 10',
		),
		'parcel_in_regulation' => array(
			'True'  => 'True — Yes',
			'False' => 'False — No',
		),
		'Exclusive' => array(
			'0' => '0 — No',
			'1' => '1 — Yes',
		),
	);

	/**
	 * Fields that may hold multiple comma-separated values.
	 *
	 * @var string[]
	 */
	public static $multi_fields = array( 'heating_ids', 'parking_ids', 'exterior_ids' );

	/**
	 * Source tokens that are resolved from per-post Realistimo meta.
	 * Maps token string → meta key suffix used in '_re_realistimo_{suffix}'.
	 *
	 * @var array<string,string>
	 */
	private static $realistimo_tokens = array(
		'__realistimo_currency__'            => 'currency',
		'__realistimo_category__'            => 'category',
		'__realistimo_subcategory__'         => 'subcategory',
		'__realistimo_construction_type__'   => 'construction_type',
		'__realistimo_building_condition__'  => 'building_condition',
		'__realistimo_furnished__'           => 'furnished',
		'__realistimo_heating_ids__'         => 'heating_ids',
		'__realistimo_parking_ids__'         => 'parking_ids',
		'__realistimo_new_construction__'    => 'new_construction',
		'__realistimo_exterior_ids__'        => 'exterior_ids',
		'__realistimo_land_category__'       => 'land_category',
		'__realistimo_parcel_in_regulation__' => 'parcel_in_regulation',
		'__realistimo_Exclusive__'           => 'Exclusive',
		'__realistimo_location__'            => 'location',
	);

	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		$this->resolver = new Field_Resolver();
	}

	// =========================================================================
	// Public API
	// =========================================================================

	/**
	 * Generate feed.xml for the given post IDs.
	 *
	 * @param int[]  $post_ids
	 * @param string $out_dir   Absolute path to timestamped output directory (unused — fixed path).
	 * @return array[]|\WP_Error  On success: array with one file descriptor.
	 */
	public function generate( array $post_ids, $out_dir ) {
		$field_map         = $this->settings->get_realistimo_field_map();
		$value_map         = $this->settings->get_realistimo_value_map();
		$agency            = $this->settings->get_realistimo_agency();
		$broker            = $this->settings->get_realistimo_broker();
		$category_tax      = $this->settings->get_realistimo_category_tax();
		$category_defaults = $this->settings->get_realistimo_category_defaults();

		// ── Build XML document ───────────────────────────────────────────────
		$dom               = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->formatOutput = true;

		$feed = $dom->createElement( 'feed' );
		$dom->appendChild( $feed );

		$this->append_text( $dom, $feed, 'updated', gmdate( 'Y-m-d H:i:s' ) );

		if ( ! empty( $agency['uid'] ) ) {
			$this->append_text( $dom, $feed, 'agency_uid', $agency['uid'] );
		}
		if ( ! empty( $agency['name'] ) ) {
			$this->append_text( $dom, $feed, 'agency_name', $agency['name'] );
		}
		if ( ! empty( $agency['phone'] ) ) {
			$this->append_text( $dom, $feed, 'agency_phone', $agency['phone'] );
		}
		if ( ! empty( $agency['email'] ) ) {
			$this->append_text( $dom, $feed, 'agency_email', $agency['email'] );
		}
		if ( ! empty( $agency['result_url'] ) ) {
			$this->append_text( $dom, $feed, 'result_url', $agency['result_url'] );
		}

		// ── Brokers block ────────────────────────────────────────────────────
		$brokers_node = $dom->createElement( 'Brokers' );
		$broker_node  = $dom->createElement( 'Broker' );
		if ( ! empty( $broker['name'] ) ) {
			$this->append_text( $dom, $broker_node, 'BrokerName', $broker['name'] );
		}
		if ( ! empty( $broker['id'] ) ) {
			$this->append_text( $dom, $broker_node, 'BrokerID', $broker['id'] );
		}
		if ( ! empty( $broker['phone'] ) ) {
			$this->append_text( $dom, $broker_node, 'BrokerPhone', $broker['phone'] );
		}
		if ( ! empty( $broker['email'] ) ) {
			$this->append_text( $dom, $broker_node, 'BrokerEmail', $broker['email'] );
		}
		if ( ! empty( $broker['photo'] ) ) {
			$this->append_text( $dom, $broker_node, 'BrokerPhoto', $broker['photo'] );
		}
		$brokers_node->appendChild( $broker_node );
		$feed->appendChild( $brokers_node );

		// ── Offers ───────────────────────────────────────────────────────────
		$offers_node = $dom->createElement( 'offers' );
		$feed->appendChild( $offers_node );

		$count = 0;
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$offer = $this->build_offer( $dom, $post, $field_map, $value_map, $broker, $category_tax, $category_defaults );
			$offers_node->appendChild( $offer );
			$count++;
		}

		if ( 0 === $count ) {
			return new \WP_Error( 'no_posts', __( 'No posts were exported.', 're-exporter' ) );
		}

		$xml = $dom->saveXML();
		if ( false === $xml ) {
			return new \WP_Error( 'xml_encode', __( 'Could not generate XML output.', 're-exporter' ) );
		}

		// ── Write file (fixed path — overwritten on every export) ───────────
		$filename = 'feed.xml';
		$descriptor = $this->build_fixed_file_descriptor( 'realistimo', $filename, $count, '', true );
		$filepath   = $descriptor['filepath'];

		$write = $this->write_file(
			$filepath,
			$xml,
			'xml_write',
			/* translators: %s = file path */
			__( 'Could not write file: %s', 're-exporter' )
		);
		if ( is_wp_error( $write ) ) {
			return $write;
		}

		return array( $descriptor );
	}

	// =========================================================================
	// Offer Building
	// =========================================================================

	/**
	 * Build a single <offer> DOMElement for one post.
	 *
	 * @param \DOMDocument $dom
	 * @param \WP_Post     $post
	 * @param array        $field_map   [ xml_field => source_key ]
	 * @param array        $value_map   [ xml_field => [ wp_value => realistimo_value ] ]
	 * @param array        $broker      Static broker info from settings.
	 * @return \DOMElement
	 */
	private function build_offer( \DOMDocument $dom, \WP_Post $post, array $field_map, array $value_map, array $broker, $category_tax = '', array $category_defaults = array() ) {
		$offer = $dom->createElement( 'offer' );

		/**
		 * Resolve one field value using the full resolution chain.
		 *
		 * Resolution priority:
		 *   1. source = __realistimo_{field}__ → _re_realistimo_{field} post meta
		 *   2. source = __realistimo_location__ → _re_realistimo_location post meta
		 *   3. source = __text__ or __number__ → _re_realistimo_manual_{field} post meta
		 *   4. otherwise → Field_Resolver
		 *   5. Apply value_map if not a token source.
		 *
		 * @param string $xml_field
		 * @return string|null
		 */
		$resolve = function ( $xml_field ) use ( $post, $field_map, $value_map, $category_tax, $category_defaults ) {
			$source = isset( $field_map[ $xml_field ] ) ? $field_map[ $xml_field ] : '__skip__';
			if ( '__skip__' === $source || '' === $source ) {
				// subcategory always falls through: per-post meta + category defaults
				// apply even when the field is not mapped (mirrors metabox behaviour).
				if ( 'subcategory' !== $xml_field ) {
					return null;
				}
				$source = '__realistimo_subcategory__';
			}

			// Token: __realistimo_*__ → per-post enum meta, then category default.
			if ( isset( self::$realistimo_tokens[ $source ] ) ) {
				$suffix = self::$realistimo_tokens[ $source ];
				$value  = get_post_meta( $post->ID, '_re_realistimo_' . $suffix, true );
				if ( '' !== $value ) {
					return (string) $value;
				}

				// Category default fallback (only for subcategory).
				if ( 'subcategory' === $suffix && $category_tax ) {
					$terms = get_the_terms( $post->ID, $category_tax );
					if ( is_array( $terms ) ) {
						foreach ( $terms as $term ) {
							$default = isset( $category_defaults[ $term->slug ]['subcategory'] )
								? (string) $category_defaults[ $term->slug ]['subcategory']
								: '';
							if ( '' !== $default ) {
								return $default;
							}
						}
					}
				}

				return null;
			}

			// Token: __text__ or __number__ → per-post manual meta.
			if ( '__text__' === $source || '__number__' === $source ) {
				$value = get_post_meta( $post->ID, '_re_realistimo_manual_' . $xml_field, true );
				return ( '' !== $value ) ? (string) $value : null;
			}

			// Standard field resolver.
			$value = $this->resolver->resolve( $post, $source, 'raw' );

			// Apply value_map.
			if ( null !== $value && ! is_array( $value ) ) {
				$str = (string) $value;
				if ( isset( $value_map[ $xml_field ][ $str ] ) ) {
					$value = $value_map[ $xml_field ][ $str ];
				}
			}

			return is_array( $value ) ? null : ( ( null !== $value ) ? (string) $value : null );
		};

		// ── offer_uid (stable UUID per post) ─────────────────────────────────
		$uid = get_post_meta( $post->ID, '_re_exporter_realistimo_uid', true );
		if ( empty( $uid ) ) {
			$uid = wp_generate_uuid4();
			update_post_meta( $post->ID, '_re_exporter_realistimo_uid', $uid );
		}
		$this->append_text( $dom, $offer, 'offer_uid', $uid );

		// ── last_update (auto: post modified date) ────────────────────────────
		$this->append_text( $dom, $offer, 'last_update', get_the_modified_date( 'Y-m-d H:i:s', $post ) );

		// ── offer_code ────────────────────────────────────────────────────────
		$offer_code = $resolve( 'offer_code' );
		if ( null !== $offer_code && '' !== $offer_code ) {
			$this->append_text( $dom, $offer, 'offer_code', $offer_code );
		}

		// ── description (HTML-stripped) ───────────────────────────────────────
		$desc_source = isset( $field_map['description'] ) ? $field_map['description'] : '__skip__';
		if ( '__skip__' !== $desc_source && '' !== $desc_source ) {
			$desc_raw = $this->resolver->resolve( $post, $desc_source, 'text' );
			$desc_text = wp_strip_all_tags( (string) $desc_raw );
			if ( '' !== $desc_text ) {
				$this->append_text( $dom, $offer, 'description', $desc_text );
			}
		}

		// ── images (semicolon-separated, max 15) ──────────────────────────────
		$images_source = isset( $field_map['images'] ) ? $field_map['images'] : '__skip__';
		if ( '__skip__' !== $images_source && '' !== $images_source ) {
			$images_raw = $this->resolver->resolve( $post, $images_source, 'raw' );
			$images_arr = is_array( $images_raw ) ? array_values( array_filter( $images_raw ) ) : array();
			$images_arr = array_slice( $images_arr, 0, 15 );
			if ( ! empty( $images_arr ) ) {
				$this->append_text( $dom, $offer, 'images', implode( ';', array_map( 'strval', $images_arr ) ) );
			}
		}

		// ── Simple scalar fields ──────────────────────────────────────────────
		$simple_fields = array(
			'price',
			'surface',
			'surface_parcel',
			'currency',
			'category',
			'subcategory',
			'construction_type',
			'floor',
			'floorAll',
			'building_condition',
			'build_year',
			'furnished',
			'new_construction',
			'rooms_count',
			'bedroom_count',
			'balcony_count',
			'bath_count',
			'land_category',
			'parcel_in_regulation',
			'youTube_link',
			'virtual_panorama_link',
			'Exclusive',
		);

		foreach ( $simple_fields as $xml_field ) {
			$val = $resolve( $xml_field );
			if ( null !== $val && '' !== $val ) {
				$this->append_text( $dom, $offer, $xml_field, $val );
			}
		}

		// ── internal_id (geo location) ────────────────────────────────────────
		$location_source = isset( $field_map['internal_id'] ) ? $field_map['internal_id'] : '__skip__';
		if ( '__realistimo_location__' === $location_source ) {
			$loc_val = get_post_meta( $post->ID, '_re_realistimo_location', true );
			if ( '' !== $loc_val ) {
				$this->append_text( $dom, $offer, 'internal_id', (string) $loc_val );
			}
		} elseif ( '__skip__' !== $location_source && '' !== $location_source ) {
			$loc_val = $this->resolver->resolve( $post, $location_source, 'raw' );
			if ( null !== $loc_val && '' !== $loc_val ) {
				$str = (string) $loc_val;
				if ( isset( $value_map['internal_id'][ $str ] ) ) {
					$str = $value_map['internal_id'][ $str ];
				}
				$this->append_text( $dom, $offer, 'internal_id', $str );
			}
		}

		// ── Multi-value fields (comma-separated in XML) ───────────────────────
		foreach ( self::$multi_fields as $xml_field ) {
			$source = isset( $field_map[ $xml_field ] ) ? $field_map[ $xml_field ] : '__skip__';
			if ( '__skip__' === $source || '' === $source ) {
				continue;
			}

			if ( isset( self::$realistimo_tokens[ $source ] ) ) {
				$suffix = self::$realistimo_tokens[ $source ];
				$raw    = get_post_meta( $post->ID, '_re_realistimo_' . $suffix, true );
			} else {
				$raw = $this->resolver->resolve( $post, $source, 'raw' );
				if ( is_array( $raw ) ) {
					$raw = implode( ',', array_map( 'strval', $raw ) );
				} elseif ( null !== $raw ) {
					$raw = (string) $raw;
				}
				if ( null !== $raw && '' !== $raw && isset( $value_map[ $xml_field ] ) ) {
					$parts  = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
					$mapped = array();
					foreach ( $parts as $part ) {
						$mapped[] = isset( $value_map[ $xml_field ][ $part ] ) ? $value_map[ $xml_field ][ $part ] : $part;
					}
					$raw = implode( ',', $mapped );
				}
			}

			if ( null !== $raw && '' !== $raw ) {
				$this->append_text( $dom, $offer, $xml_field, (string) $raw );
			}
		}

		// ── broker_id (always from settings) ──────────────────────────────────
		if ( ! empty( $broker['id'] ) ) {
			$this->append_text( $dom, $offer, 'broker_id', $broker['id'] );
		}

		return $offer;
	}

	// =========================================================================
	// DOM Helpers
	// =========================================================================

	/**
	 * Append a simple text element to a parent node.
	 *
	 * @param \DOMDocument $dom
	 * @param \DOMElement  $parent
	 * @param string       $tag
	 * @param string       $text
	 */
	private function append_text( \DOMDocument $dom, \DOMElement $parent, $tag, $text ) {
		$el = $dom->createElement( $tag );
		$el->appendChild( $dom->createTextNode( $text ) );
		$parent->appendChild( $el );
	}

}
