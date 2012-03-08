<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

//delete admin settings
delete_option( 'revostock_gallery_settings' );

//delete transients
$cache = get_option( 'revostock_gallery_cache' );
foreach ( $cache as $request => $transient ) {
	delete_transient( $transient );
}

//delete cache
delete_option( 'revostock_gallery_cache' );

//unschedule cron
$next_run = wp_next_scheduled( 'revostock_gallery_cron_hook' );
wp_unschedule_event( $next_run, 'revostock_gallery_cron_hook');

?>