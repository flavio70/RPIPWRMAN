<?php
session_start();
if(!isset($_SESSION['RPirrigate_UserID']) && trim($_SERVER['REMOTE_ADDR'])!='127.0.0.1'){
  header('location: index.php?login');die();
}
if(!isset($_GET['id'])) die();

include 'config/config.php';
$db = new DB_CONN();
$location = $db->select1_setting('Location');
$userID = $_SESSION['RPirrigate_UserID'];
$lang = $db->select1_setting('Language');
include 'languages/'.$lang.'/'.$lang.'.php';

$currRowID = $_GET['id'];

//If row doesn't exist, go to Home
if(!$db->select1_row_exists($currRowID)){header('location: home.php'); die();}

$bannerMessage="";

if(isset($_POST['DeleteThisModule']) && $_POST['DeleteThisModule'] == 'YES'){
  $db->query_module_delete($currModuleID);
  header('location: home.php');die();
}

if(isset($_POST['Description'])){
  $db->query_module_description_update($currModuleID,nl2br($_POST['Description']));
  $bannerMessage = LANG_module_BANNER_DESCRIPTION;
}

if(isset($_POST['ManualSave']) && $_POST['ManualSave']=='true'){
  $val  = isset($_POST['ManualVAL']);
  $act  = isset($_POST['ManualACT']);
  $db->query_module_manual_update($currModuleID, $act, $val);
  $bannerMessage = LANG_module_BANNER_MANUAL;
  $pid = $db->select1_daemon_pid();
  //some php version use SIG_NAME, other SIGNAME
  if(defined('SIG_USR2'))
    posix_kill($pid , SIG_USR2);
  else
    posix_kill($pid , SIGUSR2);
  sleep(1); //let daemon reload and log
}

if (isset($_POST['Settings_Throughtput']) && isset($_POST['Settings_Name']) && isset($_POST['Settings_GPIO'])){
  $db->query_module_settings_update($currModuleID, $_POST['Settings_Name'], $_POST['Settings_GPIO'], $_POST['Settings_Throughtput']);
  $bannerMessage = LANG_module_BANNER_SETTINGS;
  $pid = $db->select1_daemon_pid();
  //Newer php version use SIG_NAME, newer SIGNAME
  if(defined('SIG_USR1'))
    posix_kill($pid , SIG_USR1);
  else
    posix_kill($pid , SIGUSR1);
  sleep(1); //let daemon reload and log
}

if(isset($_POST['NewEvent_startdate']) && isset($_POST['NewEvent_starttime']) && isset($_POST['NewEvent_weeks']) && isset($_POST['NewEvent_days']) && isset($_POST['NewEvent_liters'])){
  $weeks = $_POST['NewEvent_weeks']+0;
  $days = $_POST['NewEvent_days']+0;

  $minutes = ($_POST['NewEvent_weeks']+0)*10080 +
             ($_POST['NewEvent_days']+0)*1440;
  $db->query_module_event_add($currModuleID, $minutes, $_POST['NewEvent_starttime'], $_POST['NewEvent_startdate'], $_POST['NewEvent_liters']);
  $bannerMessage = LANG_module_BANNER_NEWEVENT;
  //ALSO SEND SIGUSR1 TO THE DAEMON TO MAKE IT RELOAD SETTINGS
  $pid = $db->select1_daemon_pid();
  //Newer php version use SIG_NAME, newer SIGNAME
  if(defined('SIG_USR1'))
    posix_kill($pid , SIG_USR1);
  else
    posix_kill($pid , SIGUSR1);
  sleep(1); //let daemon reload and log
}

if(isset($_POST['DeleteEvent'])){
  $db->query_event_delete($_POST['DeleteEvent']);
  $bannerMessage = LANG_module_BANNER_DELETEEVENT;
  //ALSO SEND SIGUSR1 TO THE DAEMON
  $pid = $db->select1_daemon_pid();
  //Newer php version use SIG_NAME, newer SIGNAME
  if(defined('SIG_USR1'))
    posix_kill($pid , SIG_USR1);
  else
    posix_kill($pid , SIGUSR1);
  sleep(1); //let daemon reload and log
}

