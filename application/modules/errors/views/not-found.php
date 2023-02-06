<div class="main_wrapper">
  <div class="left_wpr"> 
    <h1>404</h1> 
    <h2>Page not found</h2>
    <h4>Looks like someting went completely wrong</h4>  
  </div>
  <div class="right_wpr">   
    <b>Here are some usefull links</b>
    <ul class="list_style">
       <?php if(isset($isAdmin) && $isAdmin == "YES"){ ?> 
        <li><a href="<?php echo base_url(); ?>admin/dashboard">Dashboard</a></li>
      <?php }else{ ?>
        <li><a href="<?php echo base_url(); ?>">Home</a></li>
      <?php } ?>
    </ul>
  </div>
</div>