<?php
defined( 'ABSPATH' ) || exit;

/** Shortcode + HTML rendering + asset loading. */
class CPF_Render {

	public static function init() {
		add_shortcode( 'custom_product_filter', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		wp_register_style( 'cpf-filter', CPF_URL . 'assets/css/filter.css', array(), CPF_VERSION );
		wp_register_script( 'cpf-filter', CPF_URL . 'assets/js/filter.js', array(), CPF_VERSION, true );
	}

	private static function enqueue_assets() {
		$path_map = array();

		foreach ( CPF_Config::path_map() as $name => $taxonomy ) {
			$path_map[] = array( $name, $taxonomy );
		}

		wp_enqueue_style( 'cpf-filter' );
		wp_enqueue_script( 'cpf-filter' );

		wp_localize_script( 'cpf-filter', 'CPF_CONFIG', array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'pathMap'       => $path_map,
			'loopSelector'  => CPF_Config::loop_selector(),
			'closeSelector' => CPF_Config::close_selector(),
			'offcanvas'     => CPF_Config::offcanvas_selectors(),
			'timeout'       => 25000,
			'i18n'          => array(
				'loading'    => __( 'Loading products...', 'cpf' ),
				'updating'   => __( 'Updating products...', 'cpf' ),
				'found'      => __( 'products found.', 'cpf' ),
				'none'       => __( 'No products found.', 'cpf'),
				'viewMore'   => __( 'View More', 'cpf' ),
				'viewLess'   => __( 'View Less', 'cpf' ),
				'error'      => __( 'Something went wrong. Please try again.', 'cpf' ),
				'timeout'    => __( 'The request took too long. Please try again.', 'cpf' ),
				'noLoop'     => __( 'Product list not found on this page.', 'cpf' ),
				'filters'    => __( 'Loading filters...', 'cpf' ),
			),
		) );
	}

	/** One attribute filter group */
	public static function attribute( $taxonomy, $label, $category_id = 0, $selected = array() ) {
		$terms = CPF_Terms::get( $taxonomy, $category_id );

		if ( ! $terms ) {
			return '';
		}

		ob_start();
		?>
		<div class="cpf-group">
			<div class="cpf-heading"><span><?php echo esc_html( $label ); ?></span></div>
			<div class="cpf-options">
				<?php foreach ( $terms as $term ) : ?>
					<label class="cpf-option">
						<input type="checkbox" class="cpf-term"
							data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
							value="<?php echo esc_attr( $term->slug ); ?>"
							<?php checked( in_array( $term->slug, $selected, true ) ); ?>>
						<span class="cpf-check"></span>
						<span class="cpf-label"><?php echo esc_html( $term->name ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Extra attributes for the selected category (walks up through parent categories) */
	public static function category_attributes_set( $category_id ) {
		if ( ! $category_id ) {
			return array();
		}

		$category = get_term( $category_id, 'product_cat' );

		if ( ! $category || is_wp_error( $category ) ) {
			return array();
		}

		$map = CPF_Config::extra_attributes();
		$ids = array_merge( array( (int) $category->term_id ), array_map( 'intval', get_ancestors( $category->term_id, 'product_cat' ) ) );

		foreach ( $ids as $id ) {
			$term = get_term( $id, 'product_cat' );

			if ( $term && ! is_wp_error( $term ) && isset( $map[ $term->slug ] ) ) {
				return $map[ $term->slug ];
			}
		}

		return array();
	}

	public static function extra_attributes_html( $category_id = 0, $selected = array() ) {
		$html = '';

		foreach ( self::category_attributes_set( $category_id ) as $taxonomy => $label ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$html .= self::attribute( $taxonomy, $label, $category_id, $selected[ $taxonomy ] ?? array() );
			}
		}

		return $html;
	}

	public static function shortcode() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return '';
		}

		self::enqueue_assets();

		$categories = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		$current_category = 0;

		if ( is_product_category() ) {
			$current = get_queried_object();

			if ( $current && ! is_wp_error( $current ) ) {
				$current_category = absint( $current->term_id );
			}
		}

		// phpcs:disable WordPress.Security.NonceVerification
		$initial = CPF_Routing::request_filters();

		foreach ( CPF_Config::legacy_keys() as $key => $taxonomy ) {
			if ( isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ) {
				$initial = CPF_Routing::merge_values( $initial, $taxonomy, explode( ',', sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ) );
			}
		}

