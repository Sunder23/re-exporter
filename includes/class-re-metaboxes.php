<?php
/**
 * Registers a single tabbed metabox on the configured CPT.
 *
 * Tabs:
 *   OLX      — all CSV-template columns; per-post inputs for JSON/text/number/city
 *              sources; disabled read-only preview for auto-mapped fields.
 *   ALO      — all template fields; same editable/disabled split; location widget.
 *   Gallery  — RE Exporter Gallery (image repeater).
 *   Settings — export flag + OLX category dropdown.
 *
 * Required fields are highlighted with a red badge.
 * Auto-mapped fields show a grey, disabled preview so the user can see
 * which value will be exported without being able to change it here.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Metaboxes
 */
class Metaboxes
{

	// ── Russian labels for OLX CSV columns ───────────────────────────────────
	private static $olx_labels = array(
		'id' => 'ID',
		'title' => 'Заголовок',
		'description' => 'Описание',
		'external_id' => 'Внешний ID',
		'location_city' => 'Город',
		'location_district' => 'Район',
		'images' => 'Изображения',
		'price_value' => 'Цена',
		'price_currency' => 'Валюта',
		'heat' => 'Отопление',
		'nlf' => 'Кол-во уровней',
		'furn' => 'Мебель',
		'cstate' => 'Состояние',
		'opt' => 'Характеристики',
		'pass' => 'Пропуск',
		'floors' => 'Этажей в здании',
		'house_floors' => 'Этажей в доме',
		'floor_sale_floors' => 'Этажей (этаж дома)',
		'floor' => 'Этаж',
		'atype' => 'Тип квартиры',
		'ppsp' => 'Цена за кв.м.',
		'space' => 'Площадь (кв.м.)',
		'cyear' => 'Год постройки',
		'ctype' => 'Тип конструкции',
		'exclusive' => 'Эксклюзив',
		'yspace' => 'Площадь двора (кв.м.)',
		'power' => 'Электричество',
		'water' => 'Водоснабжение',
		'reg' => 'Регистрация',
		'pact' => 'Форма собственности',
		'cat' => 'Категория земли',
		'type' => 'Тип объекта',
		'mestopolozhenie' => 'Расположение',
		'vid' => 'Вид',
		'price_type' => 'Тип цены',
	);

	// ── Russian labels for ALO field keys ────────────────────────────────────
	private static $alo_labels = array(
		'out_id' => 'Внешний ID',
		'subcat_id' => 'ID подкатегории',
		'region_name' => 'Регион',
		'location_name' => 'Населённый пункт',
		'section_name' => 'Район',
		'section_string' => 'Район (текст)',
		'price' => 'Цена',
		'currency' => 'Валюта',
		'title' => 'Заголовок',
		'name' => 'Название',
		'name_en' => 'Название (EN)',
		'name_ru' => 'Название (RU)',
		'desc' => 'Описание',
		'desc_ru' => 'Описание (RU)',
		'desc_en' => 'Описание (EN)',
		'images' => 'Изображения',
		'area' => 'Площадь (кв.м.)',
		'area_yard' => 'Площадь двора (кв.м.)',
		'beds' => 'Спальни',
		'beds_number' => 'Количество спален',
		'type' => 'Тип объекта',
		'building_type' => 'Тип здания',
		'category' => 'Категория',
		'construction_type' => 'Тип конструкции',
		'construction_stage' => 'Стадия строительства',
		'construction_year' => 'Год постройки',
		'floor' => 'Этаж',
		'floor_number' => 'Номер этажа',
		'floor_position' => 'Расположение на этаже',
		'floors' => 'Этажей в здании',
		'house_floor' => 'Этажей в доме',
		'house_floors' => 'Этажей в доме',
		'shop_floor' => 'Этаж (торговый)',
		'features' => 'Характеристики',
		'furniture' => 'Мебель',
		'suitable_for' => 'Подходит для',
		'prefer' => 'Предпочтения',
		'situation' => 'Ситуация',
		'regulation' => 'Регламент',
		'land_category' => 'Категория земли',
		'hotel_category' => 'Категория отеля',
		'deal_type' => 'Тип сделки',
		'has_current' => 'Электричество',
		'has_water' => 'Водоснабжение',
		'has_project' => 'Проект имеется',
		'has_landlords' => 'Собственников',
		'has_equipment' => 'Оборудование',
		'season' => 'Сезонность',
		'is_working' => 'Режим работы',
		'map.lat' => 'Координата (широта)',
		'map.lon' => 'Координата (долгота)',
		'contacts.phones' => 'Телефоны',
		'contacts.email' => 'Email',
		'contacts.hide_name' => 'Скрыть имя',
		'contacts.hide_phone' => 'Скрыть телефон',
		'contacts.hide_location' => 'Скрыть местоположение',
		'contacts.hide_address' => 'Скрыть адрес',
		'cyear' => 'Год постройки',
		'ppsp' => 'Цена за кв.м.',
		'yspace' => 'Площадь двора (кв.м.)',
	);

	// ── Labels for Realistimo XML field keys ─────────────────────────────────
	private static $realistimo_labels = array(
		'offer_uid'            => 'offer_uid (auto)',
		'last_update'          => 'last_update (auto)',
		'offer_code'           => 'Код объявления',
		'description'          => 'Описание',
		'images'               => 'Изображения',
		'price'                => 'Цена',
		'surface'              => 'Площадь (кв.м.)',
		'surface_parcel'       => 'Площадь участка (кв.м.)',
		'currency'             => 'Валюта',
		'category'             => 'Категория',
		'subcategory'          => 'Подкатегория',
		'internal_id'          => 'Местоположение (geo)',
		'construction_type'    => 'Тип конструкции',
		'floor'                => 'Этаж',
		'floorAll'             => 'Этажей в здании',
		'building_condition'   => 'Состояние здания',
		'build_year'           => 'Год постройки',
		'furnished'            => 'Мебель',
		'heating_ids'          => 'Отопление (multi)',
		'parking_ids'          => 'Парковка (multi)',
		'new_construction'     => 'Новое строительство',
		'rooms_count'          => 'Кол-во комнат',
		'bedroom_count'        => 'Спальни',
		'balcony_count'        => 'Балконы',
		'bath_count'           => 'Ванные комнаты',
		'exterior_ids'         => 'Внешние удобства (multi)',
		'land_category'        => 'Категория земли',
		'parcel_in_regulation' => 'Парцел в регулация',
		'broker_id'            => 'broker_id (из настроек)',
		'youTube_link'         => 'YouTube ссылка',
		'virtual_panorama_link' => 'Виртуальная панорама',
		'Exclusive'            => 'Эксклюзив',
	);

	// ── Russian labels for imoti.net XML field keys ───────────────────────────
	private static $imoti_labels = array(
		'UniqueEstateID'          => 'Внешний ID',
		'OfferType'               => 'Тип сделки',
		'EstateType'              => 'Тип недвижимости',
		'Address'                 => 'Адрес',
		'BuildType'               => 'Тип строительства',
		'Floor'                   => 'Этаж',
		'FloorAll'                => 'Этажей в здании',
		'Price'                   => 'Цена',
		'PricePerSqm'             => 'Цена за кв.м.',
		'CurrencyID'              => 'Валюта',
		'SurfaceAll'              => 'Площадь (кв.м.)',
		'EstateRegion'            => 'Регион',
		'EstateSubRegion'         => 'Район',
		'Latitude'                => 'Широта',
		'Longitude'               => 'Долгота',
		'Zoom'                    => 'Масштаб карты',
		'Pitch'                   => 'Наклон карты',
		'Heading'                 => 'Поворот карты',
		'energy_class'            => 'Энергийный класс',
		'energy_usage'            => 'Энергопотребление',
		'BrokerExclusiveContract' => 'Эксклюзивный договор',
		'BrokerCommission'        => 'Комиссия брокера',
		'AllowAgency'             => 'Разрешить агентству',
		'Extras'                  => 'Удобства',
		'Notes'                   => 'Примечания',
		'Description'             => 'Описание',
		'Images'                  => 'Изображения',
		'Video'                   => 'Видео',
		'tour_url'                => 'URL тура',
		'tour_provider'           => 'Провайдер тура',
		'DateOfActuality'         => 'Дата актуальности',
	);

	/**
	 * Numeric validation rules for specific OLX fields.
	 *
	 * @var array<string,array>
	 */
	private static $field_validation = array(
		'cyear' => array('type' => 'integer', 'min' => 1900, 'max' => 2030),
		'space' => array('type' => 'decimal', 'min' => 0, 'max' => null),
		'yspace' => array('type' => 'decimal', 'min' => 0, 'max' => null),
		'ppsp' => array('type' => 'decimal', 'min' => 0, 'max' => null),
		'nlf' => array('type' => 'integer', 'min' => 1, 'max' => 200),
	);

	/** ALO multi-value fields stored as arrays. */
	private static $alo_array_fields = array('features', 'suitable_for', 'prefer', 'season', 'is_working');

	/** @var Settings */
	private $settings;

	/** @var OLX_Template */
	private $olx_template;

	/** @var ALO_Template */
	private $alo_template;

	/** @var Gallery_Field */
	private $gallery;

	/** @var array|null */
	private $locations_data = null;

	/**
	 * @param Settings      $settings
	 * @param OLX_Template  $olx_template
	 * @param ALO_Template  $alo_template
	 * @param Gallery_Field $gallery
	 */
	public function __construct(
		Settings $settings,
		OLX_Template $olx_template,
		ALO_Template $alo_template,
		Gallery_Field $gallery
	) {
		$this->settings = $settings;
		$this->olx_template = $olx_template;
		$this->alo_template = $alo_template;
		$this->gallery = $gallery;

		add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
		add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_post_meta_assets'));

		$this->register_list_table_hooks();

		add_action('wp_ajax_re_exp_alo_get_locations', array($this, 'ajax_alo_get_locations'));
		add_action('wp_ajax_re_exp_alo_get_sections', array($this, 'ajax_alo_get_sections'));
		add_action('wp_ajax_re_exp_alo_search_location', array($this, 'ajax_alo_search_location'));
	}

	// =========================================================================
	// Registration
	// =========================================================================

	public function register_meta_boxes()
	{
		$post_type = $this->settings->get_post_type();
		if (!$post_type) {
			return;
		}

		// One tabbed metabox replaces all separate ones.
		add_meta_box(
			're-exporter-tabs',
			__('RE Exporter', 're-exporter'),
			array($this, 'render_tabbed_metabox'),
			$post_type,
			'normal',
			'high'
		);
	}

	// =========================================================================
	// Assets
	// =========================================================================

	public function enqueue_post_meta_assets($hook)
	{
		if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
			return;
		}

		$post_type = $this->settings->get_post_type();
		if (!$post_type) {
			return;
		}

		$current_type = '';
		if ('post.php' === $hook && isset($_GET['post'])) {
			$post_obj = get_post((int) wp_unslash($_GET['post']));
			$current_type = $post_obj ? $post_obj->post_type : '';
		} elseif ('post-new.php' === $hook) {
			$current_type = isset($_GET['post_type'])
				? sanitize_key(wp_unslash($_GET['post_type']))
				: 'post';
		}

		if ($current_type !== $post_type) {
			return;
		}

		wp_enqueue_style(
			're-exporter-admin',
			RE_EXPORTER_URL . 'assets/css/re-admin.css',
			array(),
			RE_EXPORTER_VERSION
		);

		wp_enqueue_script(
			're-exporter-city-widget',
			RE_EXPORTER_URL . 'assets/js/re-city-widget.js',
			array('jquery'),
			RE_EXPORTER_VERSION,
			true
		);