$currRow = $db->select_rows($currRowID)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>RPiPWRManagement</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" href="misc/bootstrap.css" media="screen">
    <link rel="stylesheet" href="misc/bootswatch.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <script type="text/javascript">
      function HideShow(what){
        if($('#div'+what+'1').css("display")!="none"){
          $('#div'+what+'1').fadeTo('fast',0,function(){
            $('#div'+what+'1').css("display","none");
            $('#div'+what+'2').fadeTo('fast',1);
          });
        } else {
          $('#div'+what+'2').fadeTo('fast',0,function(){
            $('#div'+what+'2').css("display","none");
            $('#div'+what+'1').fadeTo('fast',1);
          });
        }
      }
      function validate_Settings(){
        //alert("Vedere come validare GPIO... Nome Solo caratteri,spazi e numeri, Portata solo numeri...")

        //creo un array di tutti i GPIO del RPI(PROBLEMA: GESTIRE B+,2 hanno piu GPIO)
        //poi creare un array di tutti quelli usati (vedere nel DB)
        //e vedere che non sia nel primo ma non nel secondo
      }
      function Manual_Change(){
        var ManActDB = <?php echo $currModule['ManualACT']?>==1;
        var ManValDB = <?php echo $currModule['ManualVAL']?>==1;
        var ManActCHK = $('#chkManAct').prop('checked');
        var ManValCHK = $('#chkManVal').prop('checked');

        $('#trManualVal').css('display',ManActCHK? 'table-row' : 'none');

        if(ManActCHK)
          $('#aManual').css('display',(ManActDB!=ManActCHK || ManValDB!=ManValCHK)? 'block' : 'none');
        else
          $('#aManual').css('display',(ManActDB!=ManActCHK)? 'block' : 'none');

        frmManual.ManualACT.value = ManActCHK;
        frmManual.ManualVAL.value = ManValCHK;
      }
      function Events_HideShow(from, to){
        $('#divEvents'+from).fadeTo('fast',0,function(){
          $('#divEvents'+from).css("display","none");
          $('#divEvents'+to).fadeTo('fast',1,function(){
            $('#divEvents'+to).css("display","block");
          });
        });

      }

      function Events_Validate1(){
        if($('#txtNewStartDate').val().length==0){
          alert("<?php echo LANG_module_ERR2;?>");
          return;
        }  
        if($('#txtNewStartHour').val().length==0){
          alert("<?php echo LANG_module_ERR3;?>");
          return;
        }  
        Events_HideShow(2,3);
      }

      function Events_Validate2(){
        if($('#txtNewLiters').val().length==0){
          alert("<?php echo LANG_module_ERR4;?>");
          return false;
        }  
        return confirm('<?php echo LANG_settings_RUSURE?>');
      }

      function Logs_ShowHide(){
        if($('#divRowLogs').css('display')!= "block" ){

          $('#divRow1').fadeTo('fast',0,function(){
            $('#divRow1').css('display','none');
          });
          $('#divRow2').fadeTo('fast',0,function(){
            $('#divRow2').css('display','none');
            $('#divRowLogs').css('display','block').fadeTo('fast',1);
          });
        } else {
          $('#divRowLogs').fadeTo('fast',0,function(){
            $('#divRowLogs').css('display','none');

            $('#divRow1').css('display','block').fadeTo('fast',1);
            $('#divRow2').css('display','block').fadeTo('fast',1);
          });
        }
      }

      function DeleteThisModule(){
        if(confirm("<?php echo LANG_module_DELETE_ALERT?>")){
          frmDelete.submit();
        }
      }
    </script>
    <style type="text/css">
      input[type="checkbox"] { 
        opacity: 0;
      }

      /* Normal Track */
      input[type="checkbox"].ios-switch + div {
        vertical-align: middle;
        width: 40px;  height: 20px;
        border: 1px solid rgba(0,0,0,.4);
        border-radius: 999px;
        background-color: rgba(0, 0, 0, 0.1);
        -webkit-transition-duration: .4s;
        -webkit-transition-property: background-color, box-shadow;
        box-shadow: inset 0 0 0 0px rgba(0,0,0,0.4);
        display:inline-block;
      }

      /* Checked Track (Blue) */
      input[type="checkbox"].ios-switch:checked + div {
        width: 40px;
        background-position: 0 0;
        background-color: #3b89ec;
        border: 1px solid #0e62cd;
        box-shadow: inset 0 0 0 10px rgba(59,137,259,1);
      }

      /* Normal Knob */
      input[type="checkbox"].ios-switch + div > div {
        float: left;
        width: 18px; height: 18px;
        border-radius: inherit;
        background: #ffffff;
        -webkit-transition-timing-function: cubic-bezier(.54,1.85,.5,1);
        -webkit-transition-duration: 0.4s;
        -webkit-transition-property: transform, background-color, box-shadow;
        -moz-transition-timing-function: cubic-bezier(.54,1.85,.5,1);
        -moz-transition-duration: 0.4s;
        -moz-transition-property: transform, background-color;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.3), 0px 0px 0 1px rgba(0, 0, 0, 0.4);
        pointer-events: none;
        margin-top: 1px;
        margin-left: 1px;
      }

      /* Checked Knob (Blue Style) */
      input[type="checkbox"].ios-switch:checked + div > div {
        -webkit-transform: translate3d(20px, 0, 0);
        -moz-transform: translate3d(20px, 0, 0);
        background-color: #ffffff;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.3), 0px 0px 0 1px rgba(8, 80, 172,1);
      }
    </style>
  </head>
  <body>
    <div class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <a href="#" class="navbar-brand"><?php echo $db->select1_username($userID);?>@RPi</a>
          <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>
        <div class="navbar-collapse collapse" id="navbar-main">
          <ul class="nav navbar-nav">
            <li><a href="home.php">Dashboard</a></li>
            <?php
            $qry = $db->select_modules();
            while ($row = $qry->fetch(PDO::FETCH_BOTH)){
              if ($row['RowId']==$currRowID)
                echo("<li><a href='module.php?id=".$row['ModuleID']."'>".$row['Name']."</a></li>\n");
            }
            echo("<li><a href='module-new.php?id=".$currRowID."'><i class='fa fa-plus'></i>&nbsp;&nbsp;");echo LANG_menu_ADDMODULE; echo("</a></li>\n");
            ?>
            
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a href="javascript:HideShow('Settings');"><i class="fa fa-cogs"></i>&nbsp;&nbsp;<?php echo LANG_row_EDIT; ?></a></li>
            <li><a href="javascript:DeleteThisModule();"><i class="fa fa-cogs"></i>&nbsp;&nbsp;<?php echo LANG_row_DELETE_BTN; ?></a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="container">
      <form name="frmDelete" action="" method="POST">
        <input type="hidden" name="DeleteThisModule" value="YES" />
      </form>
      <div class="bs-docs-section clearfix">
        <div class="row">
          <div class="col-lg-12">
            <div class="page-header">
              <?php if($bannerMessage!=""){ ?>
                <div class="bs-component">
                  <div class="alert alert-dismissible alert-success">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <h4>OK!</h4>
                    <p><?php echo $bannerMessage;?></p>
                  </div>
                </div>
              <?php } ?>
              <h1 id="dialogs"><?php echo $currRow['RowName'];?></h1>
            </div>
          </div>
         </div>
     

          

      </div>



