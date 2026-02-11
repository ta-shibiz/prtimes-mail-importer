<?php
/**
 * PR TIMES Importer - Admin Settings
 */
if ( !defined( 'ABSPATH' ) )exit;

/**
 * 設定項目登録
 */
add_action( 'admin_init', function () {


    register_setting(
        'prtimes_mail_importer_settings',
        'prtimes_mail_importer'
    );

    register_setting(
        'prtimes_mail_importer_settings',
        'prtimes_title_keywords', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => '',
        ]
    );


} );

/**
 * 管理画面メニュー追加
 */
add_action( 'admin_menu', function () {

add_options_page(
    'PR TIMES Importer 設定',
    'PR TIMES Importer',
    'edit_posts',
    'prtimes-mail-importer',
    'prtimes_mail_importer_settings_page'
);


} );

/**
 * 設定画面描画
 */
function prtimes_mail_importer_settings_page() {

    $opts = get_option( 'prtimes_mail_importer', [] );
    $keywords = get_option( 'prtimes_title_keywords', '' );

    ?>
<div class="wrap">
    <h1>PR TIMES Importer 設定</h1>
    <?php
    $manual_url = wp_nonce_url(
        admin_url( 'edit.php?post_type=topics&prtimes_manual_run=1' ),
        'prtimes_manual_run'
    );
    ?>
    <div style="margin:15px 0;padding:10px;background:#f6f7f7;border-left:4px solid #007cba;">
        <p style="margin:0 0 8px;"> <strong>PR TIMES 手動受信</strong> </p>
        <a href="<?php echo esc_url( $manual_url ); ?>"
       class="button button-primary"> 今すぐ受信する </a> <span style="margin-left:10px;color:#555;"> （設定変更後の動作確認に） </span> </div>
    <form method="post" action="options.php">
        <?php settings_fields( 'prtimes_mail_importer_settings' ); ?>
        <table class="form-table">
            <tr>
                <th></th>
                <td>プレビュー確認<br>
                <a href="/topics" target="_blank">公開ページトピック一覧</a>
                    </td>
            </tr>
            
            <!-- メール設定（将来廃止予定だが残す） -->
            <tr style="display:none;">
                <th scope="row">メールサーバー</th>
                <td><input type="text"
                               name="prtimes_mail_importer[server]"
                               value="<?php echo esc_attr( $opts['server'] ?? '' ); ?>"
                               class="regular-text"></td>
            </tr>
            <tr style="display: none;">
                <th scope="row">ユーザー名</th>
                <td><input type="text"
                               name="prtimes_mail_importer[user]"
                               value="<?php echo esc_attr( $opts['user'] ?? '' ); ?>"
                               class="regular-text"></td>
            </tr>
            <tr style="display: none;">
                <th scope="row">パスワード</th>
                <td><input type="password"
                               name="prtimes_mail_importer[password]"
                               value="<?php echo esc_attr( $opts['password'] ?? '' ); ?>"
                               class="regular-text"></td>
            </tr>
            <tr style="display: none;">
                <th scope="row">対象送信元</th>
                <td><input type="text"
                               name="prtimes_mail_importer[from]"
                               value="<?php echo esc_attr( $opts['from'] ?? '' ); ?>"
                               class="regular-text">
                    <p class="description"> ※ メール方式使用時のみ（現在は非推奨） </p></td>
            </tr>
            
            <!-- ▼ RSS方式：タイトル判定キーワード -->
            <tr>
                <th scope="row"> PR TIMES タイトル判定キーワード </th>
                <td><textarea
                            name="prtimes_title_keywords"
                            rows="8"
                            class="large-text"
                        ><?php echo esc_textarea( $keywords ); ?></textarea>
                    <p class="description"> 1行につき1キーワードを入力してください。<br>
                        PR TIMES RSS の<strong>タイトルに含まれる場合のみ</strong>記事化されます。 </p>
                    <p class="description"> 記入例：<br>
                        サバゲー<br>
                        エアガン<br>
                        ミリタリー<br>
                        アウトドア </p></td>
            </tr>
            
        </table>
        <?php submit_button(); ?>
    </form>
</div>

<?php
}