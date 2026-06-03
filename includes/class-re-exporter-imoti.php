<?php
/**
 * Generates imoti.net XML export feed.
 *
 * Follows the imoti.net XML import specification.
 * All posts are exported into a single feed.xml file.
 *
 * Format:
 *  - UTF-8, no BOM
 *  - XML 1.0, root element <feed>
 *  - Header: <title>, <updated>, <AgencyID>
 *  - <Offers> contains one <Item> per post
 *  - <Description> wrapped in CDATA, HTML stripped
 *  - <Images> with <Image type="main"> for first image, plain <Image> for rest
 *  - <Extras> with one <extra> child per value
 *
 * Output path: {uploads}/re-exporter/imoti/feed.xml  (fixed — overwritten on each export)
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Exporter_Imoti
 */
class Exporter_Imoti
{

	/** @var Settings */
	private $settings;

	/** @var Field_Resolver */
	private $resolver;

	/**
	 * XML fields that hold numeric codes and may need value_map translation.
	 *
	 * @var string[]
	 */
	public static $mapped_fields = array(
		'OfferType',
		'BuildType',
		'CurrencyID',
		'EstateRegion',
		'EstateSubRegion',
	);

	/**
	 * All imoti XML field keys available for field_map configuration.
	 * Key => human label.
	 *
	 * @var array<string,string>
	 */
	public static $field_labels = array(
		'UniqueEstateID' => 'UniqueEstateID',
		'OfferType' => 'OfferType (numeric)',
		'EstateType' => 'EstateType (numeric)',
		'Address' => 'Address',
		'BuildType' => 'BuildType (numeric)',
		'Floor' => 'Floor',
		'FloorAll' => 'FloorAll',
		'Price' => 'Price',
		'PricePerSqm' => 'PricePerSqm',
		'CurrencyID' => 'CurrencyID (numeric)',
		'SurfaceAll' => 'SurfaceAll',
		'EstateRegion' => 'EstateRegion (numeric)',
		'EstateSubRegion' => 'EstateSubRegion (numeric)',
		'Latitude' => 'Latitude',
		'Longitude' => 'Longitude',
		'Zoom' => 'Zoom',
		'Pitch' => 'Pitch',
		'Heading' => 'Heading',
		'energy_class' => 'energy_class',
		'energy_usage' => 'energy_usage',
		'BrokerExclusiveContract' => 'BrokerExclusiveContract',
		'BrokerCommission' => 'BrokerCommission',
		'AllowAgency' => 'AllowAgency',
		'Extras' => 'Extras (array)',
		'Notes' => 'Notes',
		'Description' => 'Description (CDATA)',
		'Images' => 'Images (gallery)',
		'Video' => 'Video',
		'tour_url' => 'tour_url',
		'tour_provider' => 'tour_provider',
		'DateOfActuality' => 'DateOfActuality (default: post modified)',
	);

