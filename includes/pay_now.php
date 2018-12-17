<?php

require_once( FLW_WC_DIR_PATH . 'includes/class.flw_wc_payment_gateway.php' );

// $flw_object = FLW_WC_Payment_Gateway::get_instance();


class Flw_Pay extends FLW_WC_Payment_Gateway {

  protected $prod_id, $prod_amount, $prod_name, $prod_currency;

  public static function get_instance()
  {
      if ( NULL === self::$instance )
          self::$instance = new self;

      return self::$instance;
  }

  // The initializing function
  public function __construct(){

    global $product;

    parent::__construct();


    $this->prod_id;
    $this->prod_amount;
    $this->prod_name;
    $this->prod_currency;

    add_action( 'woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
    add_action( 'woocommerce_after_shop_loop_item', array($this, 'content_after_addtocart_button'), 10);


    add_action( 'wp_ajax_verify_payment', array( $this, 'verify_payment' ) );
    add_action( 'wp_ajax_nopriv_verify_payment', array( $this, 'verify_payment' ) );
    add_action( 'phpmailer_init', 'my_phpmailer_example' );
    }

    // Function that displays buy now button after add to cart

  public function content_after_addtocart_button()
  {
    global $product, $woocommerce;

    $this->prod_id = $product->id;
    $this->prod_name = $product->get_name();
    $this->prod_currency = get_woocommerce_currency_symbol();

    $amount_str = strip_tags($product->get_price_html());
    $decode_amount = html_entity_decode($amount_str);
    $remove_currency = substr($decode_amount, 1);
    $this->prod_amount = preg_replace('/[^0-9.]/', "", $remove_currency);

    
    if ( 'yes' == $this->buy_now && !empty($this->button_name) && $product->is_downloadable()) {
      $display_button = apply_filters( 'woocommerce_loop_add_to_cart_link',
        sprintf( '<a href="javascript:void(0)" rel="nofollow" data-the_product_id="%s" data-the_amount="%s" data-the_product_name="%s" data-the_product_sku="%s" class="%s product_type_%s %s"><span class="filter-popup">%s</span></a>',
            esc_attr($this->prod_id ),
            esc_attr($this->prod_amount),
            esc_attr($this->prod_name ),
            esc_attr( $product->get_sku() ),
            $product->is_purchasable() ? 'add_to_cart_button' : '',
            esc_attr( $product->product_type ),
            esc_attr( "buy-now" ),
            esc_html( $this->button_name )
        ),
    $product );

    // console.log(displayButtons[i].getAttribute('data-product_id'));

    echo "<script>var displayButtons =  document.getElementsByClassName( 'ajax_add_to_cart' );
          var theButton = '".$display_button."';
          for (var i = 0; i < displayButtons.length; i++){
            if (displayButtons[i].getAttribute('data-product_id') === '".$this->prod_id."') {
              displayButtons[i].parentElement.innerHTML += theButton;
            }
          };
    </script>";
    
      echo $this->fill_info();
      $this->pay_now_scripts();
      echo $this->verify_payment();
    }
  }

  // Function that loads scripts

  public function pay_now_scripts() {
  
    $pay_args["amount"] = $this->prod_amount;
    $pay_args["pb_key"] = $this->public_key;
    // $pay_args["payment_method"] = $this->payment_method;
    $pay_args["payment_style"] = $this->payment_style;
    $pay_args["pay_country"] = $this->country;
    $pay_args["currency"] = $this->prod_currency;
    $pay_args["logo"] = $this->modal_logo;
    $pay_args["title"] = $this->title;
    $pay_args['desc'] = $this->description;
    $pay_args["id"] = $this->prod_id;
    $pay_args["prod_name"] = $this->prod_name;
    $pay_args["txnref"] = "WOOC_" . $this->prod_id . '_' . time();
    $pay_args["cb_url"] = admin_url( 'admin-ajax.php' );
         
      if( $this->payment_style == 'inline'){
        wp_enqueue_script( 'flwpbf_pay_now_inline_js', $this->base_url . '/flwv3-pug/getpaidx/api/flwpbf-inline.js', array(), '1.0.0', true );
      }else{
        wp_enqueue_script( 'flwpbf_pay_now_inline_js', $this->base_url . '/flwv3-pug/getpaidx/api/flwpbf-inline.js', array(), '1.0.0', true );
      }

      wp_enqueue_script( 'fancybox_js', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.js', null, null, true );

      wp_enqueue_script( 'flw_pay_now_js', plugins_url( 'assets/js/flw_pay_now.js', FLW_WC_PLUGIN_FILE ), array( 'jquery' ), '1.0.0', true );

      wp_localize_script( 'flw_pay_now_js', 'flw_pay_now_args', $pay_args );
  }

  // Function that display form on modal

  public function fill_info() {
    return '<div style="display: none;" id="hidden-content">
          <h4>Please fill the information below to proceed to payment</h4>
          <div id="fan-modal-body">
             <form method="POST" action="" id="form" autocomplete="off">
               <div class="pay-group pay-md-12">
                 <label for="cust-email">Email: </label>
                 <input type="email" name="cust-email" class="pay-control" id="cust-email" required="required" placeholder="Enter your email">
               </div>
               <div class="pay-group pay-md-12">
                 <label for="cust-number">Phonenumber: </label>
                 <input type="tel" name="phonenumber" class="pay-control" id="cust-number" required="required" placeholder="Enter your phone number">
               </div>
               <div class="pay-md-12">
                <button type="submit" name="proceed" id="proceed" class="pay pay-info pay-lg" style="display: block; margin: auto">Proceed</button>
              </div>
           </form>
           </div>
    </div>
    
    <div style="display: none;" id="hidden-loader">
        <img class="pay-loader" src="'.plugin_dir_url( dirname( __FILE__ ) ) . 'assets/img/loader.svg'.'">
    </div>

    <div style="display: none;" id="hidden-download"></div>';
  }


  // Rave payment verification ajax callback function

  public function verify_payment() {

    global $product;
    $tx_ref = $_POST['txRef'];
    $flw_ref = $_POST['flwRef'];

    $txn = json_decode( $this->verifyTransaction( $tx_ref, $this->secret_key ) );

  if(gettype($txn) == "object"){
      if (!empty($txn->data) && $this->_is_successful( $txn->data->chargecode)) {
        $status =  $txn->data->status;
        $_products = wc_get_product($txn->data->meta[1]->metavalue);
        $main_products = $_products->get_file();

        $msg = $this->send_smtp_email( $txn->data->custemail, $txn->data->meta[0]->metavalue, $txn->data->meta[1]->metavalue, $main_products["file"] );
        echo '<div class="fan-display">
            <h3>You have successfully purchased this file '.$txn->data->meta[0]->metavalue.'.</h3>
          <h4>Please Click The Link Download File Below.</h4>
          <p>You can also check your email for the download link.</p>
          <div class="pay-md-12">
          <a href="'.$main_products["file"].'" class="pay pay-info pay-lg" role="button" download>Download</a><br/>'.
            $msg.'
        </div>';
      }
    }else{
      echo "";
    }

  }

    // Function to verify rave transaction

  private function verifyTransaction( $txref, $seckey ) {

    $url = $this->base_url . '/flwv3-pug/getpaidx/api/v2/verify';
    $args = array(
      'body' => array(
        'txref' => $txref,
        'SECKEY' => $seckey ),
      'sslverify' => false
    );

    $response = wp_remote_post( $url, $args );
    $result = wp_remote_retrieve_response_code( $response );

    if( $result === 200 ){
      return wp_remote_retrieve_body( $response );
    }

    return $result;

  }

  // Function to check the transaction status

  private function _is_successful( $data ) {

    return $data === '00' || $data === '0';

  }

  // SMTP Email function 

  public function send_smtp_email( $cust_email, $prod_id, $prod_name, $download_link ) {

    /*
    * Initialize phpmailer class
    */
    global $phpmailer;

    // (Re)create it, if it's gone missing
    if ( ! ( $phpmailer instanceof PHPMailer ) ) {
        require_once ABSPATH . WPINC . '/class-phpmailer.php';
        require_once ABSPATH . WPINC . '/class-smtp.php';
    }
    $phpmailer = new PHPMailer;

    $phpmailer->isSMTP();
    $phpmailer->Host       = $this->host;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = $this->port;
    $phpmailer->SMTPSecure = $this->smtpsecure;
    $phpmailer->Username   = $this->mail;
    $phpmailer->Password   = $this->password;

    $phpmailer->setFrom($this->mail, get_bloginfo( 'name' ));

    // Add a recipient
    $phpmailer->addAddress($cust_email);

    // Set email format to HTML
    $phpmailer->isHTML(true);

    // Email subject
    $phpmailer->Subject = 'Purchased product link from '.get_bloginfo( 'name' );

    // Email body content
    $mailContent = '<p>Hi There,</p>
        <p>You recent purchased the product with: </p>
        <p>Product ID: '.$prod_id.'</p>
        <p>Product Name: '.$prod_name.'.</p>
        <p>Here\'s the file download link: <a href="'.$download_link.'" download>Get File Here</a>.<br/>
        <p>Regards '.get_bloginfo( "name" ).'.</p>';
    $phpmailer->Body = $mailContent;

    if(!$phpmailer->send()){
        return 'Message could not be sent.';
        // echo 'Mailer Error: ' . $phpmailer->ErrorInfo;
    }else{
        return 'Message has been sent successfully';
    }
  }
}

?>