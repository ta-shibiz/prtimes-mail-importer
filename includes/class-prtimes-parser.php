<?php
// includes/class-prtimes-parser.php

if ( ! defined( 'ABSPATH' ) ) exit;

class PRTIMES_Parser {

    private array $items;
    private array $keywords = [];
    private array $matched  = [];

    public function __construct( array $rss_items ) {

        $this->items = $rss_items;

        // 管理画面で設定したキーワード取得
        $raw_keywords = get_option( 'prtimes_title_keywords', [] );

        // ★ 型正規化（ここが重要）
        if ( is_string( $raw_keywords ) ) {
            // 改行 or カンマ区切り対応
            $raw_keywords = preg_split( '/[\r\n,]+/', $raw_keywords );
        }

        if ( is_array( $raw_keywords ) ) {
            // trim & 空除去
            $this->keywords = array_values(
                array_filter(
                    array_map( 'trim', $raw_keywords )
                )
            );
        }

        $this->parse();
    }

    /**
     * 判定処理
     */
    private function parse(): void {

        foreach ( $this->items as $item ) {

            $title = $item->get_title();
            $matched_keywords = [];

            foreach ( $this->keywords as $kw ) {
                if ( $kw !== '' && mb_stripos( $title, $kw ) !== false ) {
                    $matched_keywords[] = $kw;
                }
            }

            if ( empty( $matched_keywords ) ) {
                continue;
            }

            $this->matched[] = [
                'item'             => $item,
                'matched_keywords' => $matched_keywords,
            ];

            error_log(
                '[PR TIMES] MATCH: ' . $title .
                ' / KW: ' . implode( ', ', $matched_keywords )
            );
        }
    }

    /**
     * 判定済みアイテム一覧を返す
     */
    public function get_matched_items(): array {
        return $this->matched;
    }
}
