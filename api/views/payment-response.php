
<!DOCTYPE html>
<html>
<head>
  <title>HalaGram</title>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta property="type" content="website" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>

  <script type="text/javascript"> var REDIRECTION = "<?php echo $redirect; ?>"; </script>

	<style>
    .loader{
      background: #fff;
      position: fixed;
      width: 100%;
      height: 100%;
      z-index: 999999;
      left: 0;
      top: 0;
    }

    .loginPanel, .box-shadow.ratingBox{
      position: relative;
    }

    .loginPanel .loader{
      position: absolute;
      -webkit-border-radius: 50px;
      border-radius: 50px;
    }

    .loader:before,
    .loader:after {
      -webkit-border-radius: 50%;
      border-radius: 50%;
    }

    .loader {
      color: #521751;
    }

    .loader:before{
      position: absolute;
      content: '';
      width: 50px;
      height: 50px;
      border-top: 5px solid #ea5297;
      border-left: 5px solid #ea5297;
      border-bottom: 5px solid #0bbbef;
      border-right: 5px solid #0bbbef;
      top: 50%;
      left: 50%;
      margin-left: -25px;
      margin-top: -25px;
      -webkit-border-radius: 50%;
      border-radius: 50%;
      display: block;
      box-sizing: border-box;
    }

    .loader:before {
      background: #fff;
      -ms-transform-origin: 50% 50%; /* IE 9 */
      -webkit-transform-origin: 50% 50%; 
      transform-origin: 50% 50%;
      -webkit-animation: load2 2s infinite ease;
      animation: load2 2s infinite ease;
    }

    @-webkit-keyframes load2 {
      0% {
        -webkit-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -webkit-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }

    @keyframes load2 {
      0% {
        -webkit-transform: rotate(0deg);
        -ms-transform: rotate(0deg);
        transform: rotate(0deg);
      }
      100% {
        -webkit-transform: rotate(360deg);
        -ms-transform: rotate(360deg);
        transform: rotate(360deg);
      }
    }
	</style>

</head>
<body>

  <div class="loader"></div>

  <script src="<?php echo  base_url();?>assets/admin/node_modules/jquery/jquery-3.2.1.min.js"></script>
  <script type="text/javascript">
    $(document).ready(function(){
      window.location.href = REDIRECTION
    });
  </script>
</body>
</html>