<?php

  // we include the initial configurations
  include("inc/config.php");

  // we sanitize POST/GET
  $_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
  $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

  // if there is a URL we try to convert all fiat prices into doge
  if (isset($_REQUEST["paw"])){

    // if the URL is not valid we show Sad Doge and redirect to the main page
    if (!filter_var($_REQUEST["paw"], FILTER_VALIDATE_URL)) {
      header('Location: '.$config["server_url"].'/?sad=1');
      die();
    }

    // we build the correct base path to emulate the website
    $url = explode("/",$_REQUEST["paw"]);
    $url = $url[0]."//".$url[2]."/";
?>
    <!-- we add a Meta Base to try to render correctly the website mirror-->
    <base href="<?php echo ($url);?>" />
<?php
    //We check if the shibe is using a Mobile browser to try to Dogefy mobile websites
    $m = 0;
    $isMob = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "mobile")); 
    if($isMob){ 
        $m = 1; 
    }

    // we try to get the website to dogefyit using our own proxy aka Dogexy :P
    $content = file_get_contents($config["server_url"].'/inc/dogexy.php?url='.$_REQUEST["paw"].'&m='.$m); // we get all HTML from the website

    // if nothing is returned we show a Sad Doge and ask the shibe to try anouther URL
    if (trim($content) == ""){
      header('Location: '.$config["server_url"].'/?sad=1');
      die();
    }
  
    // we show the website to start converting all fiat values into Dogecoin using Javascript below
    echo $content;
?>
<!-- because we don't know if the loaded website supports jQuery, we load it -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js" crossorigin="anonymous"></script>
    
<!-- we use the Dogefy Script to get all fiat prices and convert it into Dogecoin -->
<script type="text/javascript">

  // We try to disable all console errors because we are using a proxy
  (function () {
    const noop = () => {};
    const methods = ['log', 'debug', 'info', 'warn', 'error', 'assert', 'clear', 'count', 'dir', 'dirxml', 'group', 'groupCollapsed', 'groupEnd', 'table', 'time', 'timeEnd', 'timeLog', 'trace', 'profile', 'profileEnd', 'timeStamp', 'context'];

    if (!window.console) {
      window.console = {};
    }

    for (let method of methods) {
      if (!console[method]) {
        console[method] = noop;
      }
    }
  })();

  // to not load the same defined var we check if its alredy loaded wen converted
  if (typeof regex === 'undefined') {
    // we set your website fiat currency and fiat symbol to be automaticly replaced with the Dogecoin Value and Symbol
    var fiat_currency = "usd"; // this is to get the Dogecoin price from coingecko API
    //var fiat_currency_eur = "eur"; // this is to get the Dogecoin price from coingecko API
    var regex = /(?:\p{Sc}|EUR)\s?\d+(\.\d+)?|\d+(\.\d+)?\s?(?:\p{Sc}|EUR)/gu;
  }

