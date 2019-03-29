<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title> <?php echo ($action); ?> | <?php echo C('WRT_TITLE');?></title>

    <!-- bootstrap 3.0.2 -->
<link href="/cms/Public/AdminLTE/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
<!-- font Awesome -->
<link href="/cms/Public/AdminLTE/css/font-awesome.css" rel="stylesheet" type="text/css"/>
<!-- Ionicons -->
<link href="/cms/Public/AdminLTE/css/ionicons.min.css" rel="stylesheet" type="text/css"/>
<!-- Theme style -->
<link href="/cms/Public/AdminLTE/css/AdminLTE.css" rel="stylesheet" type="text/css"/>

<script type="text/javascript" src="/cms/Public/share/js/jquery-1.9.0.min.js"></script>
<script type="text/javascript" src="/cms/Public/share/js/jquery.validate.min.js"></script>
<script type="text/javascript" src="/cms/Public/share/js/laydate/laydate.js"></script>
<style>

    .form-group .control-label {

        float: left;
        width: 150px;
        padding-top: 5px;
        text-align: right;
    }

    .form-group .controls {

        margin-left: 170px;
    }

    .form-group .controls .radio {
        display: inline;
        padding-left: 0px;
        padding-right: 20px;
        vertical-align: baseline;
    }

    .form-group .controls .large {

        width: 60%;
    }
	
	.form-group .controls .middle {
        width: 30%;
    }
	
	.form-group .controls .small {
        width: 25%;
    }
	
    .form-group .controls select {

        width: 60%;
    }

    .form-group .controls .form-control {

        display: inline;
    }

    .form-group .controls .help-inline {
        padding-left: 10px;
        color: #595959;
    }

    .form-actions {

        margin-left: 170px;
    }

    .dropdown-checkboxes div {
        padding: 1px;
        padding-left: 10px;
    }

	
	
    .form-group-list .control-label {

        float: left;
        width: 150px;
        padding-top: 5px;
        text-align: right;
    }
	
    .form-group-list .control-label-small {

        float: left;
        width: 100px;
        padding-top: 5px;
        text-align: right;
    }
    .form-group-list .controls {

        margin-left: 20px;
    }

    .form-group-list .controls .radio {
        display: inline;
        padding-left: 0px;
        padding-right: 20px;
        vertical-align: baseline;
    }

    .form-group-list .controls .large {

        width: 60%;
    }
	
	.form-group-list .controls .middle {
        width: 30%;
    }
	
	.form-group-list .controls .small {
        width: 25%;
    }
	
    .form-group-list .controls select {

        width: 60%;
    }

    .form-group-list .controls .form-control {

        display: inline;
    }

    .form-group-list .controls .help-inline {
        padding-left: 10px;
        color: #595959;
    }

</style>

</head>
<body class="skin-blue">

<?php echo W('Common/header');?>



