<?php
/**
 * Builds review/preflight output outside the AJAX controller.
 *
 * @package RE_Exporter
 */

namespace RE_Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Export_Review_Service
 */
class Export_Review_Service {

	/** @var Settings */
	private $settings;

	/** @var OLX_Template */
	private $olx_template;

	/** @var ALO_Template */
	private $alo_template;

	/** @var OLX_Category_Resolver */
	private $olx_resolver;

	/** @var ALO_Category_Resolver */
	private $alo_resolver;

	/**
	 * @param Settings              $settings
	 * @param OLX_Template          $olx_template
	 * @param ALO_Template          $alo_template
	 * @param OLX_Category_Resolver $olx_resolver
	 * @param ALO_Category_Resolver $alo_resolver
	 */
	public function __construct( Settings $settings, OLX_Template $olx_template, ALO_Template $alo_template, OLX_Category_Resolver $olx_resolver, ALO_Category_Resolver $alo_resolver ) {
		$this->settings     = $settings;
		$this->olx_template = $olx_template;
		$this->alo_template = $alo_template;
		$this->olx_resolver = $olx_resolver;
		$this->alo_resolver = $alo_resolver;
	}

	/**
	 * @param string $platform
	 * @param int[]  $post_ids
	 * @return string
	 */
	public function render( $platform, array $post_ids ) {
		ob_start();

		if ( 'olx' === $platform ) {
			$this->render_olx_review( $post_ids );
		} elseif ( 'alo' === $platform ) {
			$this->render_alo_review( $post_ids );
		} elseif ( 'realistimo' === $platform ) {
			$this->render_realistimo_review( $post_ids );
		} else {
			$this->render_imoti_review( $post_ids );
		}

		return (string) ob_get_clean();
	}

	/**
	 * @param int[] $post_ids
	 * @return void
	 */
	private function render_olx_review( array $post_ids ) {
		$groups   = array();
		$warnings = array();

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$subcat_id = $this->olx_resolver->resolve_subcategory_id( $post );
			if ( ! $subcat_id ) {
				$warnings[] = array(
					'title'   => $post->post_title ?: "(#{$post_id})",
					'missing' => array( __( 'No OLX category assigned', 're-exporter' ) ),
				);
				continue;
			}

			$missing = $this->olx_resolver->get_missing_required_fields( $subcat_id );
			if ( ! empty( $missing ) ) {
				$warnings[] = array(
					'title'   => $post->post_title ?: "(#{$post_id})",
					'missing' => $missing,
				);
			}

			if ( ! isset( $groups[ $subcat_id ] ) ) {
				$subcat = $this->olx_template->get_subcategory_by_id( $subcat_id );
				$groups[ $subcat_id ] = array(
					'name'  => $subcat ? $subcat['name'] : $subcat_id,
					'path'  => $this->olx_template->get_csv_path( $subcat_id ),
					'count' => 0,
				);
			}

			$groups[ $subcat_id ]['count']++;
		}

