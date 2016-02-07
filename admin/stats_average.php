<?php


  require('includes/application_top.php');
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_STATS_AVERAGE);

  $res=tep_db_query("select date_format(date_sub(now(), interval 1 month), '%Y-%m-%d') lastmonth, date_format(date_sub(now(), interval 7 day), '%Y-%m-%d') lastweek, date_format(date_sub(now(), interval 1 day), '%Y-%m-%d') yesterday, date_format(date_sub(now(), interval 0 day), '%Y-%m-%d') today");
  $row=tep_db_fetch_array($res);

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="popcalendar.js"></script>
</head>
<body style="background:transparent; margin:0;">

<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    
    <td width="100%" valign="top" colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
       <tr>
          <td> 
            <form method="post" action="stats_averagesales.php" name="frm">
              <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr> 
                  <td width="11%" align="right" class="pageHeading"><?php echo STATS_AVERAGE_TITLE ?></td>
                  <td width="1%">&nbsp;</td>
                  <td width="88%">&nbsp;</td>
                </tr>
                <tr>
                  <td width="11%" align="right">&nbsp;</td>
                  <td width="1%">&nbsp;</td>
                  <td width="88%">&nbsp;</td>
                </tr>
                <tr>
                  <td width="11%" align="right" class="dataTableContent"><?php echo STATS_AVERAGE_START_DATE ?></td>
                  <td width="1%">&nbsp;</td>
                  <td width="88%"> 
                    <input type="text" name="start_date" class="dataTableContent" onClick="popUpCalendar(this,this,'yyyy-mm-dd');" value="<?php echo $row['lastweek'];?>" >
                  </td>
                </tr>
                <tr> 
                  <td width="11%" align="right" class="dataTableContent"><?php echo STATS_AVERAGE_END_DATE ?></td>
                  <td width="1%">&nbsp;</td>
                  <td width="88%"> 
                    <input type="text" name="end_date" class="dataTableContent" onClick="popUpCalendar(this,this,'yyyy-mm-dd');" value="<?php echo $row['today'];?>" >
                  </td>
                </tr>
                <tr>
                  <td width="11%">&nbsp;</td>
                  <td width="1%">&nbsp;</td>
                  <td width="88%">&nbsp;</td>
                </tr>
                <tr>
                  <td width="11%">&nbsp;</td>
                  <td width="1%">&nbsp;</td>
                  <td width="88%">
                    <input type="submit" name="submit" value="<?php echo STATS_AVERAGE_BUTTON_REPORT ?>" class="dataTableContent">
                  </td>
                </tr>
                <tr>
                  <td width="11%">&nbsp;</td>
                  <td width="1%">&nbsp;</td>
                  <td width="88%">&nbsp;</td>
                </tr>
              </table>
            </form>
          </td>
      </tr>
    </table></td>

  </tr>
</table>

</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>