<div class="wrapper row-offcanvas row-offcanvas-left">
    <?php echo W('Common/sideMenu');?>


    <!-- Right side column. Contains the navbar and content of the page -->
    <aside class="right-side">
        <!-- Content Header (Page header) -->


        <section class="content-header">
            <h1>
                <?php echo ($action); ?>
                <small><?php echo (C("title")); ?></small>
            </h1>
            <ol class="breadcrumb">
                <li><a href="<?php echo ($module_url); ?>"><i class="fa fa-dashboard"></i> <?php echo ($module); ?></a></li>
                <li class="active"><?php echo ($action); ?></li>
            </ol>
        </section>

        <!-- Main content -->
        <section class="content">

            <div class="row">
                <div class="col-md-12">
                    <div class="box box-warning">
                        <!-- /.box-header -->
                        <div class="box-body">
                        	<!-- BEGIN EXAMPLE TABLE PORTLET-->       

                           <form action="<?php echo U('Admin/System/devupdateSave');?>"
                               class="form-horizontal" method="post" id="devupdate_form">
							
                               		
                            <div class="form-group-list">
                                <div class="controls">
								    <label class="control-label" >设备版本号：</label>
                                	<input type="text" class="form-control middle" name="version" value="<?php echo ($data['version']); ?>" readonly="readonly" size=50/>
                                    <a title='发布路由器最新版本'  
									    href="<?php echo U('Admin/System/devUpdateFile', array('dev_type' => 'router'));?>" target="dialog" mask="true">
									    <label style="color:blue;text-decoration: underline;cursor: pointer;">
									       【发布设备版本】
									    </label>
									</a>
                                </div>
								&nbsp;
								
								<!--
                                <div class="controls">
								    <label class="control-label">x86版本号：</label>
                                	<input type="text"class="form-control large" name="x86_version" value="<?php echo ($data['x86_version']); ?>" readonly="readonly" size=50/>
                                    <a title='发布x86最新版本'  
									    href="<?php echo U('Admin/System/devUpdateFile', array('dev_type' => 'x86'));?>" target="dialog" mask="true">
									    <label style="color:blue;text-decoration: underline;cursor: pointer;">
									       【发布x86版本】
									    </label>
									</a>
                                </div>
								&nbsp;
								-->
								
							    <div class="controls">
								    <label class="control-label">是否自动升级：</label>
				                    <label style='width:50px'><input <?php if(0==$data['auto_update_enable']){ echo 'checked';} ?> type="radio" name="auto_update_enable"  value="0" />否</label>
				                    <label style='width:50px'><input  <?php if( 1==$data['auto_update_enable']){ echo 'checked';} ?>  type="radio" name="auto_update_enable"   value="1"/>是</label>
                                </div>
								&nbsp;
								
								<div class="controls">
								    <label class="control-label">同时升级的设备数量：</label>
                                    <input type='text' class="form-control middle" value="<?php echo ($data['upgrade_limit']); ?>"
					               name='upgrade_limit' size=50 />台
                                </div>
								&nbsp;
								
								<div class="controls">
								    <label class="control-label">自动升级时间：</label>
   				                    <label class="control-label" style='width:20px'>从&nbsp;&nbsp;</label>
				                    <select class="control-label" name="start_hour" style='width:80px'>
				                    	<option value="">请选择</option>
				                    	<?php for($i=0;$i<=23;$i++){ ?>
				                    		<option  <?php if($i == $data['start_hour']) echo 'selected';?> value="<?php echo $i;?>"><?php echo $i;?></option> 
				                    	<?php } ?>
				                    </select>
				                    <label class="control-label" style='width:35px'>时 到 </label>
				                    <select class="control-label" name="end_hour" style='width:80px' >
				                    	<option value="">请选择</option>
				                    	<?php for($j=0;$j<=23;$j++){?>
				                    		<option  <?php if($j == $data['end_hour']) echo 'selected';?>   value="<?php echo $j;?>"><?php echo $j;?></option> 
				                    	<?php } ?>
				                    </select>
				                    <label class="control-label" style='width:10px'>时 </label>
                                </div>
								&nbsp;&nbsp;
								
															
                           	</div>			
							&nbsp;
                           	<div class="form-actions">
                                <button type="submit" class="btn btn-info submit">确定</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </aside>
    <!-- /.right-side -->
</div>
<!-- ./wrapper -->

<!-- Bootstrap -->
<script src="/cms/Public/AdminLTE/js/bootstrap.min.js" type="text/javascript"></script>

<!-- AdminLTE App -->
<script src="/cms/Public/AdminLTE/js/AdminLTE/app.js" type="text/javascript"></script>

<!-- iCheck -->
<script src="/cms/Public/AdminLTE/js/plugins/iCheck/icheck.min.js" type="text/javascript"></script>

<script>
    function a_confirm() {
        var msg = "您真的确定要这样做么吗？\n\n请确认！";
//        alert(msg);

        if (confirm(msg) == true) {
            return true;
        } else {
            return false;
        }
    }

</script>


<script>


    function refreshWarningNum(selector1) {
        var num = $(selector1).find("li").length;
        $("#warning_box_num").html(num);

    }



    $(document).ready(function () {


        $("input[type='checkbox'], input[type='radio']").iCheck({
            checkboxClass: 'icheckbox_minimal',
            radioClass: 'iradio_minimal'
        });


        $('#check-all').on('ifChecked', function (event) {
            $('input').iCheck('check');
        });
        $('#check-all').on('ifUnchecked', function (event) {
            $('input').iCheck('uncheck');
        });
    });
</script>


<script>
        $(document).ready(function () {
            var url = '<?php echo U("Admin/Index/checkTodo");?>';
            $.get(url, function (data) {
                if (data != 'none') {
                    $('#warning_box').append(data);
                    refreshWarningNum('#warning_box');
                } else {
                }
            });
        });
    </script>
<script type="text/javascript">
</body>
</html>