<?php
namespace PyApiDemo;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode handler for [python_products]
 * Renders a modern grid of products fetched from the Python API.
 */
class Shortcode_Products {

    /**
     * Register shortcode
     */
    public static function register() {
        add_shortcode( 'python_products', array( __CLASS__, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
    }

    /**
     * Enqueue CSS styles
     */
    public static function enqueue_styles() {
        wp_register_style( 'pyapi-products', false );
        wp_enqueue_style( 'pyapi-products' );

        $custom_css = "
        .pyapi-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        
        .pyapi-product-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .pyapi-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .pyapi-product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        
        .pyapi-product-image-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
        }
        
        .pyapi-product-content {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .pyapi-product-header {
            margin-bottom: 15px;
        }
        
        .pyapi-product-category {
            display: inline-block;
            background: #f0f7ff;
            color: #0066cc;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .pyapi-product-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: #1a1a1a;
            line-height: 1.4;
        }
        
        .pyapi-product-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        
        .pyapi-product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .pyapi-product-price {
            font-size: 22px;
            font-weight: 800;
            color: #0066cc;
        }
        
        .pyapi-product-price span {
            font-size: 14px;
            color: #666;
            font-weight: 400;
        }
        
        .pyapi-product-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #fff8e1;
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        .pyapi-product-rating-number {
            font-weight: 700;
            color: #ff9800;
        }
        
        .pyapi-product-rating-star {
            color: #ffc107;
            font-size: 16px;
        }
        
        .pyapi-product-stock {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 15px;
            align-self: flex-start;
        }
        
        .pyapi-product-in-stock {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .pyapi-product-out-of-stock {
            background: #ffebee;
            color: #c62828;
        }
        
        .pyapi-products-empty {
            text-align: center;
            padding: 60px 20px;
            background: #f9f9f9;
            border-radius: 12px;
            color: #666;
            font-size: 18px;
        }
        
        .pyapi-products-error {
            text-align: center;
            padding: 40px 20px;
            background: #ffebee;
            border-radius: 12px;
            color: #c62828;
            font-size: 16px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .pyapi-products-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .pyapi-products-grid {
                grid-template-columns: 1fr;
            }
            
            .pyapi-product-content {
                padding: 20px;
            }
        }
        ";

        wp_add_inline_style( 'pyapi-products', $custom_css );
    }

    /**
     * Shortcode callback
     * @param array $atts
     * @return string
     */
    public static function render_shortcode( $atts = array() ) {
        $atts = shortcode_atts( array(
                'limit' => 12,
                'columns' => 3,
        ), $atts, 'python_products' );

        $client = new API_Client();
        $client->clear_cache();
        $products = $client->get_products();

        if ( is_wp_error( $products ) ) {
            return '<div class="pyapi-products-error">Error fetching products: ' . esc_html( $products->get_error_message() ) . '</div>';
        }

        if ( empty( $products ) ) {
            return '<div class="pyapi-products-empty">No products available at the moment.</div>';
        }

        // Limit number of products
        $products = array_slice( $products, 0, intval( $atts['limit'] ) );

        // Build HTML output - modern grid layout
        ob_start();
        ?>
        <div class="pyapi-products-grid">
            <?php foreach ( $products as $product ) :
                $stock_class = $product['in_stock'] ? 'pyapi-product-in-stock' : 'pyapi-product-out-of-stock';
                $stock_text = $product['in_stock'] ? 'In Stock' : 'Out of Stock';
                ?>
                <div class="pyapi-product-card">
                    <!-- Product Image -->
                    <?php if ( ! empty( $product['image'] ) ) : ?>
                        <img src="<?php echo esc_url( $product['image'] ); ?>"
                             alt="<?php echo esc_attr( $product['name'] ); ?>"
                             class="pyapi-product-image">
                    <?php else: ?>
                        <div class="pyapi-product-image-placeholder">
                            No image available
                        </div>
                    <?php endif; ?>

                    <!-- Product Content -->
                    <div class="pyapi-product-content">
                        <!-- Category -->
                        <div class="pyapi-product-category">
                            <?php echo esc_html( $product['category'] ); ?>
                        </div>

                        <!-- Title -->
                        <h3 class="pyapi-product-title">
                            <?php echo esc_html( $product['name'] ); ?>
                        </h3>

                        <!-- Description -->
                        <p class="pyapi-product-description">
                            <?php echo esc_html( wp_trim_words( $product['description'], 20, '...' ) ); ?>
                        </p>

                        <!-- Stock Status -->
                        <div class="pyapi-product-stock <?php echo esc_attr( $stock_class ); ?>">
                            <?php echo esc_html( $stock_text ); ?>
                        </div>

                        <!-- Meta Information (Price & Rating) -->
                        <div class="pyapi-product-meta">
                            <!-- Price -->
                            <div class="pyapi-product-price">
                                <?php echo number_format_i18n( floatval( $product['price_eur'] ), 2 ); ?>
                                <span>EUR</span>
                            </div>

                            <!-- Rating -->
                            <div class="pyapi-product-rating">
                                <span class="pyapi-product-rating-star">★</span>
                                <span class="pyapi-product-rating-number">
                                <?php echo number_format_i18n( floatval( $product['rating'] ), 1 ); ?>
                            </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}