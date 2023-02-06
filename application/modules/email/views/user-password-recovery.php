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
          
        <p>Hello <?php echo $name; ?>,</p>
          <p>Following are your password details.</p>
          <br>
       
          <p><strong>PASSWORD : </strong> <strong><?php echo $password; ?></strong></p>
          <br>
          <p><a href="<?php echo base_url(); ?>login">click here</a> to login.</p>

          <div style="border-top:1px solid #ccc;">
          <p>Thank you & regards,<br>
          <strong>Team Setmycoach.com</strong></p>
          
        </td>
      </tr>

      <tr style="background:#72a268; color:#fff;">
    
    </tr>
    </table>
  </div>
  </body>
</html>