		wp_enqueue_script(
			're-exporter-post-meta',
			RE_EXPORTER_URL . 'assets/js/re-post-meta.js',
			array('jquery', 're-exporter-city-widget'),
			RE_EXPORTER_VERSION,
			true
		);

		wp_enqueue_script(
			're-exporter-alo-location',
			RE_EXPORTER_URL . 'assets/js/re-alo-location-widget.js',
			array('jquery'),
			RE_EXPORTER_VERSION,
			true
		);

		wp_localize_script(
			're-exporter-post-meta',
			'reExporterMeta',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('re_exporter_nonce'),
				'i18n' => array(
					'loading' => __('Loading…', 're-exporter'),
					'noResults' => __('No results', 're-exporter'),
					'keepOriginal' => __('— Not set —', 're-exporter'),
					'aloNullSection' => __('Other (no section)', 're-exporter'),
				),
			)
		);
	}

	// =========================================================================
	// Main tabbed render
	// =========================================================================

	/**
	 * Render the single tabbed metabox that replaces all separate metaboxes.
	 *
	 * @param \WP_Post $post
	 */
	public function render_tabbed_metabox(\WP_Post $post)
	{
		// All nonces for every tab — they must always be present in POST.
		wp_nonce_field('re_exporter_mb_settings_' . $post->ID, 're_exporter_mb_settings_nonce');
		wp_nonce_field('re_exporter_mb_category_' . $post->ID, 're_exporter_mb_category_nonce');
		wp_nonce_field('re_exporter_mb_fields_' . $post->ID, 're_exporter_mb_fields_nonce');
		wp_nonce_field('re_exporter_mb_alo_fields_' . $post->ID, 're_exporter_mb_alo_fields_nonce');
		wp_nonce_field('re_exporter_mb_imoti_' . $post->ID, 're_exporter_mb_imoti_nonce');
		wp_nonce_field('re_exporter_mb_realistimo_' . $post->ID, 're_exporter_mb_realistimo_nonce');
		?>
		<div class="re-tabs" id="re-exporter-tabs" data-post="<?php echo esc_attr($post->ID); ?>">

			<ul class="re-tab-nav" role="tablist">
				<li role="presentation">
					<button type="button" class="re-tab-btn active" data-tab="re-tab-olx" role="tab">
						OLX
					</button>
				</li>
				<li role="presentation">
					<button type="button" class="re-tab-btn" data-tab="re-tab-alo" role="tab">
						ALO
					</button>
				</li>
				<li role="presentation">
					<button type="button" class="re-tab-btn" data-tab="re-tab-gallery" role="tab">
						<?php esc_html_e('Gallery', 're-exporter'); ?>
					</button>
				</li>
				<li role="presentation">
					<button type="button" class="re-tab-btn" data-tab="re-tab-imoti" role="tab">
						imoti.net
					</button>
				</li>
				<li role="presentation">
					<button type="button" class="re-tab-btn" data-tab="re-tab-realistimo" role="tab">
						Realistimo
					</button>
				</li>
				<li role="presentation">
					<button type="button" class="re-tab-btn" data-tab="re-tab-settings" role="tab">
						<?php esc_html_e('Settings', 're-exporter'); ?>
					</button>
				</li>
			</ul>

			<div id="re-tab-olx" class="re-tab-panel" role="tabpanel">
				<?php $this->render_tab_olx($post); ?>
			</div>

			<div id="re-tab-alo" class="re-tab-panel" role="tabpanel" style="display:none;">
				<?php $this->render_tab_alo($post); ?>
			</div>

			<div id="re-tab-gallery" class="re-tab-panel" role="tabpanel" style="display:none;">
				<?php $this->render_tab_gallery($post); ?>
			</div>

			<div id="re-tab-imoti" class="re-tab-panel" role="tabpanel" style="display:none;">
				<?php $this->render_tab_imoti($post); ?>
			</div>

			<div id="re-tab-realistimo" class="re-tab-panel" role="tabpanel" style="display:none;">
				<?php $this->render_tab_realistimo($post); ?>
			</div>

			<div id="re-tab-settings" class="re-tab-panel" role="tabpanel" style="display:none;">
				<?php $this->render_tab_settings($post); ?>
			</div>

		</div>
		<?php
	}

	// =========================================================================
	// Tab: Settings
	// =========================================================================

	private function render_tab_settings(\WP_Post $post)
	{
		// Export on board toggle.
		$enabled = get_post_meta($post->ID, '_re_export_enabled', true);
		$checked = ('' === $enabled || '1' === $enabled);
		?>
		<div class="re-tab-section">
			<p>
				<label style="font-size:14px;">
					<input type="hidden" name="re_export_enabled" value="0" />
					<input type="checkbox" name="re_export_enabled" value="1" <?php checked($checked); ?> />
					<strong><?php esc_html_e('Include in export', 're-exporter'); ?></strong>
				</label>
			</p>
			<p class="description">
				<?php esc_html_e('Uncheck to exclude this post from all exports.', 're-exporter'); ?>
			</p>
		</div>

		<?php
		// OLX Category (only when no taxonomy is configured).
		if (!$this->is_taxonomy_configured()):
			$saved = get_post_meta($post->ID, '_re_exporter_olx_category', true);
			$groups = $this->olx_template->get_grouped_subcategories();
			?>
			<div class="re-tab-section">
				<p><label for="re_exporter_olx_category_s" style="font-weight:600;">
						<?php esc_html_e('OLX Category', 're-exporter'); ?>
					</label></p>
				<select id="re_exporter_olx_category_s" name="re_exporter_olx_category" style="min-width:340px;max-width:100%;">
					<option value=""><?php esc_html_e('— Select category —', 're-exporter'); ?></option>
					<?php foreach ($groups as $group): ?>
						<optgroup label="<?php echo esc_attr($group['name']); ?>">
							<?php foreach ($group['subcategories'] as $sub): ?>
								<option value="<?php echo esc_attr($sub['id']); ?>" <?php selected($saved, $sub['id']); ?>>
									<?php echo esc_html($sub['name']); ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select>
				<p class="description" style="margin-top:6px;">
					<?php esc_html_e('Used for OLX export when no taxonomy is configured.', 're-exporter'); ?>
				</p>
			</div>
		<?php endif;
	}

	// =========================================================================
	// Tab: Gallery
	// =========================================================================

	private function render_tab_gallery(\WP_Post $post)
	{
		$this->gallery->render_content($post);
	}

	// =========================================================================
	// Tab: OLX
	// =========================================================================

	private function render_tab_olx(\WP_Post $post)
	{
		$subcat_id = $this->resolve_olx_subcat($post);

		if (!$subcat_id) {
			echo '<p class="description re-tab-empty">'
				. esc_html__('No OLX category assigned. Select one in the Settings tab.', 're-exporter')
				. '</p>';
			return;
		}

		// Sub info header.
		$sub_obj = $this->olx_template->get_subcategory_by_id($subcat_id);
		if ($sub_obj) {
			echo '<p class="re-tab-subcat-label">'
				. esc_html($sub_obj['name'])
				. '</p>';
		}

		$columns = $this->get_olx_columns_with_meta($post, $subcat_id);
		$required_ids = $this->get_olx_required_ids($subcat_id);

		if (empty($columns)) {
			echo '<p class="description re-tab-empty">'
				. esc_html__('No columns found for this OLX subcategory template.', 're-exporter')
				. '</p>';
			return;
		}

		echo '<table class="re-fields-table">';

		foreach ($columns as $col => $meta) {
			$is_required = in_array($col, $required_ids, true);
			$this->render_olx_field_row($col, $meta, $post, $is_required);
		}

		echo '</table>';
	}

	/**
	 * Build the full ordered map of OLX columns with their rendering metadata.
	 *
	 * @param \WP_Post $post
	 * @param string   $subcat_id
	 * @return array  [ col => [ 'display' => 'editable'|'auto'|'unmapped', ... ] ]
	 */
	private function get_olx_columns_with_meta(\WP_Post $post, $subcat_id)
	{
		$csv_path = $this->olx_template->get_csv_path($subcat_id);
		$columns = $csv_path ? $this->olx_template->get_csv_headers($csv_path) : array();

		if (empty($columns)) {
			return array();
		}

		$field_map = $this->settings->get_olx_field_map();
		$req_map = $this->settings->get_olx_required_map();
		$cat_req = isset($req_map[$subcat_id]) ? (array) $req_map[$subcat_id] : array();
		$merged = array_merge($field_map, $cat_req);

		$result = array();

		foreach ($columns as $col) {
			$source = isset($merged[$col]) ? $merged[$col] : '';

			if ('' === $source) {
				$result[$col] = array('display' => 'unmapped');
				continue;
			}

			if (self::is_per_post_source($source)) {
				// Editable — build the same data as before.
				$input_type = 'select';
				$options = array();
				$validation = array();

				if ('__text__' === $source) {
					$input_type = 'text';
				} elseif ('__number__' === $source) {
					$input_type = 'number';
					$validation = isset(self::$field_validation[$col]) ? self::$field_validation[$col] : array();
				} elseif ('__city__' === $source) {
					$input_type = 'city_api';
				} elseif ('__district__' === $source) {
					$input_type = 'district_api';
				} else {
					// __json__* source.
					$json_key = substr($source, 8);
					if ('citys' === $json_key) {
						$input_type = 'city';
					} else {
						$options = $this->olx_template->get_json_values($json_key);
						if (empty($options)) {
							$result[$col] = array('display' => 'unmapped');
							continue;
						}
					}
				}

				$result[$col] = array(
					'display' => 'editable',
					'input_type' => $input_type,
					'source' => $source,
					'options' => $options,
					'validation' => $validation,
				);
			} else {
				// Auto-resolved source — show disabled preview.
				$result[$col] = array(
					'display' => 'auto',
					'source' => $source,
					'preview' => $this->get_preview_value($post, $source),
				);
			}
		}

		return $result;
	}

	/**
	 * Render a single OLX field table row.
	 *
	 * @param string   $col
	 * @param array    $meta
	 * @param \WP_Post $post
	 * @param bool     $is_required
	 */
	private function render_olx_field_row($col, array $meta, \WP_Post $post, $is_required)
	{
		$ru_label = isset(self::$olx_labels[$col]) ? self::$olx_labels[$col] : '';
		echo '<tr class="re-field-row re-field-' . esc_attr($meta['display']) . '">';

		// Label cell.
		echo '<td class="re-field-label">';
		if ($ru_label) {
			echo '<strong>' . esc_html($ru_label);
			if ($is_required) {
				echo ' <span class="re-badge-required">' . esc_html__('req', 're-exporter') . '</span>';
			}
			echo '</strong>';
			echo '<span class="re-field-key">' . esc_html($col) . '</span>';
		} else {
			echo '<strong>' . esc_html($col);
			if ($is_required) {
				echo ' <span class="re-badge-required">' . esc_html__('req', 're-exporter') . '</span>';
			}
			echo '</strong>';
		}
		echo '</td>';

		// Input / value cell.
		echo '<td class="re-field-input">';

		$display = $meta['display'];

		if ('unmapped' === $display) {
			echo '<span class="re-field-unmapped">'
				. esc_html__('— not mapped —', 're-exporter')
				. '</span>';

		} elseif ('auto' === $display) {
			$this->render_auto_field($meta['source'], $meta['preview']);

		} else {
			// Editable field — same inputs as before.
			$input_type = $meta['input_type'];
			$saved = (string) get_post_meta($post->ID, '_re_olx_field_' . $col, true);

			if ('city' === $input_type) {
				$saved_label = (string) get_post_meta($post->ID, '_re_olx_field_location_city_label', true);
				$empty_label = esc_attr__('— Not set —', 're-exporter');
				$display_text = $saved_label ?: '';
				?>
				<div class="re-city-widget" data-empty="<?php echo $empty_label; // phpcs:ignore ?>">
					<input type="hidden" id="re_olx_field_<?php echo esc_attr($col); ?>"
						name="re_olx_fields[<?php echo esc_attr($col); ?>]" class="re-city-val"
						value="<?php echo esc_attr($saved); ?>" />
					<input type="hidden" name="re_olx_fields[location_city_label]" class="re-city-label-field"
						value="<?php echo esc_attr($saved_label); ?>" />
					<div class="re-city-trigger">
						<span class="re-city-label<?php echo ($saved && $display_text) ? '' : ' is-placeholder'; ?>" <?php if ($saved && !$saved_label): ?>data-resolve="<?php echo esc_attr($saved); ?>" <?php endif; ?>>
							<?php echo ($saved && $display_text) ? esc_html($display_text) : esc_html__('— Not set —', 're-exporter'); ?>
						</span>
						<span class="re-city-actions">
							<button type="button" class="re-city-clear-btn" <?php echo $saved ? ' style="display:inline-block;"' : ''; ?>>&#x2715;</button>
							<span class="re-city-caret"></span>
						</span>
					</div>
					<div class="re-city-panel" style="display:none;">
						<input type="text" class="re-city-search"
							placeholder="<?php esc_attr_e('Type to search city…', 're-exporter'); ?>" autocomplete="off" />
						<div class="re-city-results"></div>
					</div>
				</div>
				<?php

			} elseif ('city_api' === $input_type) {
				$saved_label = (string) get_post_meta($post->ID, '_re_olx_field_' . $col . '_label', true);
				$saved_dist = (string) get_post_meta($post->ID, '_re_olx_field_location_district', true);
				$empty_label = esc_attr__('— Not set —', 're-exporter');
				?>
				<div class="re-city-widget" data-empty="<?php echo $empty_label; // phpcs:ignore ?>">
					<input type="hidden" id="re_olx_field_<?php echo esc_attr($col); ?>"
						name="re_olx_fields[<?php echo esc_attr($col); ?>]" class="re-city-val"
						value="<?php echo esc_attr($saved); ?>" />
					<input type="hidden" name="re_olx_fields[<?php echo esc_attr($col); ?>_label]" class="re-city-label-field"
						value="<?php echo esc_attr($saved_label); ?>" />
					<input type="hidden" name="re_olx_fields[location_district]" class="re-district-val"
						value="<?php echo esc_attr($saved_dist); ?>" />
					<div class="re-city-trigger">
						<span class="re-city-label<?php echo ($saved && $saved_label) ? '' : ' is-placeholder'; ?>" <?php if ($saved && !$saved_label): ?>data-resolve="<?php echo esc_attr($saved); ?>" <?php endif; ?>>
							<?php echo ($saved && $saved_label) ? esc_html($saved_label) : esc_html__('— Not set —', 're-exporter'); ?>
						</span>
						<span class="re-city-actions">
							<button type="button" class="re-city-clear-btn" <?php echo $saved ? ' style="display:inline-block;"' : ''; ?>>&#x2715;</button>
							<span class="re-city-caret"></span>
						</span>
					</div>
					<div class="re-city-panel" style="display:none;">
						<input type="text" class="re-city-search"
							placeholder="<?php esc_attr_e('Type to search city…', 're-exporter'); ?>" autocomplete="off" />
						<div class="re-city-results"></div>
					</div>
				</div>
				<?php

			} elseif ('district_api' === $input_type) {
				echo '<span class="re-field-auto-info">'
					. esc_html($saved ?: __('— auto-filled by City —', 're-exporter'))
					. '</span>';

			} elseif ('text' === $input_type) {
				echo '<input type="text"'
					. ' id="re_olx_field_' . esc_attr($col) . '"'
					. ' name="re_olx_fields[' . esc_attr($col) . ']"'
					. ' value="' . esc_attr($saved) . '"'
					. ' class="regular-text"'
					. ' placeholder="' . esc_attr__('Enter text', 're-exporter') . '"'
					. ' />';

			} elseif ('number' === $input_type) {
				$rules = $meta['validation'];
				$attrs = ' class="small-text re-number-field"';
				$attrs .= ' placeholder="' . esc_attr__('Enter number', 're-exporter') . '"';
				$attrs .= ('integer' === ($rules['type'] ?? '')) ? ' step="1"' : ' step="0.01"';
				if (isset($rules['min']) && null !== $rules['min']) {
					$attrs .= ' min="' . esc_attr($rules['min']) . '" data-min="' . esc_attr($rules['min']) . '"';
				}
				if (isset($rules['max']) && null !== $rules['max']) {
					$attrs .= ' max="' . esc_attr($rules['max']) . '" data-max="' . esc_attr($rules['max']) . '"';
				}
				echo '<input type="number"'
					. ' id="re_olx_field_' . esc_attr($col) . '"'
					. ' name="re_olx_fields[' . esc_attr($col) . ']"'
					. ' value="' . esc_attr($saved) . '"'
					. $attrs // phpcs:ignore WordPress.Security.EscapeOutput
					. ' />';
				if (isset($rules['min'], $rules['max']) && null !== $rules['min'] && null !== $rules['max']) {
					echo ' <span class="description">' . esc_html($rules['min']) . ' – ' . esc_html($rules['max']) . '</span>';
				}

			} else {
				// select.
				echo '<select id="re_olx_field_' . esc_attr($col) . '" name="re_olx_fields[' . esc_attr($col) . ']" class="re-select-field">';
				echo '<option value="">' . esc_html__('— Not set —', 're-exporter') . '</option>';
				foreach ($meta['options'] as $opt) {
					$label = $opt['label'] !== $opt['value']
						? $opt['label'] . ' [' . $opt['value'] . ']'
						: $opt['value'];
					echo '<option value="' . esc_attr($opt['value']) . '"' . selected($saved, $opt['value'], false) . '>'
						. esc_html($label) . '</option>';
				}
				echo '</select>';
			}
		}

		echo '</td>';
		echo '</tr>';
	}

	/**
	 * Return the list of OLX field keys that are considered "required" for a subcategory.
	 * These are all keys defined in olx_required_map for the resolved subcategory.
	 *
	 * @param string $subcat_id
	 * @return string[]
	 */
	private function get_olx_required_ids($subcat_id)
	{
		// Required fields defined in the OLX template (olx_categorys.php).
		$template_required = $this->olx_template->get_required_fields($subcat_id);
		// Required fields configured in admin settings (olx_required_map).
		$req_map = $this->settings->get_olx_required_map();
		$map_keys = isset($req_map[$subcat_id]) ? array_keys((array) $req_map[$subcat_id]) : array();
		return array_values(array_unique(array_merge($template_required, $map_keys)));
	}

	// =========================================================================
	// Tab: ALO
	// =========================================================================

	private function render_tab_alo(\WP_Post $post)
	{
		$subcat_id = $this->get_alo_subcat_id_for($post);

		// Location widget runs always if configured (regardless of subcat).
		if ($this->has_alo_location_sources()) {
			$this->render_alo_location_widget($post);
		}

		// Manual text/number fields from global C1 field map.
		$this->render_alo_manual_fields($post, $subcat_id);

		if (!$subcat_id) {
			echo '<p class="description re-tab-empty">'
				. esc_html__('No ALO category assigned to this post.', 're-exporter')
				. '</p>';
			return;
		}

		$sub = $this->alo_template->get_subcategory_by_id($subcat_id);
		if ($sub) {
			echo '<p class="re-tab-subcat-label">' . esc_html($sub['name']) . '</p>';
		}

		$fields = $this->get_alo_fields_with_meta($post, $subcat_id);

		if (empty($fields)) {
			echo '<p class="description re-tab-empty">'
				. esc_html__('No fields defined for this ALO subcategory.', 're-exporter')
				. '</p>';
			return;
		}

		$required_set = array_flip($this->alo_template->get_required_fields($subcat_id));

		echo '<table class="re-fields-table">';

		foreach ($fields as $field_key => $meta) {
			$is_required = isset($required_set[$field_key]);
			$this->render_alo_field_row($field_key, $meta, $post, $is_required);
		}

		echo '</table>';
	}

	/**
	 * Render manual text/number inputs from the global ALO field map (C1).
	 *
	 * @param \WP_Post $post
	 */
	private function render_alo_manual_fields(\WP_Post $post, $subcat_id = '')
	{
		$template_fields = array();
		if ($subcat_id) {
			$template_path   = $this->alo_template->get_json_path($subcat_id);
			$template_fields = $template_path ? $this->alo_template->get_template_fields($template_path) : array();
		}

		$manual = array();
		foreach ($this->settings->get_alo_field_map() as $key => $source) {
			if ('__text__' !== $source && '__number__' !== $source) {
				continue;
			}
			// If we know the subcategory, only show fields present in its template.
			if ($template_fields && !in_array($key, $template_fields, true)) {
				continue;
			}
			$manual[$key] = $source;
		}
		if (empty($manual)) {
			return;
		}

		echo '<table class="re-fields-table" style="margin-bottom:12px;">';
		foreach ($manual as $key => $source) {
			$meta_key = '_re_alo_manual_' . sanitize_key($key);
			$saved = (string) get_post_meta($post->ID, $meta_key, true);
			$ru = isset(self::$alo_labels[$key]) ? self::$alo_labels[$key] : '';

			echo '<tr class="re-field-row re-field-editable">';
			echo '<td class="re-field-label">';
			if ($ru) {
				echo '<strong>' . esc_html($ru) . '</strong><br><span class="re-field-key">' . esc_html($key) . '</span>';
			} else {
				echo '<strong>' . esc_html($key) . '</strong>';
			}
			echo '</td>';
			echo '<td class="re-field-input">';
			if ('__number__' === $source) {
				echo '<input type="number" step="any"'
					. ' id="re_alo_manual_' . esc_attr($key) . '"'
					. ' name="re_alo_manual[' . esc_attr($key) . ']"'
					. ' value="' . esc_attr($saved) . '"'
					. ' class="small-text" />';
			} else {
				echo '<input type="text"'
					. ' id="re_alo_manual_' . esc_attr($key) . '"'
					. ' name="re_alo_manual[' . esc_attr($key) . ']"'
					. ' value="' . esc_attr($saved) . '"'
					. ' class="regular-text" />';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}

	/**
	 * Build an ordered map of ALO fields with rendering metadata.
	 * Uses the template JSON field list as the canonical column order.
	 *
	 * @param \WP_Post $post
	 * @param string   $subcat_id
	 * @return array
	 */
	private function get_alo_fields_with_meta(\WP_Post $post, $subcat_id)
	{
		// Canonical field order from the template JSON.
		$template_path = $this->alo_template->get_json_path($subcat_id);
		$template_fields = $template_path ? $this->alo_template->get_template_fields($template_path) : array();

		// Per-subcat dropdown fields (have JSON enum values).
		$template_field_map = $this->alo_template->get_field_map($subcat_id);

		// Sources for each field.
		$global_map = $this->settings->get_alo_field_map();
		$req_map = $this->settings->get_alo_required_map();
		$cat_req = isset($req_map[$subcat_id]) ? (array) $req_map[$subcat_id] : array();
		$merged_src = array_merge($global_map, $cat_req);

		$all_keys = $template_fields;

		$result = array();

		foreach ($all_keys as $field_key) {
			// Skip fields handled by manual inputs (already rendered above).
			$global_source = isset($global_map[$field_key]) ? $global_map[$field_key] : '';
			if ('__text__' === $global_source || '__number__' === $global_source) {
				continue;
			}

			// Skip location-widget fields — rendered in the widget block.
			if (self::is_alo_location_source($global_source)) {
				continue;
			}

			$source = isset($merged_src[$field_key]) ? $merged_src[$field_key] : '';

			// Always-auto fields.
			if (in_array($field_key, array('subcat_id', 'out_id'), true)) {
				$val = 'subcat_id' === $field_key ? $subcat_id : $post->ID;
				$result[$field_key] = array(
					'display' => 'always-auto',
					'source' => $field_key,
					'preview' => $val,
				);
				continue;
			}

			// Per-post dropdown from template field_map.
			if (isset($template_field_map[$field_key])) {
				$json_key = $template_field_map[$field_key];
				$options = $this->alo_template->get_json_values($json_key);
				$is_array = in_array($field_key, self::$alo_array_fields, true);
				$result[$field_key] = array(
					'display' => 'editable',
					'input_type' => $is_array ? 'checkbox' : 'select',
					'source' => $source,
					'options' => $options,
				);
				continue;
			}

			// Auto source (ACF, meta, virtual field, taxonomy).
			if ($source) {
				if (self::is_alo_location_source($source)) {
					continue; // Already in location widget.
				}
				$result[$field_key] = array(
					'display' => 'auto',
					'source' => $source,
					'preview' => $this->get_preview_value($post, $source),
				);
				continue;
			}

			// Not mapped.
			$result[$field_key] = array('display' => 'unmapped');
		}

		return $result;
	}

	/**
	 * Render a single ALO field table row.
	 *
	 * @param string   $field_key
	 * @param array    $meta
	 * @param \WP_Post $post
	 * @param bool     $is_required
	 */
	private function render_alo_field_row($field_key, array $meta, \WP_Post $post, $is_required)
	{
		$ru_label = isset(self::$alo_labels[$field_key]) ? self::$alo_labels[$field_key] : '';

		echo '<tr class="re-field-row re-field-' . esc_attr($meta['display']) . '">';

		// Label cell.
		echo '<td class="re-field-label">';
		if ($ru_label) {
			echo '<strong>' . esc_html($ru_label);
			if ($is_required) {
				echo ' <span class="re-badge-required">' . esc_html__('req', 're-exporter') . '</span>';
			}
			echo '</strong>';
			echo '<span class="re-field-key">' . esc_html($field_key) . '</span>';
		} else {
			echo '<strong>' . esc_html($field_key);
			if ($is_required) {
				echo ' <span class="re-badge-required">' . esc_html__('req', 're-exporter') . '</span>';
			}
			echo '</strong>';
		}
		echo '</td>';

		// Input / value cell.
		echo '<td class="re-field-input">';

		$display = $meta['display'];

		if ('unmapped' === $display) {
			echo '<span class="re-field-unmapped">' . esc_html__('— not mapped —', 're-exporter') . '</span>';

		} elseif (in_array($display, array('auto', 'always-auto'), true)) {
			$this->render_auto_field($meta['source'], $meta['preview']);

		} else {
			// Per-post editable: select or checkbox group.
			$saved = get_post_meta($post->ID, '_re_alo_field_' . $field_key, true);
			$options = $meta['options'];

			if (empty($options)) {
				echo '<span class="re-field-auto-info">'
					. esc_html__('(no allowed values defined)', 're-exporter')
					. '</span>';

			} elseif ('checkbox' === $meta['input_type']) {
				$saved_arr = is_array($saved) ? $saved : array();
				echo '<fieldset class="re-checkbox-group">';
				foreach ($options as $opt) {
					$checked = in_array($opt['value'], $saved_arr, true) ? ' checked' : '';
					$label = ($opt['label'] !== $opt['value'])
						? esc_html($opt['label']) . ' <span class="re-opt-key">[' . esc_html($opt['value']) . ']</span>'
						: esc_html($opt['value']);
					echo '<label class="re-checkbox-item">'
						. '<input type="checkbox"'
						. ' name="re_alo_fields[' . esc_attr($field_key) . '][]"'
						. ' value="' . esc_attr($opt['value']) . '"'
						. $checked // phpcs:ignore WordPress.Security.EscapeOutput
						. '> ' . $label . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				echo '</fieldset>';

			} else {
				// Single select.
				$saved_str = is_string($saved) ? $saved : '';
				echo '<select name="re_alo_fields[' . esc_attr($field_key) . ']" class="re-select-field">';
				echo '<option value="">' . esc_html__('— Not set —', 're-exporter') . '</option>';
				foreach ($options as $opt) {
					$label = ($opt['label'] !== $opt['value'])
						? $opt['label'] . ' [' . $opt['value'] . ']'
						: $opt['value'];
					echo '<option value="' . esc_attr($opt['value']) . '"'
						. selected($saved_str, $opt['value'], false) . '>'
						. esc_html($label) . '</option>';
				}
				echo '</select>';
			}
		}

		echo '</td>';
		echo '</tr>';
	}

	// =========================================================================
	// Auto-field (disabled preview) renderer
	// =========================================================================

	/**
	 * Render a read-only display for fields that are auto-resolved from a source.
	 *
	 * @param string $source   Source key (e.g. '__post_title__', 'acf_price').
	 * @param mixed  $preview  Pre-computed preview value.
	 */
	private function render_auto_field($source, $preview)
	{
		if (is_string($preview) && 0 === strpos($preview, '<img')) {
			echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput — already built with esc_url
		} elseif ('' !== (string) $preview) {
			echo '<span class="re-field-auto-val">' . esc_html((string) $preview) . '</span>';
		}
		echo ' <span class="re-field-auto-src">&larr; ' . esc_html($source) . '</span>';
	}

	/**
	 * Get a lightweight preview value for a source key.
	 *
	 * @param \WP_Post $post
	 * @param string   $source
	 * @return string
	 */
	private function get_preview_value(\WP_Post $post, $source)
	{
		switch ($source) {
			case '__post_id__':
				return (string) $post->ID;

			case '__post_title__':
				return $post->post_title;

			case '__post_content__':
				$text = wp_strip_all_tags($post->post_content);
				return mb_strlen($text) > 80 ? mb_substr($text, 0, 80) . '…' : $text;

			case '__post_url__':
				return get_permalink($post->ID) ?: '';

			case '__featured_image__':
				$url = get_the_post_thumbnail_url($post->ID, 'thumbnail');
				if ($url) {
					return '<img src="' . esc_url($url) . '" style="height:36px;vertical-align:middle;border-radius:2px;">';
				}
				return '—';

			case '__re_gallery__':
				$ids = get_post_meta($post->ID, Gallery_Field::META_KEY, true);
				$count = is_array($ids) ? count($ids) : 0;
				return $count
					? sprintf(_n('%d image', '%d images', $count, 're-exporter'), $count)
					: '—';
		}

		// Taxonomy source.
		if (0 === strpos($source, 'taxonomy:')) {
			$terms = get_the_terms($post->ID, substr($source, 9));
			if ($terms && !is_wp_error($terms)) {
				return implode(', ', wp_list_pluck($terms, 'name'));
			}
			return '—';
		}

		// ACF field.
		if (function_exists('get_field')) {
			$val = get_field($source, $post->ID);
			if (null !== $val && false !== $val && '' !== $val) {
				if (is_array($val)) {
					return '(array, ' . count($val) . ')';
				}
				$str = (string) $val;
				return mb_strlen($str) > 80 ? mb_substr($str, 0, 80) . '…' : $str;
			}
		}

		// Raw post meta.
		$val = get_post_meta($post->ID, $source, true);
		if ('' !== (string) $val) {
			$str = (string) $val;
			return mb_strlen($str) > 80 ? mb_substr($str, 0, 80) . '…' : $str;
		}

		return '—';
	}

	// =========================================================================
	// Tab: imoti.net
	// =========================================================================

	/**
	 * Render the imoti.net tab — all XML fields in a table.
	 *
	 * Auto-resolved fields show a disabled preview; per-post editable fields
	 * (D3 source = token) show their input widget; unmapped fields are labelled.
	 *
	 * @param \WP_Post $post
	 */
	private function render_tab_imoti( \WP_Post $post ) {
		$category_tax      = $this->settings->get_imoti_category_tax();
		$category_defaults = $this->settings->get_imoti_category_defaults();

		// Determine category defaults for this post.
		$post_defaults = array();
		if ( $category_tax ) {
			$terms = get_the_terms( $post->ID, $category_tax );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( ! empty( $category_defaults[ $term->slug ] ) ) {
						$post_defaults = $category_defaults[ $term->slug ];
						break;
					}
				}
			}
		}

		$fields = $this->get_imoti_fields_with_meta( $post );

		echo '<table class="re-fields-table">';

		foreach ( $fields as $field => $meta ) {
			$this->render_imoti_field_row( $field, $meta, $post, $post_defaults );
		}

		echo '</table>';
	}

	/**
	 * Build an ordered map of imoti fields with rendering metadata.
	 *
	 * @param \WP_Post $post
	 * @return array
	 */
	private function get_imoti_fields_with_meta( \WP_Post $post ) {
		$field_map = $this->settings->get_imoti_field_map();

		// Per-post source tokens — field is editable when D3 source = this token.
		$location_sources = array(
			'OfferType'       => '__imoti_offertype__',
			'BuildType'       => '__imoti_buildtype__',
			'CurrencyID'      => '__imoti_currency__',
			'Extras'          => '__imoti_extras__',
			'EstateRegion'    => '__imoti_region__',
			'EstateSubRegion' => '__imoti_subregion__',
		);

		$result = array();

		foreach ( array_keys( Exporter_Imoti::$field_labels ) as $field ) {
			// UniqueEstateID: show resolved value or fall back to post ID.
			if ( 'UniqueEstateID' === $field ) {
				$uid_source  = isset( $field_map[ $field ] ) ? $field_map[ $field ] : '';
				$uid_preview = ( $uid_source && '__skip__' !== $uid_source )
					? $this->get_preview_value( $post, $uid_source )
					: '';
				$result[ $field ] = array(
					'display' => 'always-auto',
					'source'  => $uid_source ?: '__post_id__',
					'preview' => ( '' !== $uid_preview ) ? $uid_preview : (string) $post->ID,
				);
				continue;
			}

			$source  = isset( $field_map[ $field ] ) ? $field_map[ $field ] : '';
			$is_skip = ( '' === $source || '__skip__' === $source );

			// D4 field with a per-post token — editable when D3 source = token.
			if ( isset( $location_sources[ $field ] ) && $source === $location_sources[ $field ] ) {
				if ( in_array( $field, Imoti_Lookups::text_fields(), true ) ) {
					$field_type = 'location_widget';
				} elseif ( 'Extras' === $field ) {
					$field_type = 'extras_checkboxes';
				} else {
					$field_type = 'dropdown';
				}
				$result[ $field ] = array(
					'display'    => 'editable',
					'field_type' => $field_type,
				);
				continue;
			}

			// D4 dropdown fields without a token are always editable (e.g. EstateType).
			if ( in_array( $field, Imoti_Lookups::dropdown_fields(), true )
				&& ! isset( $location_sources[ $field ] ) ) {
				$result[ $field ] = array(
					'display'    => 'editable',
					'field_type' => 'dropdown',
				);
				continue;
			}

			// Per-post manual text/number input.
			if ( '__text__' === $source || '__number__' === $source ) {
				$result[ $field ] = array(
					'display'    => 'editable',
					'field_type' => '__number__' === $source ? 'number' : 'text',
				);
				continue;
			}

			// Auto source.
			if ( ! $is_skip ) {
				$result[ $field ] = array(
					'display' => 'auto',
					'source'  => $source,
					'preview' => $this->get_preview_value( $post, $source ),
				);
				continue;
			}

			$result[ $field ] = array( 'display' => 'unmapped' );
		}

		return $result;
	}

	/**
	 * Render a single imoti.net field table row.
	 *
	 * @param string   $field
	 * @param array    $meta
	 * @param \WP_Post $post
	 * @param array    $post_defaults  Category defaults for this post.
	 */
	private function render_imoti_field_row( $field, array $meta, \WP_Post $post, array $post_defaults ) {
		$ru_label = isset( self::$imoti_labels[ $field ] ) ? self::$imoti_labels[ $field ] : '';
		$display  = $meta['display'];

		echo '<tr class="re-field-row re-field-' . esc_attr( $display ) . '">';

		// Label cell.
		echo '<td class="re-field-label">';
		if ( $ru_label ) {
			echo '<strong>' . esc_html( $ru_label ) . '</strong>';
			echo '<span class="re-field-key">' . esc_html( $field ) . '</span>';
		} else {
			echo '<strong>' . esc_html( $field ) . '</strong>';
		}
		echo '</td>';

		// Input / value cell.
		echo '<td class="re-field-input">';

		if ( 'unmapped' === $display ) {
			echo '<span class="re-field-unmapped">' . esc_html__( '— not mapped —', 're-exporter' ) . '</span>';

		} elseif ( in_array( $display, array( 'auto', 'always-auto' ), true ) ) {
			$this->render_auto_field( $meta['source'], $meta['preview'] );

		} else {
			// Editable per-post widget.
			$meta_key    = Imoti_Lookups::meta_key( $field );
			$saved       = (string) get_post_meta( $post->ID, $meta_key, true );
			$cat_default = isset( $post_defaults[ $field ] ) ? (string) $post_defaults[ $field ] : '';
			$placeholder = $cat_default
				? sprintf( __( 'Дефолт категории: %s', 're-exporter' ), Imoti_Lookups::label( $field, $cat_default ) )
				: __( '— Не задано —', 're-exporter' );

			if ( 'text' === $meta['field_type'] || 'number' === $meta['field_type'] ) {
				$saved_m = (string) get_post_meta( $post->ID, '_re_imoti_manual_' . $field, true );
				if ( 'number' === $meta['field_type'] ) {
					echo '<input type="number" step="any"'
						. ' name="re_imoti_fields[' . esc_attr( $field ) . ']"'
						. ' value="' . esc_attr( $saved_m ) . '"'
						. ' class="small-text" />';
				} else {
					echo '<input type="text"'
						. ' name="re_imoti_fields[' . esc_attr( $field ) . ']"'
						. ' value="' . esc_attr( $saved_m ) . '"'
						. ' class="regular-text" />';
				}
				echo '</td></tr>';
				return;

			} elseif ( 'extras_checkboxes' === $meta['field_type'] ) {
				$saved_arr   = ( '' !== $saved ) ? array_filter( array_map( 'trim', explode( ',', $saved ) ) ) : array();
				$default_arr = ( '' !== $cat_default ) ? array_filter( array_map( 'trim', explode( ',', $cat_default ) ) ) : array();
				if ( ! empty( $default_arr ) ) {
					echo '<span class="description" style="display:block;margin-bottom:4px;">'
						. esc_html( sprintf(
							__( 'Дефолт категории: %s', 're-exporter' ),
							Imoti_Lookups::label( 'Extras', $cat_default )
						) )
						. '</span>';
				}
				echo '<div style="max-height:180px;overflow-y:auto;border:1px solid #dcdcde;padding:6px 10px;border-radius:3px;column-count:2;column-gap:10px;">';
				foreach ( Imoti_Lookups::$extras as $id => $name ) {
					echo '<label style="display:block;margin-bottom:3px;break-inside:avoid;">'
						. '<input type="checkbox"'
						. ' name="re_imoti_fields[Extras][]"'
						. ' value="' . esc_attr( $id ) . '"'
						. ( in_array( (string) $id, $saved_arr, true ) ? ' checked' : '' )
						. '/> ' . esc_html( $id . ' — ' . $name )
						. '</label>';
				}
				echo '</div>';

			} elseif ( 'location_widget' === $meta['field_type'] ) {
				$saved_name  = (string) get_post_meta( $post->ID, Imoti_Lookups::name_meta_key( $field ), true );
				$empty_label = esc_attr__( '— Not set —', 're-exporter' );
				?>
				<div class="re-city-widget"
					data-imoti-field="<?php echo esc_attr( $field ); ?>"
					data-empty="<?php echo $empty_label; // phpcs:ignore ?>">
					<input type="hidden"
						name="re_imoti_fields[<?php echo esc_attr( $field ); ?>]"
						class="re-city-val"
						value="<?php echo esc_attr( $saved ); ?>" />
					<input type="hidden"
						name="re_imoti_location_name[<?php echo esc_attr( $field ); ?>]"
						class="re-city-label-field"
						value="<?php echo esc_attr( $saved_name ); ?>" />
					<div class="re-city-trigger">
						<span class="re-city-label<?php echo ( $saved && $saved_name ) ? '' : ' is-placeholder'; ?>">
							<?php echo ( $saved && $saved_name ) ? esc_html( $saved_name ) : esc_html__( '— Not set —', 're-exporter' ); ?>
						</span>
						<span class="re-city-actions">
							<button type="button" class="re-city-clear-btn" <?php echo $saved ? '' : 'style="display:none;"'; ?>>&times;</button>
							<span class="re-city-caret"></span>
						</span>
					</div>
					<div class="re-city-panel" style="display:none;">
						<input type="text" class="re-city-search"
							placeholder="<?php esc_attr_e( 'Type to search…', 're-exporter' ); ?>" />
						<div class="re-city-results"></div>
					</div>
				</div>
				<?php

			} else {
				// Dropdown.
				echo '<select name="re_imoti_fields[' . esc_attr( $field ) . ']" style="max-width:300px;">';
				echo '<option value="">' . esc_html( $placeholder ) . '</option>';
				foreach ( Imoti_Lookups::get( $field ) as $id => $name ) {
					echo '<option value="' . esc_attr( $id ) . '"' . selected( $saved, (string) $id, false ) . '>'
						. esc_html( $id . ' — ' . $name ) . '</option>';
				}
				echo '</select>';
			}
		}

		echo '</td>';
		echo '</tr>';
	}

	// =========================================================================
	// Tab: Realistimo
	// =========================================================================

	/**
	 * Render the Realistimo tab — all XML fields in a table.
	 *
	 * Fields with a __realistimo_*__ source token show a per-post selector/input.
	 * Auto-resolved fields show a disabled preview.
	 *
	 * @param \WP_Post $post
	 */
	private function render_tab_realistimo( \WP_Post $post ) {
		$field_map         = $this->settings->get_realistimo_field_map();
		$category_tax      = $this->settings->get_realistimo_category_tax();
		$category_defaults = $this->settings->get_realistimo_category_defaults();

		// Resolve category-level default subcategory for this post.
		$default_subcategory = '';
		if ( $category_tax ) {
			$terms = get_the_terms( $post->ID, $category_tax );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$val = isset( $category_defaults[ $term->slug ]['subcategory'] )
						? (string) $category_defaults[ $term->slug ]['subcategory']
						: '';
					if ( '' !== $val ) {
						$default_subcategory = $val;
						break;
					}
				}
			}
		}

		echo '<table class="re-fields-table">';

		foreach ( array_keys( Exporter_Realistimo::$field_labels ) as $field ) {
			$this->render_realistimo_field_row( $field, $field_map, $post, $default_subcategory );
		}

		echo '</table>';
	}

	/**
	 * Render a single Realistimo field row.
	 *
	 * @param string   $field
	 * @param array    $field_map  [ xml_field => source_key ]
	 * @param \WP_Post $post
	 */
	private function render_realistimo_field_row( $field, array $field_map, \WP_Post $post, $default_subcategory = '' ) {
		$ru_label = isset( self::$realistimo_labels[ $field ] ) ? self::$realistimo_labels[ $field ] : $field;
		$source   = isset( $field_map[ $field ] ) ? $field_map[ $field ] : '__skip__';

		echo '<tr class="re-field-row">';

		// Label cell.
		echo '<td class="re-field-label">';
		echo '<strong>' . esc_html( $ru_label ) . '</strong>';
		echo '<span class="re-field-key">' . esc_html( $field ) . '</span>';
		echo '</td>';

		// Input / value cell.
		echo '<td class="re-field-input">';

		// Auto-generated fields.
		if ( in_array( $field, array( 'offer_uid', 'last_update', 'broker_id' ), true ) ) {
			if ( 'offer_uid' === $field ) {
				$preview = get_post_meta( $post->ID, '_re_exporter_realistimo_uid', true );
				if ( empty( $preview ) ) {
					$preview = '(UUID generated on first export)';
				}
			} elseif ( 'last_update' === $field ) {
				$preview = get_the_modified_date( 'Y-m-d H:i:s', $post );
			} else {
				$broker  = $this->settings->get_realistimo_broker();
				$preview = isset( $broker['id'] ) ? $broker['id'] : '';
			}
			$this->render_auto_field( 'auto', $preview );
			echo '</td></tr>';
			return;
		}

		// Unmapped — but allow subcategory through if there is a category-level default.
		if ( '__skip__' === $source || '' === $source ) {
			if ( 'subcategory' === $field && '' !== $default_subcategory ) {
				$source = '__realistimo_subcategory__';
			} else {
				echo '<span class="re-field-unmapped">' . esc_html__( '— not mapped —', 're-exporter' ) . '</span>';
				echo '</td></tr>';
				return;
			}
		}

		// Location token → per-post geo widget.
		if ( '__realistimo_location__' === $source && 'internal_id' === $field ) {
			$saved_val   = (string) get_post_meta( $post->ID, '_re_realistimo_location', true );
			$saved_label = (string) get_post_meta( $post->ID, '_re_realistimo_location_label', true );
			$empty_label = esc_attr__( '— Not set —', 're-exporter' );
			?>
			<div class="re-city-widget"
				data-realistimo-field="internal_id"
				data-empty="<?php echo $empty_label; // phpcs:ignore ?>">
				<input type="hidden"
					name="re_realistimo_location"
					class="re-city-val"
					value="<?php echo esc_attr( $saved_val ); ?>" />
				<input type="hidden"
					name="re_realistimo_location_label"
					class="re-city-label-field"
					value="<?php echo esc_attr( $saved_label ); ?>" />
				<div class="re-city-trigger">
					<span class="re-city-label<?php echo ( $saved_val && $saved_label ) ? '' : ' is-placeholder'; ?>">
						<?php echo ( $saved_val && $saved_label ) ? esc_html( $saved_label ) : esc_html__( '— Not set —', 're-exporter' ); ?>
					</span>
					<span class="re-city-actions">
						<button type="button" class="re-city-clear-btn" <?php echo $saved_val ? '' : 'style="display:none;"'; ?>>&times;</button>
						<span class="re-city-caret"></span>
					</span>
				</div>
				<div class="re-city-panel" style="display:none;">
					<input type="text" class="re-city-search"
						placeholder="<?php esc_attr_e( 'Type to search location…', 're-exporter' ); ?>" />
					<div class="re-city-results"></div>
				</div>
			</div>
			<?php
			echo '</td></tr>';
			return;
		}

		// Enum / per-post token source (e.g. __realistimo_currency__).
		if ( 0 === strpos( $source, '__realistimo_' ) && isset( Exporter_Realistimo::$enum_fields[ $field ] ) ) {
			// Derive the meta key suffix from the token.
			$suffix  = substr( $source, strlen( '__realistimo_' ), -2 ); // strip leading '__realistimo_' and trailing '__'
			$meta_key = '_re_realistimo_' . $suffix;
			$saved   = (string) get_post_meta( $post->ID, $meta_key, true );
			$is_multi = in_array( $field, Exporter_Realistimo::$multi_fields, true );
			$enum    = Exporter_Realistimo::$enum_fields[ $field ];

			if ( $is_multi ) {
				$saved_arr = array_filter( array_map( 'trim', explode( ',', $saved ) ) );
				echo '<div style="max-height:160px;overflow-y:auto;border:1px solid #dcdcde;padding:6px 10px;border-radius:3px;">';
				foreach ( $enum as $enum_val => $enum_label ) {
					echo '<label style="display:block;margin-bottom:3px;">'
						. '<input type="checkbox"'
						. ' name="re_realistimo_fields[' . esc_attr( $field ) . '][]"'
						. ' value="' . esc_attr( $enum_val ) . '"'
						. ( in_array( (string) $enum_val, $saved_arr, true ) ? ' checked' : '' )
						. '/> ' . esc_html( $enum_label )
						. '</label>';
				}
				echo '</div>';
			} else {
				if ( 'subcategory' === $field && '' !== $default_subcategory ) {
					$enum_labels = Exporter_Realistimo::$enum_fields['subcategory'];
					$default_label = isset( $enum_labels[ $default_subcategory ] ) ? $enum_labels[ $default_subcategory ] : $default_subcategory;
					$not_set_label = sprintf( __( 'Дефолт категории: %s', 're-exporter' ), $default_label );
				} else {
					$not_set_label = __( '— Not set —', 're-exporter' );
				}
				echo '<select name="re_realistimo_fields[' . esc_attr( $field ) . ']" style="max-width:320px;">';
				echo '<option value="">' . esc_html( $not_set_label ) . '</option>';
				foreach ( $enum as $enum_val => $enum_label ) {
					echo '<option value="' . esc_attr( $enum_val ) . '"' . selected( $saved, (string) $enum_val, false ) . '>'
						. esc_html( $enum_label ) . '</option>';
				}
				echo '</select>';
			}

			echo '</td></tr>';
			return;
		}

		// Manual text/number input.
		if ( '__text__' === $source || '__number__' === $source ) {
			$saved_m = (string) get_post_meta( $post->ID, '_re_realistimo_manual_' . $field, true );
			if ( '__number__' === $source ) {
				echo '<input type="number" step="any"'
					. ' name="re_realistimo_manual[' . esc_attr( $field ) . ']"'
					. ' value="' . esc_attr( $saved_m ) . '"'
					. ' class="small-text" />';
			} else {
				echo '<input type="text"'
					. ' name="re_realistimo_manual[' . esc_attr( $field ) . ']"'
					. ' value="' . esc_attr( $saved_m ) . '"'
					. ' class="regular-text" />';
			}
			echo '</td></tr>';
			return;
		}

		// Standard auto-resolved source.
		$preview = $this->get_preview_value( $post, $source );
		$this->render_auto_field( $source, $preview );

		echo '</td></tr>';
	}

	// =========================================================================
	// Save
	// =========================================================================

	public function save_meta_boxes($post_id, \WP_Post $post)
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (wp_is_post_revision($post_id)) {
			return;
		}

		$post_type = $this->settings->get_post_type();
		if ($post->post_type !== $post_type) {
			return;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// ── Settings: Export on board ─────────────────────────────────────────
		$nonce1 = isset($_POST['re_exporter_mb_settings_nonce'])
			? sanitize_text_field(wp_unslash($_POST['re_exporter_mb_settings_nonce'])) : '';
		if (wp_verify_nonce($nonce1, 're_exporter_mb_settings_' . $post_id)) {
			// Hidden field + checkbox pattern: value is always '0' or '1'.
			$raw = isset($_POST['re_export_enabled'])
				? sanitize_text_field(wp_unslash($_POST['re_export_enabled']))
				: '';
			$enabled = ('1' === $raw) ? '1' : '0';
			update_post_meta($post_id, '_re_export_enabled', $enabled);
		}

		// ── Settings: OLX Category ────────────────────────────────────────────
		$nonce2 = isset($_POST['re_exporter_mb_category_nonce'])
			? sanitize_text_field(wp_unslash($_POST['re_exporter_mb_category_nonce'])) : '';
		if (wp_verify_nonce($nonce2, 're_exporter_mb_category_' . $post_id)) {
			$category = isset($_POST['re_exporter_olx_category'])
				? sanitize_text_field(wp_unslash($_POST['re_exporter_olx_category'])) : '';
			if ($category) {
				if ($this->olx_template->get_subcategory_by_id($category)) {
					update_post_meta($post_id, '_re_exporter_olx_category', $category);
				}
			} else {
				delete_post_meta($post_id, '_re_exporter_olx_category');
			}
		}

		// ── OLX Field Values ──────────────────────────────────────────────────
		$nonce3 = isset($_POST['re_exporter_mb_fields_nonce'])
			? sanitize_text_field(wp_unslash($_POST['re_exporter_mb_fields_nonce'])) : '';
		if (wp_verify_nonce($nonce3, 're_exporter_mb_fields_' . $post_id)) {
			$fields_input = (isset($_POST['re_olx_fields']) && is_array($_POST['re_olx_fields']))
				? array_map('sanitize_text_field', wp_unslash($_POST['re_olx_fields']))
				: array();

			// Save companion _label fields then remove from loop.
			foreach (array_keys($fields_input) as $fkey) {
				if ('_label' === substr($fkey, -6)) {
					$label_col = sanitize_key($fkey);
					$label_val = $fields_input[$fkey];
					if ('' === $label_val) {
						delete_post_meta($post_id, '_re_olx_field_' . $label_col);
					} else {
						update_post_meta($post_id, '_re_olx_field_' . $label_col, $label_val);
					}
					unset($fields_input[$fkey]);
				}
			}

			foreach ($fields_input as $col => $value) {
				$col = sanitize_key($col);
				$value = $this->validate_field_value($col, $value);
				if ('' === $value) {
					delete_post_meta($post_id, '_re_olx_field_' . $col);
				} else {
					update_post_meta($post_id, '_re_olx_field_' . $col, $value);
				}
			}
		}

		// ── ALO Field Values ──────────────────────────────────────────────────
		$nonce4 = isset($_POST['re_exporter_mb_alo_fields_nonce'])
			? sanitize_text_field(wp_unslash($_POST['re_exporter_mb_alo_fields_nonce'])) : '';
		if (wp_verify_nonce($nonce4, 're_exporter_mb_alo_fields_' . $post_id)) {
			// Manual text/number.
			if (isset($_POST['re_alo_manual']) && is_array($_POST['re_alo_manual'])) {
				foreach ($_POST['re_alo_manual'] as $field_key => $val) { // phpcs:ignore
					$field_key = sanitize_key($field_key);
					$val = sanitize_text_field(wp_unslash((string) $val));
					$meta_key = '_re_alo_manual_' . $field_key;
					if ('' === $val) {
						delete_post_meta($post_id, $meta_key);
					} else {
						update_post_meta($post_id, $meta_key, $val);
					}
				}
			}

			// Location widget values.
			if (isset($_POST['re_alo_location']) && is_array($_POST['re_alo_location'])) {
				$loc_input = array_map('sanitize_text_field', wp_unslash((array) $_POST['re_alo_location']));
				$loc_keys = array(
					'region_name'    => '_re_alo_region_name',
					'location_name'  => '_re_alo_location_name',
					'section_name'   => '_re_alo_section_name',
					'section_string' => '_re_alo_section_string',
				);
				foreach ($loc_keys as $input_key => $meta_key) {
					$val = isset($loc_input[$input_key]) ? $loc_input[$input_key] : '';
					if ('' === $val) {
						delete_post_meta($post_id, $meta_key);
					} else {
						update_post_meta($post_id, $meta_key, $val);
					}
				}
			}

			// Display labels for ALO location city-widgets.
			if (isset($_POST['alo_location_label']) && is_array($_POST['alo_location_label'])) {
				$label_input = array_map('sanitize_text_field', wp_unslash((array) $_POST['alo_location_label']));
				$label_keys = array(
					'region_name'   => '_re_alo_region_label',
					'location_name' => '_re_alo_location_label',
					'section_name'  => '_re_alo_section_label',
				);
				foreach ($label_keys as $input_key => $meta_key) {
					$val = isset($label_input[$input_key]) ? $label_input[$input_key] : '';
					if ('' === $val) {
						delete_post_meta($post_id, $meta_key);
					} else {
						update_post_meta($post_id, $meta_key, $val);
					}
				}
			}

			// ALO dropdown/checkbox fields.
			$alo_subcat = $this->get_alo_subcat_id_for($post);
			if ($alo_subcat) {
				$alo_field_map = $this->alo_template->get_field_map($alo_subcat);
				$alo_input = (isset($_POST['re_alo_fields']) && is_array($_POST['re_alo_fields']))
					? wp_unslash($_POST['re_alo_fields']) // phpcs:ignore
					: array();

				foreach ($alo_field_map as $field_key => $json_key) {
					$meta_key = '_re_alo_field_' . sanitize_key($field_key);

					if (in_array($field_key, self::$alo_array_fields, true)) {
						$vals = (isset($alo_input[$field_key]) && is_array($alo_input[$field_key]))
							? array_values(array_map('sanitize_text_field', (array) $alo_input[$field_key]))
							: array();
						if (empty($vals)) {
							delete_post_meta($post_id, $meta_key);
						} else {
							update_post_meta($post_id, $meta_key, $vals);
						}
					} else {
						$val = isset($alo_input[$field_key])
							? sanitize_text_field((string) $alo_input[$field_key]) : '';
						if ('' === $val) {
							delete_post_meta($post_id, $meta_key);
						} else {
							update_post_meta($post_id, $meta_key, $val);
						}
					}
				}
			}
		}

		// ── imoti.net D4 Field Values ─────────────────────────────────────────
		$nonce5 = isset($_POST['re_exporter_mb_imoti_nonce'])
			? sanitize_text_field(wp_unslash($_POST['re_exporter_mb_imoti_nonce'])) : '';
		if (wp_verify_nonce($nonce5, 're_exporter_mb_imoti_' . $post_id)) {
			$input = (isset($_POST['re_imoti_fields']) && is_array($_POST['re_imoti_fields']))
				? $_POST['re_imoti_fields'] // phpcs:ignore — sanitised per-field below
				: array();

			// Scalar D4 fields (dropdown + text inputs).
			foreach (array_merge(Imoti_Lookups::dropdown_fields(), Imoti_Lookups::text_fields()) as $field) {
				$meta_key = Imoti_Lookups::meta_key($field);
				$value    = isset($input[$field]) ? sanitize_text_field(wp_unslash((string) $input[$field])) : '';
				if ('' === $value) {
					delete_post_meta($post_id, $meta_key);
				} else {
					update_post_meta($post_id, $meta_key, $value);
				}
			}

			// Companion display-name metas for location widgets (EstateRegion, EstateSubRegion).
			$name_input = (isset($_POST['re_imoti_location_name']) && is_array($_POST['re_imoti_location_name']))
				? $_POST['re_imoti_location_name'] // phpcs:ignore — sanitised below
				: array();
			foreach (Imoti_Lookups::text_fields() as $field) {
				$name_key = Imoti_Lookups::name_meta_key($field);
				$name_val = isset($name_input[$field]) ? sanitize_text_field(wp_unslash((string) $name_input[$field])) : '';
				if ('' === $name_val) {
					delete_post_meta($post_id, $name_key);
				} else {
					update_post_meta($post_id, $name_key, $name_val);
				}
			}

			// Extras — checkboxes → comma-separated string of codes.
			$extras_raw = (isset($input['Extras']) && is_array($input['Extras']))
				? array_map('sanitize_text_field', wp_unslash((array) $input['Extras']))
				: array();
			$extras_value = implode(',', array_filter(array_map('trim', $extras_raw)));
			$extras_key   = Imoti_Lookups::meta_key('Extras');
			if ('' === $extras_value) {
				delete_post_meta($post_id, $extras_key);
			} else {
				update_post_meta($post_id, $extras_key, $extras_value);
			}

			// Per-post manual text/number fields.
			foreach ($this->settings->get_imoti_field_map() as $field => $source) {
				if ('__text__' !== $source && '__number__' !== $source) {
					continue;
				}
				$manual_key = '_re_imoti_manual_' . $field;
				$value      = isset($input[$field]) ? sanitize_text_field(wp_unslash((string) $input[$field])) : '';
				if ('' === $value) {
					delete_post_meta($post_id, $manual_key);
				} else {
					update_post_meta($post_id, $manual_key, $value);
				}
			}
		}

		// ── Realistimo Field Values ───────────────────────────────────────────
		$nonce_r = isset($_POST['re_exporter_mb_realistimo_nonce'])
			? sanitize_text_field(wp_unslash($_POST['re_exporter_mb_realistimo_nonce'])) : '';
		if (wp_verify_nonce($nonce_r, 're_exporter_mb_realistimo_' . $post_id)) {
			// Per-post location (internal_id).
			$loc_val   = isset($_POST['re_realistimo_location'])       ? sanitize_text_field(wp_unslash($_POST['re_realistimo_location']))       : '';
			$loc_label = isset($_POST['re_realistimo_location_label']) ? sanitize_text_field(wp_unslash($_POST['re_realistimo_location_label'])) : '';
			if ('' === $loc_val) {
				delete_post_meta($post_id, '_re_realistimo_location');
				delete_post_meta($post_id, '_re_realistimo_location_label');
			} else {
				update_post_meta($post_id, '_re_realistimo_location', $loc_val);
				update_post_meta($post_id, '_re_realistimo_location_label', $loc_label);
			}

			// Per-post enum / dropdown / checkbox fields.
			$realistimo_input = (isset($_POST['re_realistimo_fields']) && is_array($_POST['re_realistimo_fields']))
				? $_POST['re_realistimo_fields'] // phpcs:ignore — sanitised per-field below
				: array();

			foreach (array_keys(Exporter_Realistimo::$enum_fields) as $field) {
				$meta_key = '_re_realistimo_' . $field;
				$is_multi = in_array($field, Exporter_Realistimo::$multi_fields, true);

				if ($is_multi) {
					$vals = (isset($realistimo_input[$field]) && is_array($realistimo_input[$field]))
						? array_filter(array_map('sanitize_text_field', wp_unslash((array) $realistimo_input[$field])))
						: array();
					$csv = implode(',', $vals);
					if ('' === $csv) {
						delete_post_meta($post_id, $meta_key);
					} else {
						update_post_meta($post_id, $meta_key, $csv);
					}
				} else {
					$val = isset($realistimo_input[$field]) ? sanitize_text_field(wp_unslash((string) $realistimo_input[$field])) : '';
					if ('' === $val) {
						delete_post_meta($post_id, $meta_key);
					} else {
						update_post_meta($post_id, $meta_key, $val);
					}
				}
			}

			// Manual text/number fields.
			$realistimo_manual = (isset($_POST['re_realistimo_manual']) && is_array($_POST['re_realistimo_manual']))
				? $_POST['re_realistimo_manual'] // phpcs:ignore — sanitised below
				: array();
			$realistimo_field_map = $this->settings->get_realistimo_field_map();
			foreach ($realistimo_field_map as $field => $src) {
				if ('__text__' !== $src && '__number__' !== $src) {
					continue;
				}
				$manual_key = '_re_realistimo_manual_' . $field;
				$val = isset($realistimo_manual[$field]) ? sanitize_text_field(wp_unslash((string) $realistimo_manual[$field])) : '';
				if ('' === $val) {
					delete_post_meta($post_id, $manual_key);
				} else {
					update_post_meta($post_id, $manual_key, $val);
				}
			}
		}

		// ── Quick Edit: Export flag ───────────────────────────────────────────
		$qe_nonce = isset($_POST['re_exporter_qe_nonce'])
			? sanitize_text_field(wp_unslash($_POST['re_exporter_qe_nonce'])) : '';
		if (wp_verify_nonce($qe_nonce, 're_exporter_quick_edit')) {
			// Hidden field + checkbox pattern: value is always '0' or '1'.
			$raw = isset($_POST['re_export_enabled'])
				? sanitize_text_field(wp_unslash($_POST['re_export_enabled']))
				: '';
			$enabled = ('1' === $raw) ? '1' : '0';
			update_post_meta($post_id, '_re_export_enabled', $enabled);
		}

		$bulk_val = isset($_REQUEST['re_export_enabled_bulk'])
			? sanitize_text_field(wp_unslash($_REQUEST['re_export_enabled_bulk']))
			: '';

		if ($bulk_val === '') {
			return;
		}

		update_post_meta(
			$post_id,
			'_re_export_enabled',
			$bulk_val === '1' ? '1' : '0'
		);
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	private function validate_field_value($col, $value)
	{
		if ('' === $value) {
			return '';
		}
		if (!isset(self::$field_validation[$col])) {
			return $value;
		}
		$rules = self::$field_validation[$col];
		if (!is_numeric($value)) {
			return '';
		}
		$num = (float) $value;
		if (isset($rules['min']) && null !== $rules['min'] && $num < (float) $rules['min']) {
			$num = (float) $rules['min'];
		}
		if (isset($rules['max']) && null !== $rules['max'] && $num > (float) $rules['max']) {
			$num = (float) $rules['max'];
		}
		return 'integer' === $rules['type'] ? (string) (int) round($num) : (string) $num;
	}

	private function is_taxonomy_configured()
	{
		return '' !== $this->settings->get_olx_category_tax();
	}

	private static function is_per_post_source($source)
	{
		return 0 === strpos($source, '__json__')
			|| '__text__' === $source
			|| '__number__' === $source
			|| '__city__' === $source
			|| '__district__' === $source;
	}

	private function has_alo_location_sources()
	{
		foreach ($this->settings->get_alo_field_map() as $source) {
			if (self::is_alo_location_source($source)) {
				return true;
			}
		}
		return false;
	}

	private static function is_alo_location_source($source)
	{
		return in_array($source, array(
			'__alo_region__',
			'__alo_location__',
			'__alo_section__',
			'__alo_section_string__',
		), true);
	}

	/**
	 * Resolve the OLX subcategory ID for a post, applying deal-type routing.
	 *
	 * @param \WP_Post $post
	 * @return string
	 */
	private function resolve_olx_subcat(\WP_Post $post)
	{
		$category_tax = $this->settings->get_olx_category_tax();
		$category_map = $this->settings->get_olx_category_map();

		$subcat_id = null;

		if ($category_tax) {
			$terms = get_the_terms($post->ID, $category_tax);
			if ($terms && !is_wp_error($terms)) {
				$slug = reset($terms)->slug;
				if (isset($category_map[$slug])) {
					$subcat_id = $category_map[$slug];
				}
			}
		} else {
			$subcat_id = (string) get_post_meta($post->ID, '_re_exporter_olx_category', true);
		}

		if (!$subcat_id) {
			return '';
		}

		// Try direct CSV lookup; fall back to deal-type routing.
		if (!$this->olx_template->get_csv_path($subcat_id)) {
			$deal_field = $this->settings->get_olx_deal_type_field();
			$deal_map = $this->settings->get_olx_deal_type_map();
			$direction = 'sales';

			if ('__always_rent__' === $deal_field) {
				$direction = 'rent';
			} elseif ($deal_field && !in_array($deal_field, array('', '__always_sales__'), true)) {
				$wp_val = get_post_meta($post->ID, $deal_field, true);
				$direction = isset($deal_map[$wp_val]) ? $deal_map[$wp_val] : 'sales';
			}

			$resolved = $subcat_id . ('rent' === $direction ? '_rent' : '_sale');
			if ($this->olx_template->get_csv_path($resolved)) {
				$subcat_id = $resolved;
			}
		}

		return (string) $subcat_id;
	}

	// =========================================================================
	// ALO Location Widget
	// =========================================================================

	private function get_locations_data()
	{
		if (null === $this->locations_data) {
			$file = RE_EXPORTER_ALO_TEMPLATES . 'json/locations.json';
			$raw = file_exists($file) ? file_get_contents($file) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions
			$this->locations_data = $raw ? (array) json_decode($raw, true) : array();
		}
		return $this->locations_data;
	}

	private function get_alo_all_regions()
	{
		$regions = array();
		foreach ($this->get_locations_data() as $rid => $region) {
			$regions[] = array('id' => (string) $rid, 'name' => (string) $region['region_name']);
		}
		return $regions;
	}

	private function get_alo_locations_for_region($region_id)
	{
		$data = $this->get_locations_data();
		if (empty($data[$region_id]['locations'])) {
			return array();
		}
		$locs = array();
		foreach ($data[$region_id]['locations'] as $lid => $loc) {
			$locs[] = array('id' => (string) $lid, 'name' => (string) $loc['location_name']);
		}
		return $locs;
	}

	private function get_alo_sections_for_location($region_id, $location_id)
	{
		$data = $this->get_locations_data();
		$sections = isset($data[$region_id]['locations'][$location_id]['sections'])
			? $data[$region_id]['locations'][$location_id]['sections']
			: null;
		return $sections ? array_values((array) $sections) : array();
	}

	public function ajax_alo_get_locations()
	{
		check_ajax_referer('re_exporter_nonce', 'nonce');
		if (!current_user_can('edit_posts')) {
			wp_send_json_error('Forbidden', 403);
		}
		$region_id = isset($_POST['region_id']) ? sanitize_text_field(wp_unslash($_POST['region_id'])) : '';
		wp_send_json_success($this->get_alo_locations_for_region($region_id));
	}

	public function ajax_alo_get_sections()
	{
		check_ajax_referer('re_exporter_nonce', 'nonce');
		if (!current_user_can('edit_posts')) {
			wp_send_json_error('Forbidden', 403);
		}
		$region_id = isset($_POST['region_id']) ? sanitize_text_field(wp_unslash($_POST['region_id'])) : '';
		$location_id = isset($_POST['location_id']) ? sanitize_text_field(wp_unslash($_POST['location_id'])) : '';
		wp_send_json_success($this->get_alo_sections_for_location($region_id, $location_id));
	}

	public function ajax_alo_search_location()
	{
		check_ajax_referer('re_exporter_nonce', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Forbidden', 403);
		}
		$field = isset($_POST['field']) ? sanitize_key(wp_unslash($_POST['field'])) : 'location_name';
		$q = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';
		$all = $this->alo_template->get_location_values($field);
		$items = array();
		foreach ($all as $v) {
			if ('' === $q || false !== mb_stripos($v['label'], $q)) {
				$items[] = array('label' => $v['label'], 'value' => $v['value']);
				if (count($items) >= 10) {
					break;
				}
			}
		}
		wp_send_json_success(array('items' => $items));
	}

	private function render_alo_location_widget(\WP_Post $post)
	{
		$alo_map = $this->settings->get_alo_field_map();
		$has_region = false;
		$has_location = false;
		$has_section = false;
		$has_sec_string = false;

		foreach ($alo_map as $source) {
			if ('__alo_region__' === $source) {
				$has_region = true;
			}
			if ('__alo_location__' === $source) {
				$has_location = true;
			}
			if ('__alo_section__' === $source) {
				$has_section = true;
			}
			if ('__alo_section_string__' === $source) {
				$has_sec_string = true;
			}
		}

		$saved_region_name    = (string) get_post_meta($post->ID, '_re_alo_region_name', true);
		$saved_location_name  = (string) get_post_meta($post->ID, '_re_alo_location_name', true);
		$saved_section_name   = (string) get_post_meta($post->ID, '_re_alo_section_name', true);
		$saved_section_string = (string) get_post_meta($post->ID, '_re_alo_section_string', true);

		// Display labels — stored separately so the city-widget can restore without AJAX.
		$saved_region_label   = (string) get_post_meta($post->ID, '_re_alo_region_label', true);
		$saved_location_label = (string) get_post_meta($post->ID, '_re_alo_location_label', true);
		$saved_section_label  = (string) get_post_meta($post->ID, '_re_alo_section_label', true);
		// Fallback: use value as label (works for region/section; location keeps plain name for old posts).
		if ('' === $saved_region_label)   { $saved_region_label   = $saved_region_name; }
		if ('' === $saved_location_label) { $saved_location_label = $saved_location_name; }
		if ('' === $saved_section_label)  { $saved_section_label  = $saved_section_name; }
		?>
		<div class="re-alo-location-widget">
			<p class="re-location-widget-title"><?php esc_html_e('ALO Location', 're-exporter'); ?></p>

			<table class="re-fields-table" style="margin-bottom:0;">
				<?php if ($has_region || $has_location): ?>
				<tr>
					<td class="re-field-label"><strong><?php esc_html_e('Region', 're-exporter'); ?></strong></td>
					<td class="re-field-input">
						<?php $this->render_alo_city_widget('region_name', $saved_region_name, $saved_region_label, esc_attr__('— Select region —', 're-exporter')); ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if ($has_location): ?>
				<tr>
					<td class="re-field-label"><strong><?php esc_html_e('Location', 're-exporter'); ?></strong></td>
					<td class="re-field-input">
						<?php $this->render_alo_city_widget('location_name', $saved_location_name, $saved_location_label, esc_attr__('— Select location —', 're-exporter')); ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if ($has_section): ?>
				<tr>
					<td class="re-field-label"><strong><?php esc_html_e('Section', 're-exporter'); ?></strong></td>
					<td class="re-field-input">
						<?php $this->render_alo_city_widget('section_name', $saved_section_name, $saved_section_label, esc_attr__('— Select section —', 're-exporter'), true); ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if ($has_sec_string): ?>
				<tr class="re-alo-section-string-row" <?php echo ('NULL' === $saved_section_name) ? '' : 'style="display:none;"'; ?>>
					<td class="re-field-label"><strong><?php esc_html_e('Section (free text)', 're-exporter'); ?></strong></td>
					<td class="re-field-input">
						<input type="text" name="re_alo_location[section_string]"
							class="re-alo-section-string-input regular-text"
							value="<?php echo esc_attr($saved_section_string); ?>"
							placeholder="<?php esc_attr_e('Optional free-text district name', 're-exporter'); ?>" />
					</td>
				</tr>
				<?php endif; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Render a single ALO location city-search widget (shared for region / location / section).
	 *
	 * @param string $field_name   'region_name' | 'location_name' | 'section_name'
	 * @param string $saved_value  Saved ALO value (the string stored in post meta).
	 * @param string $saved_label  Saved display label (may include region prefix for location_name).
	 * @param string $placeholder  Escaped placeholder string.
	 * @param bool   $is_section   Whether this is the section widget (adds extra class for JS hook).
	 */
	private function render_alo_city_widget($field_name, $saved_value, $saved_label, $placeholder, $is_section = false)
	{
		$has_val    = '' !== $saved_value;
		$extra_cls  = $is_section ? ' re-alo-section-city-widget' : '';
		?>
		<div class="re-city-widget<?php echo esc_attr($extra_cls); ?>"
			data-alo-field="<?php echo esc_attr($field_name); ?>"
			data-empty="<?php echo esc_attr($placeholder); ?>">
			<input type="hidden"
				name="re_alo_location[<?php echo esc_attr($field_name); ?>]"
				class="re-city-val"
				value="<?php echo esc_attr($saved_value); ?>" />
			<input type="hidden"
				name="alo_location_label[<?php echo esc_attr($field_name); ?>]"
				class="re-city-label-field"
				value="<?php echo esc_attr($has_val ? $saved_label : ''); ?>" />
			<div class="re-city-trigger">
				<span class="re-city-label<?php echo $has_val ? '' : ' is-placeholder'; ?>">
					<?php echo $has_val ? esc_html($saved_label ?: $saved_value) : esc_html(wp_strip_all_tags($placeholder)); ?>
				</span>
				<span class="re-city-actions">
					<button type="button" class="re-city-clear-btn"<?php echo $has_val ? ' style="display:inline-block;"' : ''; ?>>&#x2715;</button>
					<span class="re-city-caret"></span>
				</span>
			</div>
			<div class="re-city-panel" style="display:none;">
				<input type="text" class="re-city-search"
					placeholder="<?php esc_attr_e('Type to search…', 're-exporter'); ?>"
					autocomplete="off" />
				<div class="re-city-results"></div>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// Subcategory resolvers
	// =========================================================================

	private function get_alo_subcat_id_for(\WP_Post $post)
	{
		$category_tax = $this->settings->get_alo_category_tax();
		$category_map = $this->settings->get_alo_category_map();

		$subcat_id = '';

		if ($category_tax) {
			$terms = get_the_terms($post->ID, $category_tax);
			if (is_array($terms)) {
				foreach ($terms as $term) {
					if (!empty($category_map[$term->slug])) {
						$subcat_id = (string) $category_map[$term->slug];
						break;
					}
				}
			}
		} else {
			$subcat_id = (string) get_post_meta($post->ID, '_re_exporter_alo_category', true);
		}

		if (!$subcat_id) {
			return '';
		}

		$deal_field = $this->settings->get_alo_deal_type_field();
		if (!$deal_field) {
			return $subcat_id;
		}

		$term_slug = '';
		if ($category_tax) {
			$terms = get_the_terms($post->ID, $category_tax);
			if (is_array($terms)) {
				foreach ($terms as $term) {
					if (isset($category_map[$term->slug]) && (string) $category_map[$term->slug] === $subcat_id) {
						$term_slug = $term->slug;
						break;
					}
				}
			}
		}

		$rent_map = $this->settings->get_alo_category_rent_map();
		if (!$term_slug || empty($rent_map[$term_slug])) {
			return $subcat_id;
		}

		$rent_subcat_id = (string) $rent_map[$term_slug];
		$direction = 'sales';

		if ('__always_rent__' === $deal_field) {
			$direction = 'rent';
		} elseif ('__always_sales__' !== $deal_field) {
			$wp_val = get_post_meta($post->ID, $deal_field, true);
			$deal_map = $this->settings->get_alo_deal_type_map();
			$direction = isset($deal_map[$wp_val]) ? $deal_map[$wp_val] : 'sales';
		}

		return 'rent' === $direction ? $rent_subcat_id : $subcat_id;
	}

	// =========================================================================
	// Posts list table — column, quick edit, bulk edit
	// =========================================================================

	/**
	 * Register list-table hooks after post type is known.
	 */
	public function register_list_table_hooks()
	{
		$post_type = $this->settings->get_post_type();
		if (!$post_type) {
			return;
		}
		add_filter('manage_' . $post_type . '_posts_columns', array($this, 'add_export_column'));
		add_filter('manage_edit-' . $post_type . '_columns', array($this, 'add_export_column'), 9999);
		add_action('manage_' . $post_type . '_posts_custom_column', array($this, 'render_export_column'), 10, 2);
		add_action('quick_edit_custom_box', array($this, 'render_quick_edit_fields'), 10, 2);
		add_action('bulk_edit_custom_box', array($this, 'render_bulk_edit_fields'), 10, 2);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_list_assets'));
	}

	/**
	 * Append Export column to the posts list table.
	 */
	public function add_export_column($columns)
	{
		$columns['re_export_enabled'] = __('Export', 're-exporter');
		return $columns;
	}

	/**
	 * Render the Export column cell.
	 */
	public function render_export_column($column, $post_id)
	{
		if ('re_export_enabled' !== $column) {
			return;
		}
		$val = get_post_meta($post_id, '_re_export_enabled', true);
		$is_on = ('' === $val || '1' === $val);
		echo '<span class="re-export-col-flag" data-enabled="' . ($is_on ? '1' : '0') . '">'
			. ($is_on ? '&#10003;' : '&mdash;')
			. '</span>';
	}

	/**
	 * Render quick-edit checkbox.
	 */
	public function render_quick_edit_fields($column_name, $post_type)
	{
		if ('re_export_enabled' !== $column_name || $post_type !== $this->settings->get_post_type()) {
			return;
		}
		wp_nonce_field('re_exporter_quick_edit', 're_exporter_qe_nonce');
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="alignleft">
					<input type="hidden" name="re_export_enabled" value="0" />
					<input type="checkbox" name="re_export_enabled" value="1" class="re-qe-export-flag" />
					<span class="checkbox-title"><?php esc_html_e('Include in RE export', 're-exporter'); ?></span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Render bulk-edit select.
	 */
	public function render_bulk_edit_fields($column_name, $post_type)
	{
		if ('re_export_enabled' !== $column_name || $post_type !== $this->settings->get_post_type()) {
			return;
		}
		wp_nonce_field('re_exporter_bulk_edit', 're_exporter_be_nonce');
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="alignleft">
					<span class="title"><?php esc_html_e('RE Export', 're-exporter'); ?></span>
					<select name="re_export_enabled_bulk">
						<option value=""><?php esc_html_e('— No change —', 're-exporter'); ?></option>
						<option value="1"><?php esc_html_e('Include', 're-exporter'); ?></option>
						<option value="0"><?php esc_html_e('Exclude', 're-exporter'); ?></option>
					</select>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Enqueue list-table JS on edit.php for the configured post type.
	 */
	public function enqueue_list_assets($hook)
	{
		if ('edit.php' !== $hook) {
			return;
		}
		wp_enqueue_style(
			're-exporter-admin',
			RE_EXPORTER_URL . 'assets/css/re-admin.css',
			array(),
			RE_EXPORTER_VERSION
		);
		wp_enqueue_script(
			're-exporter-list',
			RE_EXPORTER_URL . 'assets/js/re-list-table.js',
			array('jquery', 'inline-edit-post'),
			RE_EXPORTER_VERSION,
			true
		);
	}
}