$(document).ready(function() {
    
// we test if you have the Browser local Storage enable to store the Fiat Value of Dogecoin to calculate prices
function isLocalStorageAvailable(){
    var SuchTest = 'SuchTest';
    try {
        localStorage.setItem(SuchTest, SuchTest);
        localStorage.removeItem(SuchTest);
        return true;
    } catch(e) {
        return false;
    }
}
  // if Local Storage is not enable to bypass coingecko limitations to get the Dogecoin fiat value
  if(!isLocalStorageAvailable()){
      alert("Please enable Local Storage on your browser to be able to store the Dogecoin current value");
  }

  // we get the current fiat value of Dogecoin to be able to convert your website prices to Doge
  $.getJSON("https://api.coingecko.com/api/v3/simple/price?ids=dogecoin&vs_currencies=" + fiat_currency, function(data){
      localStorage.setItem('dogecoinValue', data["dogecoin"][fiat_currency]); // we store the value in local storage        
      //localStorage.setItem('dogecoinValueEur', data["dogecoin"][fiat_currency_eur]); // we store the value in local storage        
  });

  // until we cant get the local storage dogecoin value do to JQuery Storage, we reload the webpage
  const dogecoinValue = localStorage.getItem('dogecoinValue');
  if (dogecoinValue <= 0 ){
      setTimeout(
      function() 
          {
            location.reload();
          }, 120);
  };

  // function to fix Amazon website specificly (probably need more patches for more websites)
  // Function to replace money symbol and price
  function replaceMoneyAndPriceAmazon(dogecoinValue) {

    // Select all elements with class 'money-symbol' and replace the symbol
    $('.a-price-symbol').each(function () {
      $(this).text("Ð");
    });

    // Select all elements with class 'price' and replace the price
    $('.a-price-whole').each(function () {
      $(this).text((parseFloat($(this).text()) / dogecoinValue).toFixed(2));
    });
    
  }
  
  // we start and get all HTML of the proxy website loaded to dogefyit
  dogefy = $('body').html();
    
  $('body').filter(function() { // we search all elements on the page do Dogefyit
      return $(this).children().length;    
  }).each(function() {
      var soFiat = $(this).text(); // we get all text of element to find fiat money
      var matches = soFiat.match(regex); // we find fiat currency values in text   

      // if we find a currency value we try to convert into Dogecoin 
      if (matches) {
          for (var i = 0; i < matches.length; i++) {
              fiat = (matches[i].match(/[\d\.]+/)); // we get only the fiat value
              fiat_currency_symbol = (matches[i].match(/(?<=\d\s*)(?:\p{Sc}|EUR)|(?:\p{Sc}|EUR)(?=\s*\d)/gu)); // we get only the fiat currency symbol

              //we save all HTML to dogefy the webpage            
              dogefy = dogefy.replace(fiat_currency_symbol + fiat, 'Ð' + (fiat / dogecoinValue).toFixed(2));
              dogefy = dogefy.replace(fiat + fiat_currency_symbol, 'Ð' + (fiat / dogecoinValue).toFixed(2));

              dogefy = dogefy.replace(fiat_currency_symbol + ' ' + fiat, 'Ð' + (fiat / dogecoinValue).toFixed(2));
              dogefy = dogefy.replace(fiat + ' ' + fiat_currency_symbol, 'Ð' + (fiat / dogecoinValue).toFixed(2));            
              // now lets dogefy all website :P
                              
          }                       
      }        
  });

  // we show all Dogefy Website changed
  $('body').html(dogefy);

  replaceMoneyAndPriceAmazon(dogecoinValue); // this will run only on Amazon, probably will be more websites like this
  $.noConflict(); // it helps to stop some conflit errors wen loading a website using a proxy
    
});
</script>
<?php        
    }else{ // we ask a website to convert prices into Dogecoin
?>
<!DOCTYPE HTML>
<html>
<head>
  <title>Convert any website fiat prices into Dogecoin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">  
  <meta name="description" content="Convert any website fiat prices into Dogecoin">
  <meta name="author" content="All Dogecoin Community!">
  <meta name="generator" content="You!">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="Dogefyit any website">
  <meta name="twitter:site" content="<?php echo $config["server_url"]; ?>">
  <meta name="twitter:description" content="Convert any Website prices into Dogecoin">
  <meta name="twitter:image" content="<?php echo $config["server_url"]; ?>/img/dogefyit_preview1.png">
  <link href="<?php echo $config["server_url"]; ?>/img/doge_it.png" rel="icon" />
  <link href="//fonts.googleapis.com/css2?family=Comic+Neue&amp;display=swap" rel="stylesheet">
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>  
  <script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="css/doge.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  </head>
<body>
<div class="col" style="text-align:center">
  <img src="img/dogefyit.png" style="max-width:200px; position:relative; bottom:-80px">
  <form method="POST">  
    <div class="alert alert-light shibebox shadow">
      <label for="dogecoin" class="form-label"><b><i class="fa-solid fa-paw"></i> Add any website URL that have prices in fiat</b></label>
      <div class="input-group input-group-lg">
        <span class="input-group-text" id="basic-addon3"><i class="fa-solid fa-gears"></i></span>
        <input type="url" class="form-control" name="paw" id="paw" placeholder="https://ebay.com" aria-describedby="basic-addon3 basic-addon4">
        <button class="btn btn-secondary" type="submit" id="paw"><i class="fa-solid fa-paw"></i></button>
      </div>
      <div class="form-text" id="basic-addon4">Add a website URL above <i class="fa-solid fa-turn-up"></i></div>
    </div>
</form>
<span class="git"><a href="https://github.com/qlpqlp/dogefy" target="_blank"><i class="fa-brands fa-github"></i></a> <a href="/" alt="Convert any Website to Dogecoin"><i class="fa-solid fa-paw"></i> Convert any Website to Dogecoin</a></span>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
   // Thanks to https://twitter.com/patricklodder we can check if the DogeAddress is valid
    $(document).ready(function() {
      <?php
        // if we request a website and dosent to convert to Dogecoin and it dosent allow it we show a sad message
        if (isset($_GET["sad"])){
      ?>
                swal.fire({
                  icon: 'warning',
                  title: 'Much Sad!',
                  showConfirmButton: true,
                  confirmButtonColor: '#666666',
                  html:
                    '<img src="img/sad_doge.gif" style="border-radius:3rem"><br><br>' +
                    'Sorry Shibe, Try anouther URL to Dogefyit.',
                })
      <?php 
        };
      ?>    
    });  
</script>
</body>
</html> 
<?php
   }; 
?>