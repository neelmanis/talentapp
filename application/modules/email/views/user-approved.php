<!doctype html>
<html>
<head>
  <meta charset="utf-8">
 <title>Setmycoach.com</title>
</head>
<body>
  <div style="margin:0 auto; max-width:700px; width:700px; position:relative; line-height:18px;">
    <table  cellpadding="0" cellspacing="0" style="font-family:Arial, Helvetica, sans-serif; color:#292b29; width:100%; border:1px solid #619855;border-bottom:4px solid #0aa360; font-size:14px; background:#fdfdfd;">
      <tr>
        <td colspan="2" style="background:#ba4c59; height:20px;"></td>
      </tr>

     
      <tr>
        <td style="padding:30px 30px 30px 30px;">
          <h2 style="font-weight:normal; color:#ba4c59;">Welcome <strong><?php echo $name; ?>, </strong></h2>
          <p style=""><strong>Dear Sir / Madam,</strong></p>
          <p>Welcome to Setmycoach.com</p>
          <br>        
      
          <p>Your account is approved. Kindly click here to <a href="<?php echo base_url(); ?>login">login</a>  using below credentials</p> 
          
          <p>Username : <?php echo $username; ?></p>
          <p>Password :  <?php echo $password ?>	</p>  
  
          <div style="border-top:1px solid #ccc;">
          <p>Thank you & regards,<br>
          <strong>Team Setmycoach.com</strong></p>
        </td>
      </tr>

    
    </table>
  </div>
</body>
</html>

