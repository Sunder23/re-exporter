<?php
/**
 * Registers the RE Exporter Gallery field on the configured CPT.
 *
 * The gallery stores attachment IDs as a serialized array in the post meta
 * key `_re_gallery_images`. It behaves like an ACF gallery repeater and can
 * be bound to image fields for OLX and ALO exports via the virtual source
 * key `__re_gallery__`.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Gallery_Field
 */
class Gallery_Field {

	/**
	 * Post meta key used to store attachment IDs.
	 */
	const META_KEY = '_re_gallery_images';

	/** @var Settings */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		add_action( 'save_post',             array( $this, 'save_meta_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	// =========================================================================
	// Assets
	// =========================================================================

	/**
	 * Enqueue gallery assets on the CPT post edit screen.
	 *
	 * @param string $hook  Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$post_type = $this->settings->get_post_type();
		if ( ! $post_type ) {
			return;
		}

		$current_type = '';
		if ( 'post.php' === $hook && isset( $_GET['post'] ) ) {
			$post_obj     = get_post( (int) wp_unslash( $_GET['post'] ) );
			$current_type = $post_obj ? $post_obj->post_type : '';
		} elseif ( 'post-new.php' === $hook ) {
			$current_type = isset( $_GET['post_type'] )
				? sanitize_key( wp_unslash( $_GET['post_type'] ) )
				: 'post';
		}

		if ( $current_type !== $post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			're-exporter-gallery',
			RE_EXPORTER_URL . 'assets/js/re-gallery-field.js',
			array( 'jquery', 'jquery-ui-sortable', 'media-upload' ),
			RE_EXPORTER_VERSION,
			true
		);

		wp_localize_script(
			're-exporter-gallery',
			'reExporterGallery',
			array(
				'nonce' => wp_create_nonce( 're_exporter_gallery_nonce' ),
				'i18n'  => array(
					'selectMedia' => __( 'Select Images', 're-exporter' ),
					'addToGallery' => __( 'Add to Gallery', 're-exporter' ),
				),
			)
		);
	}

	// =========================================================================
	// Render
	// =========================================================================

	/**
	 * Render the gallery UI (called from the Metaboxes gallery tab).
	 *
	 * @param \WP_Post $post
	 */
	public function render_content( \WP_Post $post ) {
		wp_nonce_field( 're_exporter_gallery_' . $post->ID, 're_exporter_gallery_nonce' );

		$ids = get_post_meta( $post->ID, self::META_KEY, true );
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		?>
		<div class="re-gallery-wrap">

			<input
				type="hidden"
				name="re_gallery_ids"
				id="re-gallery-ids"
				value="<?php echo esc_attr( implode( ',', $ids ) ); ?>"
			/>

			<div class="re-gallery-items" id="re-gallery-items">
				<?php foreach ( $ids as $att_id ) : ?>
					<?php
					$thumb = wp_get_attachment_image_url( $att_id, 'thumbnail' );
					if ( ! $thumb ) {
						continue;
					}
					?>
					<div class="re-gallery-item" data-id="<?php echo esc_attr( $att_id ); ?>">
						<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
						<button type="button" class="re-gallery-remove" title="<?php esc_attr_e( 'Remove', 're-exporter' ); ?>">&#x2715;</button>
						<span class="re-gallery-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 're-exporter' ); ?>">&#9776;</span>
					</div>
				<?php endforeach; ?>
			</div>

			<p
				class="re-gallery-empty description"
				id="re-gallery-empty"
				<?php echo ! empty( $ids ) ? 'style="display:none;"' : ''; ?>
			>
				<?php esc_html_e( 'No images added yet. Click "Add Images" to upload or select from the media library.', 're-exporter' ); ?>
			</p>

			<p class="re-gallery-actions">
				<button type="button" class="button" id="re-gallery-add">
					<?php esc_html_e( 'Add Images', 're-exporter' ); ?>
				</button>
				<button
					type="button"
					class="button"
					id="re-gallery-clear"
					<?php echo empty( $ids ) ? 'style="display:none;"' : ''; ?>
				>
					<?php esc_html_e( 'Clear All', 're-exporter' ); ?>
				</button>
				<span class="re-gallery-count" id="re-gallery-count">
					<?php
					$count = count( $ids );
					if ( $count > 0 ) {
						/* translators: %d = number of images */
						echo esc_html( sprintf( _n( '%d image', '%d images', $count, 're-exporter' ), $count ) );
					}
					?>
				</span>
			</p>

			<p class="description" style="margin-top:4px;">
				<?php esc_html_e( 'Bind these images to OLX / ALO exports by selecting source key __re_gallery__ in the field map.', 're-exporter' ); ?>
				<br><strong><?php esc_html_e( 'Note: only the first 24 images are included in OLX export; ALO export is capped at 15.', 're-exporter' ); ?></strong>
			</p>
		</div>
		<?php
	}

	// =========================================================================
	// Save
	// =========================================================================

	/**
	 * Save the gallery attachment IDs.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function save_meta_box( $post_id, \WP_Post $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = $this->settings->get_post_type();
		if ( $post->post_type !== $post_type ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST['re_exporter_gallery_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['re_exporter_gallery_nonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, 're_exporter_gallery_' . $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['re_gallery_ids'] )
			? sanitize_text_field( wp_unslash( $_POST['re_gallery_ids'] ) )
			: '';

		if ( '' === $raw ) {
			delete_post_meta( $post_id, self::META_KEY );
			return;
		}

		$ids = array_values(
			array_filter(
				array_map( 'absint', explode( ',', $raw ) )
			)
		);

		if ( empty( $ids ) ) {
			delete_post_meta( $post_id, self::META_KEY );
		} else {
			update_post_meta( $post_id, self::META_KEY, $ids );
		}
	}
}
