<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
delete_option( 'cpf_cache_v' );
delete_option( 'cpf_rewrite_version' );
