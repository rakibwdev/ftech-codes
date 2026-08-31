<?php
/**
 * Plugin Name: WP Media Image Duplicator
 * Description: Adds a "Duplicate" action to images in the WordPress Media Library.
 * Version: 1.0.0
 * Author: Rakib-ForaziTech
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add "Duplicate" action to Media Library list view.
 */
add_filter( 'media_row_actions', function ( $actions, $post ) {
	if ( 'attachment' !== $post->post_type ) {
		return $actions;
	}

	if ( ! wp_attachment_is_image( $post->ID ) ) {
		return $actions;
	}

	if ( ! current_user_can( 'upload_files' ) ) {
		return $actions;
	}

	$url = wp_nonce_url(
		admin_url( 'admin-post.php?action=wp_mid_duplicate_image&attachment_id=' . $post->ID ),
		'wp_mid_duplicate_image_' . $post->ID
	);

	$actions['wp_mid_duplicate'] = sprintf(
		'<a href="%s">Duplicate</a>',
		esc_url( $url )
	);

	return $actions;
}, 10, 2 );

/**
 * Add "Duplicate" button to Media Library grid/detail view.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'upload.php' !== $hook ) {
		return;
	}

	wp_add_inline_script(
		'media-editor',
		'
		jQuery(function($) {
			if (typeof wp === "undefined" || !wp.media || !wp.media.view || !wp.media.view.Attachment) {
				return;
			}

			var OriginalAttachment = wp.media.view.Attachment;

			wp.media.view.Attachment = OriginalAttachment.extend({
				render: function() {
					OriginalAttachment.prototype.render.apply(this, arguments);

					var model = this.model;
					var attachmentId = model && model.get("id");

					if (!attachmentId || model.get("type") !== "image") {
						return this;
					}

					if (this.$el.find(".wp-mid-duplicate").length) {
						return this;
					}

					var nonce = "' . esc_js( wp_create_nonce( 'wp_mid_duplicate_grid' ) ) . '";
					var url = "' . esc_js( admin_url( 'admin-ajax.php' ) ) . '";

					var button = $("<button>", {
						type: "button",
						class: "button-link wp-mid-duplicate",
						text: "Duplicate"
					}).css({
						marginTop: "8px",
						display: "block"
					});

					button.on("click", function(e) {
						e.preventDefault();

						var $button = $(this);
						$button.prop("disabled", true).text("Duplicating...");

						$.post(url, {
							action: "wp_mid_duplicate_image_ajax",
							attachment_id: attachmentId,
							nonce: nonce
						})
						.done(function(response) {
							if (response.success) {
								alert("Image duplicated successfully.");
								window.location.reload();
							} else {
								alert(response.data || "Could not duplicate image.");
								$button.prop("disabled", false).text("Duplicate");
							}
						})
						.fail(function() {
							alert("Could not duplicate image.");
							$button.prop("disabled", false).text("Duplicate");
						});
					});

					this.$el.append(button);

					return this;
				}
			});
		});
		',
		'after'
	);
} );

/**
 * AJAX duplicate handler for Media Library grid.
 */
add_action( 'wp_ajax_wp_mid_duplicate_image_ajax', function () {
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error( 'You do not have permission to duplicate media.' );
	}

	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
	$nonce         = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! $attachment_id || ! wp_verify_nonce( $nonce, 'wp_mid_duplicate_grid' ) ) {
		wp_send_json_error( 'Security check failed.' );
	}

	$new_id = wp_mid_duplicate_image( $attachment_id );

	if ( is_wp_error( $new_id ) ) {
		wp_send_json_error( $new_id->get_error_message() );
	}

	wp_send_json_success( array(
		'id' => $new_id,
	) );
} );

/**
 * Duplicate handler for Media Library list view.
 */
add_action( 'admin_post_wp_mid_duplicate_image', function () {
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die( 'You do not have permission to duplicate media.' );
	}

	$attachment_id = isset( $_GET['attachment_id'] ) ? absint( $_GET['attachment_id'] ) : 0;

	check_admin_referer( 'wp_mid_duplicate_image_' . $attachment_id );

	$new_id = wp_mid_duplicate_image( $attachment_id );

	if ( is_wp_error( $new_id ) ) {
		wp_die( esc_html( $new_id->get_error_message() ) );
	}

	$redirect = wp_get_referer();

	if ( ! $redirect ) {
		$redirect = admin_url( 'upload.php' );
	}

	wp_safe_redirect( add_query_arg( 'wp_mid_duplicated', 1, $redirect ) );
	exit;
} );

/**
 * Actually duplicate the image attachment and file.
 */
function wp_mid_duplicate_image( $attachment_id ) {
	$attachment = get_post( $attachment_id );

	if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
		return new WP_Error( 'invalid_attachment', 'Invalid media attachment.' );
	}

	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return new WP_Error( 'not_image', 'Only images can be duplicated.' );
	}

	$original_file = get_attached_file( $attachment_id );

	if ( ! $original_file || ! file_exists( $original_file ) ) {
		return new WP_Error( 'file_missing', 'The original image file could not be found.' );
	}

	$upload_dir = wp_upload_dir();

	$filename = wp_basename( $original_file );
	$filename = wp_unique_filename(
		$upload_dir['path'],
		pathinfo( $filename, PATHINFO_FILENAME ) . '-fr.' . pathinfo( $filename, PATHINFO_EXTENSION )
	);

	$new_file = trailingslashit( $upload_dir['path'] ) . $filename;

	if ( ! copy( $original_file, $new_file ) ) {
		return new WP_Error( 'copy_failed', 'Could not copy the image file.' );
	}

	$filetype = wp_check_filetype( $filename, null );

	$new_attachment = array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => sanitize_text_field( $attachment->post_title ) . ' fr',
		'post_content'   => $attachment->post_content,
		'post_excerpt'   => $attachment->post_excerpt,
		'post_status'    => 'inherit',
		'post_parent'    => $attachment->post_parent,
	);

	$new_id = wp_insert_attachment( $new_attachment, $new_file, $attachment->post_parent );

	if ( is_wp_error( $new_id ) ) {
		wp_delete_file( $new_file );
		return $new_id;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';

	$metadata = wp_generate_attachment_metadata( $new_id, $new_file );

	if ( ! empty( $metadata ) && ! is_wp_error( $metadata ) ) {
		wp_update_attachment_metadata( $new_id, $metadata );
	}

	// Copy common attachment metadata.
	$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

	if ( '' !== $alt ) {
		update_post_meta( $new_id, '_wp_attachment_image_alt', $alt );
	}

	return $new_id;
}

/**
 * Show success notice after list-view duplication.
 */
add_action( 'admin_notices', function () {
	if ( ! isset( $_GET['wp_mid_duplicated'] ) ) {
		return;
	}

	echo '<div class="notice notice-success is-dismissible"><p>Image duplicated successfully.</p></div>';
} );
