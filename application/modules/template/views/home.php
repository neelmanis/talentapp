<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<?php if(isset($pageTitle) && !empty($pageTitle)){ ?>
<title><?php echo $pageTitle;?></title>
<?php }else{ ?>
<title>Welcome to Talent App</title>
<?php } ?>
</head>
<body>
<?php require_once('include/header.php');
 $path = $module. '/' . $viewFile; 
 echo $this->load->view($path); 
 require_once('include/footer.php');
 ?>
<script>
var CI_ROOT = "<?php echo base_url(); ?>";        
Number.prototype.zeroPadding = function()
{
  var ret = "" + this.valueOf();
  return ret.length == 1 ? "0" + ret : ret;
};
</script>
<?php if(isset($scriptFile) && !empty($scriptFile)){ ?>
<script src="<?php echo base_url(); ?>public/custom/js/<?php echo $scriptFile; ?>.js"></script>
<?php } ?>
</body>
</html>


