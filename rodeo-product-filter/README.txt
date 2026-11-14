Rodeo Vault Product Filter 2.0.0

INSTALL
1. Delete / disable the old code snippet (Code Snippets plugin or functions.php). Never run both.
2. Upload this folder as a zip (Plugins > Add New > Upload) and activate.
3. Settings > Permalinks > Save (once).
4. Elementor:
   - Loop Grid widget: CSS ID = product_loop, Query > Source = Current Query or Query ID = cpf_products
   - Shortcode widget: [custom_product_filter]
   - "Apply" close button (optional): CSS class folter_close

CONFIG (wp-config.php or a small mu-plugin)
   define( 'CPF_LOOP_TEMPLATE_ID', 326 );                // Elementor Loop Item template ID
   add_filter( 'cpf_offcanvas_selectors', fn() => array( '#off-canvas-c27cf1e' ) );
   add_filter( 'cpf_products_per_page', fn() => 24 );
   add_filter( 'cpf_category_extra_attributes', ... );   // per-category attribute groups
   add_filter( 'cpf_path_attribute_map', ... );          // URL segment => taxonomy

STRUCTURE
   rodeo-product-filter.php     bootstrap, activation hooks
   includes/class-cpf-config    settings and filters
   includes/class-cpf-routing   path URLs, rewrite rules, 301 redirects
   includes/class-cpf-query     tax/price queries (page load + AJAX)
   includes/class-cpf-terms     attribute terms (single SQL query, cached)
   includes/class-cpf-cache     transient cache + auto invalidation
   includes/class-cpf-render    shortcode + HTML + asset loading
   includes/class-cpf-ajax      AJAX endpoints
   assets/css, assets/js        front-end

IMPORTANT (LiteSpeed / page cache)
   The AJAX endpoints do not use a nonce, so cached pages keep working.
   After installing: LiteSpeed > Toolbox > Purge All.
   Disable/delete BOTH old snippets: "Custom-filter" (#18) and "test filter" (#17).