<div class="row">
          <?php
            $qry = $db->select_modules();
            $i=0;
            while ($row = $qry->fetch(PDO::FETCH_BOTH)){ 
              if ($row['RowId']==$currRowID){
              $i++;

              //every three panels, go to new row
              if ($i>3 &&  $i%3==1)
                echo("</div><div class='row'>");
              ?>
              <div class="col-lg-4">
                <div class="bs-component">
                  
                  <div class="panel panel-primary">
                    <div class="panel-heading">
                      <h3 class="panel-title"><?php echo "<a href='module.php?id=".$row['ModuleID']."' align='left'>".$row['Name']."</a>" ?></h3>
                    </div>
                    <div class="panel-body">
                      <table>
                        <tr>
                          <td>
                            <?php
                            $filename = glob("mod_images/".$row['ModuleID'].".*");
                            if (count($filename)>0)
                              echo("<img src='".$filename[0]."' height='100' style='border-radius:10px'/>");
                            else
                              echo("<img src='misc/logo.png' height='100' style='border-radius:10px'/>");
                            ?>

                          <td style="padding-left:20px">
                            <b><?php echo $row['Name'] ?></b><br/>
                            <?php echo $row['Description'] ?>
                          </td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            <?php }} ?>
      </div>




      <footer><?php include 'misc/footer.php';?></footer>
    </div>


<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="misc/bootstrap.min.js"></script>
</body>
</html>