		if ( ! $current_category && isset( $_GET['category'] ) ) {
			$requested = absint( $_GET['category'] );

			foreach ( $categories as $category ) {
				if ( (int) $category->term_id === $requested ) {
					$current_category = $requested;
					break;
				}
			}
		}

		if ( ! $current_category ) {
			foreach ( CPF_Config::attribute_category_hints() as $taxonomy => $slug ) {
				if ( empty( $initial[ $taxonomy ] ) ) {
					continue;
				}

				foreach ( $categories as $category ) {
					if ( $category->slug === $slug ) {
						$current_category = absint( $category->term_id );
						break 2;
					}
				}
			}
		}

		$min_value = isset( $_GET['min_price'] ) ? sanitize_text_field( wp_unslash( $_GET['min_price'] ) ) : '';
		$max_value = isset( $_GET['max_price'] ) ? sanitize_text_field( wp_unslash( $_GET['max_price'] ) ) : '';

		$has_filters = ! empty( array_filter( $initial ) ) || '' !== $min_value || '' !== $max_value || isset( $_GET['category'] );
		// phpcs:enable

		$category_paths = array();

		foreach ( $categories as $category ) {
			$link = get_term_link( $category );

			if ( ! is_wp_error( $link ) ) {
				$category_paths[ $category->term_id ] = trailingslashit( wp_parse_url( $link, PHP_URL_PATH ) );
			}
		}

		$path_mode  = ( function_exists( 'is_shop' ) && is_shop() ) || is_product_category();
		$radio_name = wp_unique_id( 'cpf_category_' );
		$shop_path  = trailingslashit( wp_parse_url( wc_get_page_permalink( 'shop' ), PHP_URL_PATH ) );

		ob_start();
		?>
		<div class="cpf-filter"
			data-current-category="<?php echo esc_attr( $current_category ); ?>"
			data-shop-path="<?php echo esc_attr( $shop_path ); ?>"
			data-has-filters="<?php echo $has_filters ? '1' : '0'; ?>"
			data-path-mode="<?php echo $path_mode ? '1' : '0'; ?>"
			data-category-paths="<?php echo esc_attr( wp_json_encode( $category_paths ) ); ?>">

			<div class="cpf-category-field cpf-group">
				<div class="cpf-heading"><span><?php esc_html_e( 'Choose Product Category', 'cpf' ); ?></span></div>
				<div class="cpf-options cpf-category-options">
					<label class="cpf-option">
						<input type="radio" class="cpf-category" name="<?php echo esc_attr( $radio_name ); ?>" value="0" <?php checked( ! $current_category ); ?>>
						<span class="cpf-check"></span>
						<span class="cpf-label"><?php esc_html_e( 'All Categories', 'cpf' ); ?></span>
					</label>
					<?php foreach ( $categories as $category ) : ?>
						<label class="cpf-option">
							<input type="radio" class="cpf-category" name="<?php echo esc_attr( $radio_name ); ?>"
								value="<?php echo esc_attr( $category->term_id ); ?>"
								<?php checked( (int) $current_category, (int) $category->term_id ); ?>>
							<span class="cpf-check"></span>
							<span class="cpf-label"><?php echo esc_html( $category->name ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="cpf-dynamic-attributes">
				<?php
				echo self::attribute( 'pa_brand', 'Brand', $current_category, $initial['pa_brand'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo self::attribute( 'pa_size', 'Size', $current_category, $initial['pa_size'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo self::extra_attributes_html( $current_category, $initial ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>

			<div class="cpf-price">
				<h4><?php esc_html_e( 'Price Range', 'cpf' ); ?></h4>
				<div class="cpf-price-inputs">
					<label>
						<span><?php esc_html_e( 'Min price', 'cpf' ); ?></span>
						<input type="number" inputmode="decimal" class="cpf-min" min="0" step="any" placeholder="0" value="<?php echo esc_attr( $min_value ); ?>">
					</label>
					<label>
						<span><?php esc_html_e( 'Max price', 'cpf' ); ?></span>
						<input type="number" inputmode="decimal" class="cpf-max" min="0" step="any" placeholder="<?php esc_attr_e( 'No limit', 'cpf' ); ?>" value="<?php echo esc_attr( $max_value ); ?>">
					</label>
				</div>
			</div>

			<div class="cpf-actions">
				<p class="cpf-status" aria-live="polite"></p>
				<button type="button" class="cpf-apply"><?php esc_html_e( 'View Results', 'cpf' ); ?></button>
				<button type="button" class="cpf-clear"><?php esc_html_e( 'Clear All', 'cpf' ); ?></button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
