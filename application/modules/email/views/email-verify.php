<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Flik</title>
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
          <p style=""><strong>Hello,</strong></p>
          <p>Welcome to Flik</p>
          <br>        
      
          <p>Your Email needs to be verified, Please click on below Link to complete Verification Process.</p>  
<p><a href='<?php echo $verification_link ?>' style="color:#F00;background: #FDA034;-webkit-border-radius: 28;-moz-border-radius: 28;
border-radius: 28px;font-family: Arial;color: #242424;font-size: 14px;padding: 5px 10px 5px 10px;text-decoration: none;">Verify Your Email</a></p>		  
  
          <div style="border-top:1px solid #ccc;">
          <p>Thank you & regards,<br>
          <strong>Team Flik</strong></p>
        </td>
      </tr>

    
    </table>
  </div>
  </body>
</html>

