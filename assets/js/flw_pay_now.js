/* global location flw_payment_args jQuery*/
'use strict';

// variables

var raveLogo = 'https://res.cloudinary.com/dkbfehjxf/image/upload/v1511542310/Pasted_image_at_2017_11_09_04_50_PM_vc75kz.png'
var amount,
    cbUrl  = flw_pay_now_args.cb_url,
    payCountry,
    curr   = flw_pay_now_args.currency,
    currencyCode,
    productId,
    productName,
    desc   = flw_pay_now_args.desc,
    custEmail  = jQuery( '#cust-email' ),
    custNumber = jQuery( '#cust-number' ),
    logo   = flw_pay_now_args.logo || raveLogo,
    pb_key  = flw_pay_now_args.pb_key,
    title  = flw_pay_now_args.title,
    txref  = flw_pay_now_args.txnref,
    // paymentMethod  = flw_pay_now_args.payment_method,
    paymentStyle  = flw_pay_now_args.payment_style,
    loader = jQuery (".pay-modal-loader"),
    payModal = document.querySelector( '#pay-modal' ),
    spanOne = document.getElementsByClassName("close-modal")[0],
    payForm = jQuery( '#form' ),
    redirect_url,
    downloadDisplay = jQuery( '#hidden-download' ),
    proceed = jQuery( '#proceed' ),
    buyNow = jQuery( '.buy-now' );

  buyNow.click(function (evt) {
    evt.preventDefault();
    productId = jQuery(this).attr("data-the_product_id");
    productName = jQuery(this).attr("data-the_product_name");
    amount = jQuery(this).attr("data-the_amount");
    switch (curr) {

      case "KSh":
        currencyCode = "KES";
        payCountry = "KE";
        break;
      case "₵":
        currencyCode = "GHS";
        payCountry = "GH";
        break;
      case "$":
        currencyCode = "USD";
        payCountry = "NG";
        break;
      case "€":
        currencyCode = "EUR";
        payCountry = "NG";
        break;
      case "R":
        currencyCode = "ZAR";
        payCountry = "NG";
        break;
      case "£":
        currencyCode = "GBP";
        payCountry = "NG";
        break;
      case "UGX":
        currencyCode = "UGX";
        payCountry = "NG";
        break;
      default:
        currencyCode= "NGN";
        payCountry = "NG";
        break;
    }
    

    jQuery.fancybox.open({
      src  : '#hidden-content',
      type : 'inline',
      opts : {
        afterShow : function( instance, current ) {
          // console.info('done!');
        }
      }
    });
  });

  payForm.submit( function( evt ) {
    evt.preventDefault();

    jQuery.fancybox.close({
      src  : '#hidden-content',
      type : 'none',
      opts : {
        afterShow : function( instance, current ) {
          // console.info('hide!');
        }
      }
    });

    if(paymentStyle == 'inline'){
      processPayment();
    }
  });

// // function to process the rave payment

var processPayment = function() {
 var popup = getpaidSetup({
    amount: amount,
    country: payCountry,
    currency: currencyCode,
    custom_description: desc,
    custom_title: title,
    custom_logo: logo,
    customer_email: custEmail.val(),
    customer_phone: custNumber.val(),
    txref: txref,
    // payment_method: paymentMethod,
    PBFPubKey: pb_key,
    "meta": [{metaname: "Product Name", metavalue: productName}, {metaname: "Product ID", metavalue: productId}],
    onclose: function() {},
    callback: function(response){
      if ( response.tx.chargeResponseCode == "00" || response.tx.chargeResponseCode == "0" ) {

          handlePaymentResponse(response, payForm);
          // console.log(response);
      }else{
          alert(response.respmsg);
      }
      popup.close();
    }
  });

};

// Payment response handler

var handlePaymentResponse =  function(res, payForm) {
  var args  = {
    action: 'verify_payment'
  }

  jQuery.fancybox.open({
    src  : '#hidden-loader',
    type : 'inline',
    opts : {
      afterShow : function( instance, current ) {
        // console.info('done!');
      }
    }
  });

  var dataObj = Object.assign( {}, args, res.tx );
  jQuery
  .post( cbUrl, dataObj )
  .success( function(data) {

    jQuery.fancybox.close({
      src  : '#hidden-loader',
      type : 'none',
      opts : {
        afterShow : function( instance, current ) {
          // console.info('loader hide!');
        }
      }
    });

    jQuery.fancybox.open({
      src  : '#hidden-download',
      type : 'inline',
      opts : {
        afterShow : function( instance, current ) {
          // console.info('done!');
          downloadDisplay.html(data);
          payForm[0].reset();
        }
      }
    });
  })
}


var redirectTo = function( url ) {
  location.href = url;
};
