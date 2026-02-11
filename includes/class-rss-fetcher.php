<?php
// includes/class-rss-fetcher.php

if ( ! defined( 'ABSPATH' ) ) exit;

class PRTIMES_RSS_Fetcher {

    /**
     * RSS取得件数
     */
    const FETCH_LIMIT = 100;

    /**
     * RSS取得（メインから呼ばれる正式API）
     *
     * @return array
     */
    public function fetch(): array {

        if ( ! class_exists( 'SimplePie' ) ) {
            require_once ABSPATH . WPINC . '/class-simplepie.php';
        }

$feed = new SimplePie();
$feed->set_feed_url( 'https://prtimes.jp/index.rdf' );
$feed->enable_cache( true );
$feed->set_cache_duration( 30 * MINUTE_IN_SECONDS );
$feed->set_cache_location( WP_CONTENT_DIR . '/cache/prtimes-rss' );
$feed->init();


        if ( $feed->error() ) {
            error_log( '[PR TIMES] RSS error: ' . $feed->error() );
            return [];
        }

        $items = $feed->get_items( 0, self::FETCH_LIMIT );

        error_log(
            '[PR TIMES] RSS fetched: ' . ( is_array( $items ) ? count( $items ) : 0 )
        );

        return is_array( $items ) ? $items : [];
    }
}
