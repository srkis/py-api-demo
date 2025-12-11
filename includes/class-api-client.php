<?php
namespace PyApiDemo;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * API Client
 * Responsible for communicating with the external Python API.
 * - Uses wp_remote_get / wp_remote_post
 * - Sends API key in query string
 * - Handles basic error checking and caching (transients)
 */
class API_Client {

    /**
     * Option names
     */
    const OPTION_API_URL = 'pyapi_demo_api_url';
    const OPTION_API_KEY = 'pyapi_demo_api_key';

    /**
     * Cache TTL in seconds to reduce API calls (adjustable)
     * @var int
     */
    private $cache_ttl = 60;

    /**
     * Fetch list of products
     * @return array|WP_Error
     */
    public function get_products() {
        $transient_key = 'pyapi_demo_products_cache';

//        $cached = get_transient($transient_key);
//        if ($cached !== false) {
//            return $cached;
//        }

        $this->clear_cache();

        $api_url = esc_url_raw(get_option(self::OPTION_API_URL, ''));
        $api_key = sanitize_text_field(get_option(self::OPTION_API_KEY, ''));

        if (empty($api_url)) {
            return new \WP_Error('pyapi_demo_no_api_url', 'API URL is not configured.');
        }

        $endpoint = trailingslashit($api_url) . 'products';
        if (!empty($api_key)) {
            $endpoint = add_query_arg('token', $api_key, $endpoint);
        }

        $response = wp_remote_get($endpoint, ['timeout' => 10]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code !== 200) {
            return new \WP_Error('pyapi_demo_bad_response', "API responded with HTTP code {$code}.");
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('pyapi_demo_json_error', 'Unable to parse JSON from API.');
        }


        $products = isset($data['products']) ? $data['products'] : [];

        $sanitized = $this->sanitize_products_array($products);

       // set_transient($transient_key, $sanitized, $this->cache_ttl);

        return $sanitized;
    }

    /**
     * Sanitize products array
     * @param array $items
     * @return array
     */
    private function sanitize_products_array($items) {
        if (!is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;

            $out[] = [
                'id' => isset($item['id']) ? sanitize_text_field((string)$item['id']) : '',
                'name' => isset($item['name']) ? sanitize_text_field($item['name']) : '',
                'slug' => isset($item['slug']) ? sanitize_title($item['slug']) : '',
                'price_eur' => isset($item['price_eur']) ? floatval($item['price_eur']) : 0,
                'description' => isset($item['description']) ? wp_kses_post($item['description']) : '',
                'image' => isset($item['image']) ? esc_url_raw($item['image']) : '',
                'category' => isset($item['category']) ? sanitize_text_field($item['category']) : '',
                'in_stock' => isset($item['in_stock']) ? boolval($item['in_stock']) : false,
                'rating' => isset($item['rating']) ? floatval($item['rating']) : 0,
            ];
        }

        return $out;
    }


    /**
     * Allow external code to clear cache.
     */
    public function clear_cache() {
        delete_transient( 'pyapi_demo_products_cache' );
    }
}
