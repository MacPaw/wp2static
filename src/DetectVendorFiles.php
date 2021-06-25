<?php

namespace WP2Static;

/**
 * Class DetectVendorFiles
 *
 * @package WP2Static
 */
class DetectVendorFiles {

    /**
     * Detect vendor URLs from filesystem
     *
     * @return string[] list of URLs
     */
    public static function detect( string $wp_site_url ) : array {
        $vendor_files = [];

        // cache dir used by Autoptimize and other themes/plugins
        $vendor_cache_dir =
            SiteInfo::getPath( 'content' ) . 'cache/';

        if ( is_dir( $vendor_cache_dir ) ) {
            $site_url = SiteInfo::getUrl( 'site' );
            // This hack needed for cli version of plugin
            // In cli version we got http instead of https version, and it brokes our links
            // during static generation
            $content_url = SiteInfo::getUrl( 'content' );
            if(!str_contains($content_url, "https") && str_contains($site_url, "https")){
                    $content_url = str_replace( "http", "https", $content_url);
            }
            // get difference between home and wp-contents URL
            $prefix = str_replace(
                $site_url,
                '/',
                $content_url
            );

            $vendor_cache_urls = DetectVendorCache::detect(
                $vendor_cache_dir,
                SiteInfo::getPath( 'content' ),
                $prefix
            );

            $vendor_files = array_merge( $vendor_files, $vendor_cache_urls );
        }

        if ( class_exists( 'Custom_Permalinks' ) ) {
            global $wpdb;

            $query = "
                SELECT meta_value
                FROM %s
                WHERE meta_key = '%s'
                ";

            $custom_permalinks = [];

            $posts = $wpdb->get_results(
                sprintf(
                    $query,
                    $wpdb->postmeta,
                    'custom_permalink'
                )
            );

            if ( $posts ) {
                foreach ( $posts as $post ) {
                    $custom_permalinks[] = $wp_site_url . $post->meta_value;
                }

                $vendor_files = array_merge(
                    $vendor_files,
                    $custom_permalinks
                );
            }
        }

        return $vendor_files;
    }
}