		if ( ! empty( $groups ) ) :
			?>
			<table class="re-review-table widefat">
				<thead><tr>
					<th><?php esc_html_e( 'Output file', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Category', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Posts', 're-exporter' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $groups as $subcat_id => $g ) : ?>
					<tr>
						<td><code><?php echo esc_html( $subcat_id . '.csv' ); ?></code></td>
						<td><?php echo esc_html( $g['name'] ); ?></td>
						<td><?php echo esc_html( $g['count'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		endif;

		$this->render_warnings( $groups, $warnings );
	}

	/**
	 * @param int[] $post_ids
	 * @return void
	 */
	private function render_alo_review( array $post_ids ) {
		$groups   = array();
		$warnings = array();

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$subcat_id = $this->alo_resolver->resolve_subcategory_id( $post );
			if ( ! $subcat_id ) {
				$warnings[] = array(
					'title'   => $post->post_title ?: "(#{$post_id})",
					'missing' => array( __( 'No ALO category assigned', 're-exporter' ) ),
				);
				continue;
			}

			$missing = $this->alo_resolver->get_missing_required_fields( $subcat_id );
			if ( ! empty( $missing ) ) {
				$warnings[] = array(
					'title'   => $post->post_title ?: "(#{$post_id})",
					'missing' => $missing,
				);
			}

			if ( ! isset( $groups[ $subcat_id ] ) ) {
				$subcat = $this->alo_template->get_subcategory_by_id( $subcat_id );
				$groups[ $subcat_id ] = array(
					'name'  => $subcat ? $subcat['name'] : $subcat_id,
					'count' => 0,
				);
			}

			$groups[ $subcat_id ]['count']++;
		}

		if ( ! empty( $groups ) ) :
			?>
			<table class="re-review-table widefat">
				<thead><tr>
					<th><?php esc_html_e( 'Output file', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Category', 're-exporter' ); ?></th>
					<th><?php esc_html_e( 'Posts', 're-exporter' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $groups as $subcat_id => $g ) : ?>
					<tr>
						<td><code><?php echo esc_html( $subcat_id . '.json' ); ?></code></td>
						<td><?php echo esc_html( $g['name'] ); ?></td>
						<td><?php echo esc_html( $g['count'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		endif;

		$this->render_warnings( $groups, $warnings );
	}

	/**
	 * @param int[] $post_ids
	 * @return void
	 */
	private function render_imoti_review( array $post_ids ) {
		$agency_id    = $this->settings->get_imoti_agency_id();
		$agency_title = $this->settings->get_imoti_agency_title();
		$field_map    = $this->settings->get_imoti_field_map();
		$recommended  = array( 'OfferType', 'EstateType', 'Price', 'Description', 'Images' );
		$missing      = array();

		foreach ( $recommended as $field ) {
			if ( empty( $field_map[ $field ] ) || '__skip__' === $field_map[ $field ] ) {
				$missing[] = $field;
			}
		}
		?>
		<table class="re-review-table widefat">
			<thead><tr>
				<th><?php esc_html_e( 'Output file', 're-exporter' ); ?></th>
				<th><?php esc_html_e( 'AgencyID', 're-exporter' ); ?></th>
				<th><?php esc_html_e( 'Posts', 're-exporter' ); ?></th>
			</tr></thead>
			<tbody>
				<tr>
					<td><code>feed.xml</code></td>
					<td><?php echo esc_html( $agency_id ?: '—' ); ?></td>
					<td><?php echo esc_html( count( $post_ids ) ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php if ( $agency_title ) : ?>
			<p style="margin-top:6px;color:#646970;">
				<?php esc_html_e( 'Agency:', 're-exporter' ); ?>
				<strong><?php echo esc_html( $agency_title ); ?></strong>
			</p>
		<?php endif; ?>
		<?php if ( ! empty( $missing ) ) : ?>
		<div class="re-review-warnings">
			<strong>&#9888; <?php esc_html_e( 'Warnings', 're-exporter' ); ?></strong>
			<ul>
				<li><?php
					echo esc_html( sprintf( __( 'Recommended fields not mapped: %s', 're-exporter' ), implode( ', ', $missing ) ) );
				?></li>
			</ul>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * @param int[] $post_ids
	 * @return void
	 */
	private function render_realistimo_review( array $post_ids ) {
		$agency      = $this->settings->get_realistimo_agency();
		$field_map   = $this->settings->get_realistimo_field_map();
		$recommended = array( 'price', 'description', 'images', 'category', 'subcategory' );
		$missing     = array();

		foreach ( $recommended as $field ) {
			if ( empty( $field_map[ $field ] ) || '__skip__' === $field_map[ $field ] ) {
				$missing[] = $field;
			}
		}
		?>
		<table class="re-review-table widefat">
			<thead><tr>
				<th><?php esc_html_e( 'Output file', 're-exporter' ); ?></th>
				<th><?php esc_html_e( 'Agency UID', 're-exporter' ); ?></th>
				<th><?php esc_html_e( 'Posts', 're-exporter' ); ?></th>
			</tr></thead>
			<tbody>
				<tr>
					<td><code>feed.xml</code></td>
					<td><?php echo esc_html( ! empty( $agency['uid'] ) ? $agency['uid'] : '—' ); ?></td>
					<td><?php echo esc_html( count( $post_ids ) ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php if ( ! empty( $agency['name'] ) ) : ?>
			<p style="margin-top:6px;color:#646970;">
				<?php esc_html_e( 'Agency:', 're-exporter' ); ?>
				<strong><?php echo esc_html( $agency['name'] ); ?></strong>
			</p>
		<?php endif; ?>
		<?php if ( ! empty( $missing ) ) : ?>
		<div class="re-review-warnings">
			<strong>&#9888; <?php esc_html_e( 'Warnings', 're-exporter' ); ?></strong>
			<ul>
				<li><?php
					echo esc_html( sprintf( __( 'Recommended fields not mapped: %s', 're-exporter' ), implode( ', ', $missing ) ) );
				?></li>
			</ul>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * @param array $groups
	 * @param array $warnings
	 * @return void
	 */
	private function render_warnings( array $groups, array $warnings ) {
		if ( ! empty( $warnings ) ) {
			echo '<div class="re-review-warnings">';
			echo '<strong>&#9888; ' . esc_html__( 'Warnings', 're-exporter' ) . '</strong><ul>';
			foreach ( $warnings as $warning ) {
				echo '<li><em>' . esc_html( $warning['title'] ) . '</em>: ' . esc_html( implode( ', ', $warning['missing'] ) ) . '</li>';
			}
			echo '</ul></div>';
		}

		if ( empty( $groups ) && empty( $warnings ) ) {
			echo '<p>' . esc_html__( 'No exportable posts found.', 're-exporter' ) . '</p>';
		}
	}
}
