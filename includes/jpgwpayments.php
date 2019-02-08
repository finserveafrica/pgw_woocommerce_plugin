<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 11/19/18
 * Time: 2:38 PM
 */

class Jpgw_payments_Table extends WP_List_Table {

    function __construct(){
        global $status, $page;

        parent::__construct( array(
            'singular'  => __( 'Payment', 'sp' ),     //singular name of the listed records
            'plural'    => __( 'Payments', 'sp' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?

        ) );
        // Add action to resize column widths
        add_action('admin_head', 'my_column_width');
            function my_column_width() {
                echo '<style type="text/css">';
                echo '.wp-list-table .column-order_id { width: 30%; }';
                echo '.wp-list-table .column-status { width: 30%; }';
                echo '.wp-list-table .column-trx_time { width: 40%; }';
                echo '.wp-list-table .column-transaction_id { width: 40%; }';
                echo '.wp-list-table .column-payment_method { width: 40%; }';
                echo '.wp-list-table .column-amount { width: 30%; }';

                echo '</style>';
            }

    }
   // function to retrieve payments transactions
    public static function get_payments( $per_page = 10, $page_number = 1 ) {

        global $wpdb;

        $appTable = $wpdb->prefix . "jpgw_trx";
        $sql = ("SELECT * FROM $appTable");

        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        }

        $sql .= " LIMIT $per_page";

        $sql .= " OFFSET " . ( $page_number - 1 ) * $per_page;

        $result = $wpdb->get_results( $sql, 'ARRAY_A' );

        return $result;

    }

   // Display text when the transactions table is empty
    public function no_items() {
        _e( 'No payments Transactions found.' );
    }
    // Function to count records
    public static function record_count() {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}jpgw_trx";

        return $wpdb->get_var( $sql );
    }

   // Define Table Columns
    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'order_id':
            case 'status':
            case 'trx_time':
            case 'transaction_id':
            case 'payment_method':
            case 'amount':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }


   // Define the  Search Box that will appear on the Transactions table
    public function search_box( $text, $input_id ) {
        if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
            return;

        $input_id = $input_id . '-search-input';

        if ( ! empty( $_REQUEST['orderby'] ) )
            echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
        if ( ! empty( $_REQUEST['order'] ) )
            echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
        if ( ! empty( $_REQUEST['post_mime_type'] ) )
            echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
        if ( ! empty( $_REQUEST['detached'] ) )
            echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
        </p>
        <?php
    }


    public function get_columns(){
        $columns = array(
            'order_id' => __( 'Order ID', 'mylisttable' ),
            'status'    => __( 'Status', 'mylisttable' ),
            'trx_time'      => __( 'Transaction Time', 'mylisttable' ),
            'transaction_id'      => __( 'Transaction ID', 'mylisttable' ),
            'payment_method'      => __( 'Payment Method', 'mylisttable' ),
            'amount'      => __( 'Amount', 'mylisttable' )
        );
        return $columns;
    }

    // Define Table sortable columns
    public function get_sortable_columns() {
        $sortable_columns = array(
            'order_id'  => array('order_id',false),
            'status' => array('status',false),
            'trx_time'   => array('trx_time',false),
            'transaction_id'   => array('transaction_id',false),
            'payment_method'   => array('payment_method',false),
            'amount'   => array('amount',false),


        );
        return $sortable_columns;
    }

    // Function to populate the transactions table with data
    public function prepare_items() {

            global $wpdb;

            $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;
            $per_page = 5;
            $page_number = 1;
            $appTable = $wpdb->prefix . "jpgw_trx";
            $do_search = ( $search ) ? $wpdb->prepare(" SELECT * FROM $appTable WHERE order_id LIKE '%%%s%%' ", $search ) : '';


            if ( ! empty( $_REQUEST['orderby'] ) ) {
                $do_search .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
                $do_search .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
            }

            $do_search .= " LIMIT $per_page";

            $do_search .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

            $result = $wpdb->get_results( $do_search, 'ARRAY_A' );

            $this->_column_headers = $this->get_column_info();

            $per_page = $this->get_items_per_page('customers_per_page', 10);
            $current_page = $this->get_pagenum();
            $total_items = self::record_count();

            $this->set_pagination_args([
                'total_items' => $total_items, //WE have to calculate the total number of items
                'per_page' => $per_page //WE have to determine how many items to show on a page
            ]);

            $this->items = self::get_payments($per_page, $current_page);
            if(( isset( $_REQUEST['s'] ) )) {
                $this->items = $result;
            }

    }

} //class


class SP_Plugin {

    // class instance
    static $instance;

    // customer WP_List_Table object
    public $customers_obj;

    // class constructor
    public function __construct() {
        add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
        add_action( 'admin_menu', [ $this, 'plugin_menu' ], 100 );
    }


    public static function set_screen( $status, $option, $value ) {
        return $value;
    }

    // Add submenu under the Jenga Payments Admin Menu to populate the transactions table
    public function plugin_menu() {

        $hook = add_submenu_page(
        'jpgw',
        'JPGW Transactions',
        'Transactions',
        'manage_options',
        'jpgw_transactions',
        [ $this, 'plugin_settings_page' ]
    );
        add_action( "load-$hook", [ $this, 'screen_option' ] );

    }

    /**
     * Plugin settings page
     */

    // Define the table content and text to appear in the transactions page section
    public function plugin_settings_page() {
        ?>
        <div class="wrap">
            <h2>Jenga PGW Payments Transactions</h2>

            <form method="post">
                                <?php
                                $this->customers_obj->prepare_items();
                                $this->customers_obj->search_box('Search', 'search');
                                $this->customers_obj->display(); ?>
                            </form>
                        </div>
                <br class="clear">

        <?php
    }

    /**
     * Screen options
     */
    public function screen_option() {

        $option = 'per_page';
        $args   = [
            'label'   => 'Customers',
            'default' => 5,
            'option'  => 'customers_per_page'
        ];

        add_screen_option( $option, $args );

        $this->customers_obj = new Jpgw_payments_Table();
    }


    /** Singleton instance */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

}

add_action( 'plugins_loaded', function () {
    SP_Plugin::get_instance();
} );