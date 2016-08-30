<?php
session_start();
if(!isset($_SESSION['RPirrigate_UserID']) && trim($_SERVER['REMOTE_ADDR'])!='127.0.0.1'){
  header('location: index.php?login');die();
}
include 'config/config.php';
$db = new DB_CONN();
$userID = $_SESSION['RPirrigate_UserID'];
$lang = $db->select1_setting('Language');
include 'languages/'.$lang.'/'.$lang.'.php';

$bannerMessage="";
if(isset($_POST['name'])&&isset($_POST['description']) ){
  $db->query_row_add($_POST['name'],$_POST['description'],$_FILES['image']);
  $bannerMessage = LANG_rownew_BANNER;
  //*** ALSO SEND SIGUSR1 TO THE DAEMON TO MAKE IT RELOAD SETTINGS!!
  $pid = $db->select1_daemon_pid();
  //Newer php version use SIG_NAME, newer SIGNAME
  if(defined('SIG_USR1'))
    posix_kill($pid , SIG_USR1);
  else
    posix_kill($pid , SIGUSR1);
  sleep(1); //let daemon reload and log
}
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
    <script src="//crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/md5.js"></script>
    <script src="//crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/sha1.js"></script>
    <script type="text/javascript">
    function Step1_2(){
      if($('#txtName').val().length==0 ){
        alert("<?php echo LANG_rownew_ERR1; ?>");
        return;
      }
      if($('#txtDescription').val().length==0){
        alert("<?php echo LANG_rownew_ERR2; ?>");
        return;
      }
      $('#fsStep1').fadeTo('fast',0,function(){
        $('#fsStep1').css('display','none');
        $('#fsStep2').css('display','block').fadeTo('fast',1);
      });


    }

   function Step2_1(){
      $('#fsStep2').fadeTo('fast',0,function(){
        $('#fsStep2').css('display','none');
        $('#fsStep1').css('display','block').fadeTo('fast',1);
      });
    }

    
    function Step2_end(){

      frmNewRow.submit();
    }
    </script>
  </head>
  <body>
    <div class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <a href="#" class="navbar-brand"><?php echo $db->select1_username($userID);?>@RPirrigate</a>
          <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>
        <div class="navbar-collapse collapse" id="navbar-main">
          <ul class="nav navbar-nav">
            <li>
              <a href="home.php">Dashboard</a>
            </li>
            <?php
            $qry = $db->select_rows();
            while ($row = $qry->fetch(PDO::FETCH_BOTH))
              echo("<li><a href='row.php?id=".$row['RowID']."'>".$row['RowName']."</a></li>\n");
            ?>
            <li class="active"><a href="row-new.php"><i class="fa fa-plus"></i>&nbsp;&nbsp;<?php echo LANG_menu_ADDROW; ?></a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a href="settings.php"><i class="fa fa-cogs"></i>&nbsp;&nbsp;<?php echo LANG_menu_SETTINGS; ?></a></li>
          </ul>

        </div>
      </div>
    </div>
    <div class="container">
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
              <h1 id="dialogs"><?php echo LANG_menu_ADDROW; ?></h1>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-2"></div>
          <div class="col-lg-8">
            <div class="bs-component">
              
              <div class="panel panel-info" style="height:400px">
                <div class="panel-heading">
                  <h3 class="panel-title"><?php echo LANG_menu_ADDROW; ?>
                  </h3>
                </div>
                <div class="panel-body">
                  <form name="frmNewRow" class="form-horizontal" method="post" action="" style="width:50%;margin:auto" 
                        enctype="multipart/form-data">
                    <fieldset id="fsStep1">
                      <div class="form-group">
                        <p style="font-weight:bold;text-align:center;"><?php echo LANG_row_NAME ?></p>
                        <div class="col-lg-10" style="width:100%">
                          <input id="txtName" name="name" type="text" class="form-control" >
                        </div>
                      </div>
                      <div class="form-group">
                        <p style="font-weight:bold;text-align:center;"><?php echo LANG_row_DESCRIPTION ?></p>
                        <div class="col-lg-10" style="width:100%" >
                          <textarea id="txtDescription" name="description" rows="7" class="form-control"></textarea>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="col-lg-10" style="text-align:center;width:100%">
                          <a href="javascript:Step1_2();"
                            class="btn btn-primary input-sm" 
                            style="padding-top:4px;margin-top:15px;">
                              <?php echo LANG_settings_NEXT; ?></a>
                      </div>
                    </fieldset>
                    <fieldset id="fsStep2" style="display:none">
                    
                      <div class="form-group">
                        <p style="font-weight:bold;text-align:center;"><?php echo LANG_rownew_IMAGEFILE ?></p>
                        <div class="col-lg-10" style="width:80%;float:none;margin:auto" >
                          <input type="file" name="image" class="form-control" accept="image/*">
                          <?php echo LANG_rownew_IMAGE_LOGODEFAULT ?>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="col-lg-10" style="text-align:center;width:100%">
                          <a href="javascript:Step2_1();" 
                            class="btn btn-warning input-sm" 
                            style="padding-top:4px;margin-top:18px">
                              <?php echo LANG_settings_BACK; ?></a>
                        &nbsp;&nbsp;&nbsp;
                        <a href="javascript:Step2_end();" 
                            class="btn btn-primary input-sm" 
                            style="padding-top:4px;margin-top:18px">
                              <?php echo LANG_settings_CONFIRM; ?></a>
                      </div>
                    </fieldset>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      <footer><?php include 'misc/footer.php';?></footer>
    </div>
<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="misc/bootstrap.min.js"></script>
</body>
</html>