	/**
	 * @param Settings $settings
	 */
	public function __construct(Settings $settings)
	{
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
	 * @param string $out_dir   Absolute path to the output directory.
	 * @return array[]|\WP_Error  On success: array with one file descriptor
	 *                            [ 'filename', 'filepath', 'url', 'count' ]
	 */
	public function generate(array $post_ids, $out_dir)
	{
		$field_map = $this->settings->get_imoti_field_map();
		$value_map = $this->settings->get_imoti_value_map();
		$broker = $this->settings->get_imoti_broker();
		$category_tax = $this->settings->get_imoti_category_tax();
		$category_defaults = $this->settings->get_imoti_category_defaults();

		$agency_id = $this->settings->get_imoti_agency_id();
		$agency_title = $this->settings->get_imoti_agency_title();

		// ── Build XML document ───────────────────────────────────────────────
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;

		$feed = $dom->createElement('feed');
		$dom->appendChild($feed);

		$this->append_text($dom, $feed, 'title', $agency_title);
		$this->append_text($dom, $feed, 'updated', gmdate('Y-m-d H:i:s'));
		$this->append_text($dom, $feed, 'AgencyID', $agency_id);

		$offers = $dom->createElement('Offers');
		$feed->appendChild($offers);

		$count = 0;

		foreach ($post_ids as $post_id) {
			$post = get_post($post_id);
			if (!$post instanceof \WP_Post) {
				continue;
			}

			$item = $this->build_item($dom, $post, $field_map, $value_map, $broker, $category_tax, $category_defaults);
			$offers->appendChild($item);
			$count++;
		}

		if (0 === $count) {
			return new \WP_Error('no_posts', __('No posts were exported.', 're-exporter'));
		}

		$xml = $dom->saveXML();
		if (false === $xml) {
			return new \WP_Error('xml_encode', __('Could not generate XML output.', 're-exporter'));
		}

		// ── Write file (fixed path — overwritten on every export) ───────────
		$upload   = wp_upload_dir();
		$base_dir = trailingslashit($upload['basedir']) . 're-exporter/imoti';
		$base_url = trailingslashit($upload['baseurl']) . 're-exporter/imoti';

		$filename = 'feed.xml';
		$filepath = trailingslashit($base_dir) . $filename;
		$file_url = trailingslashit($base_url) . $filename;

		$write = $this->fs_write($filepath, $xml);
		if (is_wp_error($write)) {
			return $write;
		}

		return array(
			array(
				'filename'  => $filename,
				'filepath'  => $filepath,
				'url'       => $file_url,
				'count'     => $count,
				'category_name' => '',
				'link_only' => true,
			),
		);
	}

	// =========================================================================
	// Item Building
	// =========================================================================

	/**
	 * Build a single <Item> DOMElement for one post.
	 *
	 * @param \DOMDocument $dom
	 * @param \WP_Post     $post
	 * @param array        $field_map   [ imoti_field => source_key ]
	 * @param array        $value_map   [ imoti_field => [ wp_value => imoti_value ] ]
	 * @param array        $broker      Static broker info from settings.
	 * @return \DOMElement
	 */
	private function build_item(
		\DOMDocument $dom,
		\WP_Post $post,
		array $field_map,
		array $value_map,
		array $broker,
		$category_tax,
		array $category_defaults
	) {
		$item = $dom->createElement('Item');

		/**
		 * Resolve a field value via field_map and optional value_map.
		 *
		 * @param string $field  imoti XML field key.
		 * @return mixed|null
		 */
		$get = function ($field) use ($post, $field_map, $value_map) {
			$source = isset($field_map[$field]) ? $field_map[$field] : '__skip__';
			if ('__skip__' === $source || '' === $source) {
				return null;
			}


			$value = $this->resolver->resolve($post, $source, 'raw');

			// Apply value_map translation.
			if (null !== $value && !is_array($value)) {
				$str = (string) $value;
				if (isset($value_map[$field][$str])) {
					$value = $value_map[$field][$str];
				}
			}

			return $value;
		};

		// D4 fields use three-level resolution: per-post meta → category default → field_map.
		$get_d4 = function ($field) use ($post, $field_map, $value_map, $category_tax, $category_defaults) {
			return $this->resolve_d4_field($field, $post, $field_map, $value_map, $category_tax, $category_defaults);
		};

		// Fields that go through D4 resolution.
		$d4_set = array_flip(Imoti_Lookups::d4_fields());

		// ── Pre-resolve D4 fields (avoids double-resolving in loops) ─────────
		$d4_codes = array();
		foreach (Imoti_Lookups::d4_fields() as $_d4f) {
			$d4_codes[$_d4f] = $get_d4($_d4f);
		}

		// ── Auto-generate Name companions from lookup tables ──────────────────
		// Dropdown fields: code → human label via static lookup.
		$d4_names = array();
		foreach (array(
			'OfferType' => 'OfferTypeName',
			'EstateType' => 'EstateTypeName',
			'BuildType' => 'BuildTypeName',
			'CurrencyID' => 'CurrencyName',
		) as $code_fld => $name_fld) {
			$code = $d4_codes[$code_fld];
			if (null !== $code && '' !== $code) {
				$lookup = Imoti_Lookups::get($code_fld);
				$int_v = (int) $code;
				if (isset($lookup[$int_v])) {
					$d4_names[$name_fld] = $lookup[$int_v];
				}
			}
		}
		// Text fields: companion name stored in a separate post meta key.
		foreach (array(
			'EstateRegion' => 'EstateRegionName',
			'EstateSubRegion' => 'EstateSubRegionName',
		) as $code_fld => $name_fld) {
			$name_key = Imoti_Lookups::name_meta_key($code_fld);
			if ($name_key) {
				$n = (string) get_post_meta($post->ID, $name_key, true);
				if ('' !== $n) {
					$d4_names[$name_fld] = $n;
				}
			}
		}

		// ── UniqueEstateID ────────────────────────────────────────────────────
		$uid = $get('UniqueEstateID');
		$this->append_text($dom, $item, 'UniqueEstateID', (string) ($uid ?: $post->ID));

		// ── Scalar fields in spec order ───────────────────────────────────────
		$scalar_fields = array(
			'OfferType',
			'OfferTypeName',
			'EstateType',
			'EstateTypeName',
			'Address',
			'BuildType',
			'BuildTypeName',
			'Floor',
			'FloorAll',
		);
		foreach ($scalar_fields as $field) {
			if (isset($d4_names[$field])) {
				$this->append_text($dom, $item, $field, $d4_names[$field]);
				continue;
			}
			$v = isset($d4_set[$field]) ? $d4_codes[$field] : $get($field);
			if (null !== $v && '' !== $v) {
				$this->append_text($dom, $item, $field, (string) $v);
			} elseif ('Floor' === $field) {
				// Spec shows empty <Floor/> when not set.
				$item->appendChild($dom->createElement('Floor'));
			}
		}

		// ── DateOfActuality ───────────────────────────────────────────────────
		$date_v = $get('DateOfActuality');
		if ($date_v) {
			$this->append_text($dom, $item, 'DateOfActuality', (string) $date_v);
		} else {
			// Default: post modified date.
			$this->append_text($dom, $item, 'DateOfActuality', get_the_modified_date('Y-m-d', $post));
		}

		// ── Price / currency / area ───────────────────────────────────────────
		foreach (array('Price', 'PricePerSqm', 'CurrencyID', 'CurrencyName', 'SurfaceAll') as $field) {
			if (isset($d4_names[$field])) {
				$this->append_text($dom, $item, $field, $d4_names[$field]);
				continue;
			}
			$v = isset($d4_set[$field]) ? $d4_codes[$field] : $get($field);
			if (null !== $v && '' !== $v) {
				$this->append_text($dom, $item, $field, (string) $v);
			}
		}

		// ── Location ─────────────────────────────────────────────────────────
		foreach (array(
			'EstateRegion',
			'EstateRegionName',
			'EstateSubRegion',
			'EstateSubRegionName',
		) as $field) {
			if (isset($d4_names[$field])) {
				$this->append_text($dom, $item, $field, $d4_names[$field]);
				continue;
			}
			$v = isset($d4_set[$field]) ? $d4_codes[$field] : $get($field);
			if (null !== $v && '' !== $v) {
				$this->append_text($dom, $item, $field, (string) $v);
			}
		}

		// ── Coordinates ──────────────────────────────────────────────────────
		foreach (array('Latitude', 'Longitude', 'Zoom', 'Pitch', 'Heading') as $field) {
			$v = $get($field);
			if (null !== $v && '' !== $v) {
				$this->append_text($dom, $item, $field, (string) $v);
			}
		}

		// ── Energy ───────────────────────────────────────────────────────────
		foreach (array('energy_class', 'energy_usage') as $field) {
			$v = $get($field);
			if (null !== $v && '' !== $v) {
				$this->append_text($dom, $item, $field, (string) $v);
			}
		}

		// ── Broker (static from settings) ─────────────────────────────────────
		if (!empty($broker['id'])) {
			$this->append_text($dom, $item, 'BrokerID', $broker['id']);
		}
		if (!empty($broker['name'])) {
			$this->append_text($dom, $item, 'BrokerName', $broker['name']);
		}
		if (!empty($broker['phone'])) {
			$this->append_text($dom, $item, 'BrokerPhone', $broker['phone']);
		}
		if (!empty($broker['email'])) {
			$this->append_text($dom, $item, 'BrokerEmail', $broker['email']);
		}

		// ── BrokerExclusiveContract / BrokerCommission / AllowAgency ─────────
		foreach (array('BrokerExclusiveContract', 'BrokerCommission', 'AllowAgency') as $field) {
			$v = $get($field);
			if (null !== $v && '' !== $v) {
				$this->append_text($dom, $item, $field, (string) $v);
			}
		}

		// ── Extras (D4: per-post → category default → field_map) ─────────────
		$extras_val = $d4_codes['Extras']; // comma-separated codes or null; pre-resolved above
		if (null !== $extras_val && '' !== $extras_val) {
			$extras_arr = array_values(array_filter(array_map('trim', explode(',', $extras_val))));
			if (!empty($extras_arr)) {
				$extras_node = $dom->createElement('Extras');
				foreach ($extras_arr as $extra_code) {
					$this->append_text($dom, $extras_node, 'extra', $extra_code);
				}
				$item->appendChild($extras_node);
			}
		}

		// ── Notes ─────────────────────────────────────────────────────────────
		$notes_v = $get('Notes');
		if (null !== $notes_v && '' !== $notes_v) {
			$this->append_text($dom, $item, 'Notes', (string) $notes_v);
		}

		// ── Description (CDATA) ───────────────────────────────────────────────
		$desc_source = isset($field_map['Description']) ? $field_map['Description'] : '__skip__';
		if ('__skip__' !== $desc_source && '' !== $desc_source) {
			$desc_raw = $this->resolver->resolve($post, $desc_source, 'text');
			$desc_text = wp_strip_all_tags((string) $desc_raw);
			if ('' !== $desc_text) {
				$desc_node = $dom->createElement('Description');
				$desc_node->appendChild($dom->createCDATASection($desc_text));
				$item->appendChild($desc_node);
			}
		}

		// ── Images ───────────────────────────────────────────────────────────
		$images_source = isset($field_map['Images']) ? $field_map['Images'] : '__skip__';
		$images_node = $dom->createElement('Images');
		if ('__skip__' !== $images_source && '' !== $images_source) {
			$images_raw = $this->resolver->resolve($post, $images_source, 'raw');
			$images_arr = is_array($images_raw) ? array_values(array_filter($images_raw)) : array();

			$first = true;
			foreach ($images_arr as $img_url) {
				$img_node = $dom->createElement('Image', htmlspecialchars((string) $img_url, ENT_XML1, 'UTF-8'));
				if ($first) {
					$img_node->setAttribute('type', 'main');
					$first = false;
				}
				$images_node->appendChild($img_node);
			}
		}
		$item->appendChild($images_node);

		// ── Video ─────────────────────────────────────────────────────────────
		$video_v = $get('Video');
		if (null !== $video_v && '' !== $video_v) {
			$this->append_text($dom, $item, 'Video', (string) $video_v);
		}

		// ── ImageChanged (always 1 — regenerated on every export) ─────────────
		$this->append_text($dom, $item, 'ImageChanged', '1');

		// ── Virtual tour ──────────────────────────────────────────────────────
		foreach (array('tour_url', 'tour_provider') as $field) {
			$v = $get($field);
			if (null !== $v && '' !== $v) {
				$this->append_text($dom, $item, $field, (string) $v);
			}
		}

		return $item;
	}

	// =========================================================================
	// D4 Field Resolution
	// =========================================================================

	/**
	 * Resolve a D4 field value with three-level priority:
	 *   1. Per-post meta  (_re_imoti_*)
	 *   2. Category default from settings
	 *   3. Global field_map + value_map
	 *
	 * For Extras, returns a comma-separated string of codes (or empty string).
	 *
	 * @param string   $field
	 * @param \WP_Post $post
	 * @param array    $field_map
	 * @param array    $value_map
	 * @param string   $category_tax
	 * @param array    $category_defaults  [ term_slug => [ field => value ] ]
	 * @return string|null
	 */
	private function resolve_d4_field($field, \WP_Post $post, array $field_map, array $value_map, $category_tax, array $category_defaults)
	{
		// ── 1. Per-post meta ──────────────────────────────────────────────────
		$meta_key = Imoti_Lookups::meta_key($field);
		$per_post = get_post_meta($post->ID, $meta_key, true);
		if ('' !== $per_post && null !== $per_post) {
			return (string) $per_post; // Already the final imoti code — no value_map needed.
		}

		// ── 2. Category default ───────────────────────────────────────────────
		if ($category_tax) {
			$terms = get_the_terms($post->ID, $category_tax);
			if (is_array($terms)) {
				foreach ($terms as $term) {
					if (!empty($category_defaults[$term->slug][$field])) {
						return (string) $category_defaults[$term->slug][$field];
					}
				}
			}
		}

		// ── 3. Global field_map ───────────────────────────────────────────────
		$source = isset($field_map[$field]) ? $field_map[$field] : '__skip__';
		if ('__skip__' === $source || '' === $source) {
			return null;
		}

		$value = $this->resolver->resolve($post, $source, 'raw');

		if (null === $value || '' === $value) {
			return null;
		}

		if ('Extras' === $field) {
			// Array → comma-separated, then apply value_map per item.
			$arr = is_array($value)
				? array_values(array_filter(array_map('strval', $value)))
				: array_values(array_filter(array_map('trim', explode(',', (string) $value))));

			$mapped = array();
			foreach ($arr as $item) {
				$mapped[] = isset($value_map['Extras'][$item]) ? $value_map['Extras'][$item] : $item;
			}
			return implode(',', $mapped);
		}

		// Apply value_map for scalar fields.
		if (!is_array($value)) {
			$str = (string) $value;
			if (isset($value_map[$field][$str])) {
				return $value_map[$field][$str];
			}
			return $str;
		}

		return null;
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
	private function append_text(\DOMDocument $dom, \DOMElement $parent, $tag, $text)
	{
		$el = $dom->createElement($tag);
		$el->appendChild($dom->createTextNode($text));
		$parent->appendChild($el);
	}

	// =========================================================================
	// File Writing
	// =========================================================================

	/**
	 * Write content to disk via WP_Filesystem.
	 *
	 * @param string $filepath
	 * @param string $content
	 * @return true|\WP_Error
	 */
	private function fs_write($filepath, $content)
	{
		global $wp_filesystem;

		if (empty($wp_filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$dir = dirname($filepath);
		if (!$wp_filesystem->is_dir($dir)) {
			wp_mkdir_p($dir);
		}

		if (!$wp_filesystem->put_contents($filepath, $content, FS_CHMOD_FILE)) {
			return new \WP_Error(
				'xml_write',
				/* translators: %s = file path */
				sprintf(__('Could not write file: %s', 're-exporter'), $filepath)
			);
		}

		return true;
	}
}
