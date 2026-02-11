<?php
/**
 * Plugin Name: PR TIMES Mail Importer
 * Description: PR TIMES から届くメールを WordPress に自動取り込み（下書き）
 * Version: 1.0.1
 * Author: Midnight Code
 */

if ( !defined( 'ABSPATH' ) )exit;

// ===============================
// 定数
// ===============================
define( 'PRTIMES_MI_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRTIMES_MI_URL', plugin_dir_url( __FILE__ ) );
define( 'PRTIMES_MI_LOG', PRTIMES_MI_DIR . 'logs/log.txt' );

// ===============================
// クラス読み込み
// ===============================
//require_once PRTIMES_MI_DIR . 'includes/class-mail-fetcher.php';
require_once PRTIMES_MI_DIR . 'includes/class-prtimes-parser.php';
require_once PRTIMES_MI_DIR . 'includes/class-rss-fetcher.php';
require_once PRTIMES_MI_DIR . 'includes/class-rss-parser.php';
require_once PRTIMES_MI_DIR . 'includes/class-post-creator.php';
require_once PRTIMES_MI_DIR . 'includes/admin-settings.php';

// ===============================
// Cron / 手動実行 共通処理
// ===============================
add_action( 'prtimes_mail_importer_cron', function () {

    // ---- cron 発火確認 ----
    file_put_contents(
        PRTIMES_MI_LOG,
        '[' . date( 'Y-m-d H:i:s' ) . "] cron fired\n",
        FILE_APPEND
    );

    // RSS取得
    $fetcher = new PRTIMES_RSS_Fetcher();
    $rss_items = $fetcher->fetch();

    if ( empty( $rss_items ) ) {
        file_put_contents(
            PRTIMES_MI_LOG,
            '[' . date( 'Y-m-d H:i:s' ) . "] no rss items\n",
            FILE_APPEND
        );
        return;
    }

    // Parser
    $parser = new PRTIMES_Parser( $rss_items );

    // Creator
    $creator = new PRTIMES_Post_Creator( $parser );

    // 記事化
    $creator->process();
} );


// ===============================
// 有効化時：logs 初期化
// ===============================
register_activation_hook( __FILE__, function () {

    $log_dir = PRTIMES_MI_DIR . 'logs';

    if ( !file_exists( $log_dir ) ) {
        wp_mkdir_p( $log_dir );
    }

    $log_file = $log_dir . '/log.txt';

    if ( !file_exists( $log_file ) ) {
        file_put_contents(
            $log_file,
            '[' . date( 'Y-m-d H:i:s' ) . "] Log initialized\n"
        );
    }
} );


// トピック一覧に「PR TIMES 手動受信」ボタン追加
add_action( 'admin_notices', function () {

    $screen = get_current_screen();
    if ( !$screen ) return;

    // topics の一覧ページのみ
    if ( $screen->id !== 'edit-topics' ) return;

    if ( !current_user_can( 'edit_posts' ) ) return;

    $url = wp_nonce_url(
        admin_url( 'edit.php?post_type=topics&prtimes_manual_run=1' ),
        'prtimes_manual_run'
    );

    echo '<div class="notice notice-info" style="padding:10px;">';
    echo '<strong>PR TIMES：</strong> ';
    echo '<a href="' . esc_url( $url ) . '" class="button button-primary">手動受信</a>';
    echo '<span style="margin-left:10px;color:#555;">（PR TIMES 記事を即時取り込み）</span>';
    echo '</div>';
} );

//受信処理

/**
 * トピック一覧からの手動受信処理
 */
add_action( 'admin_init', function () {

    if ( !is_admin() ) {
        return;
    }

    if ( !current_user_can( 'edit_posts' ) ) {
        return;
    }

    if (
        isset( $_GET[ 'prtimes_manual_run' ] ) &&
        $_GET[ 'prtimes_manual_run' ] === '1' &&
        isset( $_GET[ '_wpnonce' ] ) &&
        wp_verify_nonce( $_GET[ '_wpnonce' ], 'prtimes_manual_run' )
    ) {

        do_action( 'prtimes_mail_importer_cron' );

        wp_redirect(
            admin_url( 'edit.php?post_type=topics&prtimes_done=1' )
        );
        exit;
    }
} );

//完了メッセージ
add_action( 'admin_notices', function () {

    if ( isset( $_GET[ 'prtimes_done' ] ) ) {
        echo '<div class="notice notice-success">';
        echo '<p>PR TIMES 記事を取り込みました。</p>';
        echo '</div>';
    }
} );
//サイドメニュー「トピック」にサブメニュー追加
add_action( 'admin_menu', function () {

    add_submenu_page(
        'edit.php?post_type=topics',
        'PR TIMES 手動受信',
        'PR TIMES 受信',
        'edit_posts',
        'prtimes-manual-run',
        function () {

            if ( !current_user_can( 'edit_posts' ) ) return;

            if ( !isset( $_GET[ '_wpnonce' ] ) ||
                !wp_verify_nonce( $_GET[ '_wpnonce' ], 'prtimes_manual_run' )
            ) {
                wp_die( 'Invalid request' );
            }

            do_action( 'prtimes_mail_importer_cron' );

            echo '<div class="wrap">';
            echo '<h1>PR TIMES 手動受信</h1>';
            echo '<p>PR TIMES 記事を取り込みました。</p>';
            echo '<p><a href="' . admin_url( 'edit.php?post_type=topics' ) . '" class="button">トピック一覧へ戻る</a></p>';
            echo '</div>';
        }


    );
} );



// トピックに「PR TIMES 設定」サブメニュー追加
add_action( 'admin_menu', function () {

add_submenu_page(
    'edit.php?post_type=topics',
    'PR TIMES Importer 設定',
    'PR TIMES 設定',
    'manage_options',
    'prtimes-mail-importer',
    'prtimes_mail_importer_settings_page'
);


}, 20 );


/**
 * URLからOGP画像を取得（og:image）
 */
function prtimes_get_ogp_image( $url ) {

    if ( empty( $url ) ) return '';

    $response = wp_remote_get( $url, [
        'timeout'   => 10,
        'user-agent'=> 'WordPress'
    ]);

    if ( is_wp_error( $response ) ) {
        return '';
    }

    $html = wp_remote_retrieve_body( $response );
    if ( empty( $html ) ) {
        return '';
    }

    if ( preg_match(
        '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i',
        $html,
        $matches
    )) {
        return esc_url_raw( $matches[1] );
    }

    return '';
}
function prtimes_get_ogp_image_cached( $url ) {

    $key = 'prtimes_ogp_' . md5( $url );
    $cached = get_transient( $key );

    if ( $cached !== false ) {
        return $cached;
    }

    $ogp = prtimes_get_ogp_image( $url );

    // 取得できなくても空でキャッシュ（無限リトライ防止）
    set_transient( $key, $ogp, DAY_IN_SECONDS );

    return $ogp;
}
