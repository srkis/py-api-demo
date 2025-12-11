<?php
namespace PyApiDemo;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Page - settings for API URL and API Key, plus Add Product
 */
class Admin_Page {

    /**
     * Register admin menu
     */
    public static function register_menu() {
        add_options_page(
                'Python API Demo Settings',        // page title
                'Python API Demo',                 // menu title
                'manage_options',                  // capability
                'pyapi-demo-settings',             // menu slug
                array( __CLASS__, 'render_settings_page' ) // callback
        );
    }

    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting( 'pyapi_demo_settings', API_Client::OPTION_API_URL, array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => '',
        ) );

        register_setting( 'pyapi_demo_settings', API_Client::OPTION_API_KEY, array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
        ) );
    }

    /**
     * Render admin settings page
     */
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Process Add Product submission
        $message = '';
        if ( isset( $_POST['pyapi_add_product_nonce'] ) && wp_verify_nonce( $_POST['pyapi_add_product_nonce'], 'pyapi_add_product' ) ) {
            $result = self::handle_add_product();
            $message = is_wp_error( $result ) ? '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>' :
                    '<div class="notice notice-success"><p>Product added successfully! ID: ' . intval( $result['product_id'] ) . '</p></div>';
        }

        $api_url = esc_url( get_option( API_Client::OPTION_API_URL, '' ) );
        $api_key = esc_html( get_option( API_Client::OPTION_API_KEY, '' ) );
        ?>
        <div class="wrap">
            <h1>Python API Demo Settings</h1>
            <?php echo $message; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'pyapi_demo_settings' );
                do_settings_sections( 'pyapi_demo_settings' );
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pyapi_demo_api_url">API Base URL</label></th>
                        <td><input name="<?php echo esc_attr( API_Client::OPTION_API_URL ); ?>" id="pyapi_demo_api_url" type="text" value="<?php echo esc_attr( $api_url ); ?>" class="regular-text" placeholder="http://localhost:8000/"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pyapi_demo_api_key">API Key (token)</label></th>
                        <td><input name="<?php echo esc_attr( API_Client::OPTION_API_KEY ); ?>" id="pyapi_demo_api_key" type="text" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" placeholder="MY_SECRET_TOKEN_123"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr>
            <h2>Add New Product</h2>
            <form method="post">
                <?php wp_nonce_field( 'pyapi_add_product', 'pyapi_add_product_nonce' ); ?>
                <table class="form-table">
                    <tr><th>Name</th><td><input type="text" name="name" required></td></tr>
                    <tr><th>Slug</th><td><input type="text" name="slug"></td></tr>
                    <tr><th>Price (EUR)</th><td><input type="number" step="0.01" name="price_eur" required></td></tr>
                    <tr><th>Description</th><td><textarea name="description"></textarea></td></tr>
                    <tr><th>Image URL</th><td><input type="url" name="image"></td></tr>
                    <tr><th>Category</th><td><input type="text" name="category"></td></tr>
                    <tr><th>In Stock</th>
                        <td>
                            <select name="in_stock">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </td>
                    </tr>
                    <tr><th>Rating</th><td><input type="number" step="0.1" name="rating"></td></tr>
                </table>
                <?php submit_button('Add Product'); ?>
            </form>

            <p>Plugin caches product results for <strong>60 seconds</strong> by default.</p>
        </div>
        <?php
    }

    /**
     * Handle Add Product POST
     */
    private static function handle_add_product() {
        if ( empty($_POST) || ! isset($_POST['pyapi_add_product_nonce']) || ! wp_verify_nonce($_POST['pyapi_add_product_nonce'], 'pyapi_add_product') ) {
            return;
        }

        $api_url = esc_url_raw( get_option( API_Client::OPTION_API_URL, '' ) );
        $api_key = sanitize_text_field( get_option( API_Client::OPTION_API_KEY, '' ) );

        if ( empty( $api_url ) || empty( $api_key ) ) {
            return new \WP_Error( 'pyapi_demo_missing_settings', 'API URL or API key is missing.' );
        }

        // Sanitize input
        $data = array(
                'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
                'slug'        => sanitize_title( $_POST['slug'] ?? '' ),
                'price_eur'   => floatval( $_POST['price_eur'] ?? 0 ),
                'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
                'image'       => esc_url_raw( $_POST['image'] ?? '' ),
                'category'    => sanitize_text_field( $_POST['category'] ?? '' ),
                'in_stock'    => isset($_POST['in_stock']) && $_POST['in_stock'] == '1' ? true : false,
                'rating'      => floatval( $_POST['rating'] ?? 0 ),
        );

        if ( empty( $data['name'] ) || $data['price_eur'] <= 0 ) {
            return new \WP_Error( 'pyapi_demo_invalid_data', 'Please provide valid name and price.' );
        }

        $endpoint = trailingslashit( $api_url ) . 'product/add';
        $endpoint = add_query_arg( 'token', $api_key, $endpoint );

        $response = wp_remote_post( $endpoint, array(
                'headers' => array(
                        'Content-Type' => 'application/json',
                ),
                'body'    => wp_json_encode($data),
                'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $json = json_decode( $body, true );

        if ( $code !== 200 ) {
            $detail = isset($json['detail']) ? $json['detail'] : 'Unknown error';
            return new \WP_Error( 'pyapi_demo_add_failed', "API error: {$detail}" );
        }

        // Clear cached products so shortcode shows the new product immediately
        $api_client = new API_Client();
        $api_client->clear_cache();

        return $json;
    }




}
