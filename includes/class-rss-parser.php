<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PRTIMES_RSS_Parser {

    private $items;

    public function __construct( array $items ) {
        $this->items = $items;
    }

    /**
     * Post_Creator が期待する形式で返す
     */
    public function get_items() {
        return $this->items;
    }
}
