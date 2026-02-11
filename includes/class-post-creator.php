<?php
// includes/class-post-creator.php

if ( ! defined( 'ABSPATH' ) ) exit;

class PRTIMES_Post_Creator {

    private PRTIMES_Parser $parser;

    private int $created = 0;
    private int $skipped = 0;

    public function __construct( PRTIMES_Parser $parser ) {
        $this->parser = $parser;
    }

    /**
     * 一括処理エントリポイント
     */
    public function process(): void {

        $items = $this->parser->get_matched_items();

        if ( empty( $items ) ) {
            error_log( '[PR TIMES] No matched items' );
            return;
        }

        foreach ( $items as $data ) {
            $this->create_post(
                $data['item'],
                $data['matched_keywords']
            );
        }

        error_log(
            sprintf(
                '[PR TIMES] DONE created=%d skipped=%d',
                $this->created,
                $this->skipped
            )
        );
    }

    /**
     * 投稿作成（重複防止あり）
     */
    private function create_post( $item, array $matched_keywords ): void {

        $url = strtok( $item->get_link(), '?' );

        if ( $this->is_duplicate( $url ) ) {
            $this->skipped++;
            error_log( '[PR TIMES] SKIP duplicate: ' . $url );
            return;
        }

        
$content = $item->get_content();

$source_url = $url; // _prtimes_url に保存するURL

$footer = '
<hr>
<p style="font-size:14px;color:#555;">
この記事は <strong>PR TIMES</strong> から配信されたプレスリリースです。<br>
▶︎ <a href="' . esc_url( $source_url ) . '" target="_blank" rel="noopener">
PR TIMESで全文を読む
</a>
</p>
';

$content .= $footer;

        
$post_id = wp_insert_post([
    'post_type'    => 'topics',
    'post_status'  => 'private',
    'post_title'   => wp_strip_all_tags( $item->get_title() ),
    'post_content' => $content,
//    'post_excerpt' => mb_strimwidth(
//        wp_strip_all_tags( $item->get_description() ),
//        0,
//        120,
//        '…',
//        'UTF-8'
//    ),
]);

        if ( is_wp_error( $post_id ) ) {
            error_log( '[PR TIMES] FAILED insert: ' . $item->get_title() );
            return;
        }
        
        if ( is_wp_error( $post_id ) ) {
            error_log( '[PR TIMES] FAILED insert: ' . $item->get_title() );
            return;
        }

        // ① 元記事URLを保存（既存）
        update_post_meta( $post_id, '_prtimes_url', $url );
        update_post_meta( $post_id, '_prtimes_matched_keywords', $matched_keywords );

        // ★② OGP画像を取得して保存（ここに追加）
        if ( function_exists( 'prtimes_get_ogp_image_cached' ) ) {
            $ogp_image = prtimes_get_ogp_image_cached( $url );

            if ( ! empty( $ogp_image ) ) {
                update_post_meta( $post_id, '_prtimes_ogp_image', $ogp_image );
            }
        }

        $this->created++;
        

        update_post_meta( $post_id, '_prtimes_url', $url );
        update_post_meta( $post_id, '_prtimes_matched_keywords', $matched_keywords );

        $this->created++;

        error_log(
            '[PR TIMES] CREATED: ' .
            $item->get_title() .
            ' / KW: ' . implode( ', ', $matched_keywords )
        );
    }

    /**
     * URL重複チェック
     */
    private function is_duplicate( string $url ): bool {

        $q = new WP_Query([
            'post_type'      => 'topics',
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_prtimes_url',
                    'value' => $url,
                ]
            ],
            'fields'         => 'ids',
            'posts_per_page' => 1,
        ]);

        return $q->have_posts();
    }
}
