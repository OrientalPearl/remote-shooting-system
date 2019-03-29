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
                <div class="col-md-10">
                    <div class="box box-warning">
                        <!-- /.box-header -->
                        <div class="box-body">
                        	<!-- BEGIN EXAMPLE TABLE PORTLET-->
                            <?php switch($action_name): case "editDevice": ?><form action="<?php echo U('Admin/DeviceManage/devEditHandle');?>"
										class="form-horizontal" id="newdevice" method="post"><?php break;?>
                                    
                                    <?php default: ?>
                                    <form action="<?php echo U('Admin/DeviceManage/devAddHandle');?>"
                                          class="form-horizontal" id="newdevice" method="post"><?php endswitch;?>
							
                            <div class="form-group" style=<?php echo ($user['type']==2?"display:none":""); ?>>
                            	<label class="control-label">管理员：</label>

                                <div class="controls">
								 <?php switch($action_name): case "addDevice": ?><select id="user_id" name="user_id" class="large form-control" tabindex="1" >
										<option value="">请选择管理员</option>
										<?php if(is_array($list_user)): $i = 0; $__LIST__ = $list_user;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["id"]); ?> <?php if($devinfo["user_id"] == $vo['id']): ?>selected<?php endif; ?>><?php echo ($vo["name"]); ?></option><?php endforeach; endif; endif; ?>
		                              </select><?php break;?>
			                            	
			                        <?php default: ?>                                	
										<select id="user_id" name="user_id" disabled="disabled" class="large form-control" tabindex="1" >
										<option value="">请选择管理员</option>
										<?php if(is_array($list_user)): $i = 0; $__LIST__ = $list_user;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["id"]); ?> <?php if($devinfo["user_id"] == $vo['id']): ?>selected<?php endif; ?>><?php echo ($vo["name"]); ?></option><?php endforeach; endif; endif; ?>
		                              </select><?php endswitch;?>
		                            
                                </div>
                           	</div> 
							
							<div class="form-group">
                            	<label class="control-label">序列号：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addDevice": ?><input id="serial" type="text" placeholder="设备序列号(必填)"" class="form-control large" name="serial"/><?php break;?>
			                            	
			                            <?php default: ?>
										<input id="serial" type="hidden" name="serial" value="<?php echo $devinfo['serial'];?>"/>										
										<input id="serial" type="text" disabled="disabled" class="form-control large" name="serial" value="<?php echo $devinfo['serial'];?>"/><?php endswitch;?>

                                </div>
                            </div>

							<div class="form-group" style=<?php echo ($user['type']==2?"display:none":""); ?>>
                            	<label class="control-label">自动升级 ：</label>

                           		<div class="controls">
									<select name="auto_upgrade" id="auto_upgrade" class="large form-control" tabindex="1">
	                                    <option value="">请选择是否自动升级</option>
		                                <option value="0" <?php if($devinfo["auto_upgrade"] == '0'): ?>selected<?php endif; ?> > 不自动升级 </option>
										<option value="1" <?php if($devinfo["auto_upgrade"] == '1'): ?>selected<?php endif; ?> > 自动升级 </option>
		                            </select>
							
                                </div>
							</div>
							
							<div class="form-group" style=<?php echo ($user['type']==2?"display:none":""); ?>>
                            	<label class="control-label">磁盘限制：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addDevice": ?><input id="disk_size" type="text" placeholder="磁盘限制，单位GB，0表示不限制"" class="form-control large" name="disk_size"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="disk_size" type="text" class="form-control large" name="disk_size" value="<?php echo $devinfo['disk_size'];?>"/><?php endswitch;?>

									<p style="display:inline">(GB)</p>
                                </div>
							</div>  

							<div class="form-group" style=<?php echo ($user['type']==2?"display:none":""); ?>>
                            	<label class="control-label">每日上传限制：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addDevice": ?><input id="upload_limit_day" type="text" placeholder="每日文件上传限制，单位MB，0表示不限制"" class="form-control large" name="upload_limit_day"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="upload_limit_day" type="text" class="form-control large" name="upload_limit_day" value="<?php echo $devinfo['upload_limit_day'];?>"/><?php endswitch;?>

									<p style="display:inline">(MB)</p>
                                </div>
							</div>  

							<div class="form-group" style=<?php echo ($user['type']==2?"display:none":""); ?>>
                            	<label class="control-label">带宽限制：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addDevice": ?><input id="bwlimit" type="text" placeholder="带宽限制，单位Mbps，0表示不限制"" class="form-control large" name="bwlimit"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="bwlimit" type="text" class="form-control large" name="bwlimit" value="<?php echo $devinfo['bwlimit'];?>"/><?php endswitch;?>
									<p style="display:inline">(Mbps)</p>
								</div>
								
								
							</div>  

							<div class="form-group">
                            	<label class="control-label">位置：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addDevice": ?><input id="area" type="text" placeholder="设备部署位置"" class="form-control large" name="area"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="area" type="text" class="form-control large" name="area" value="<?php echo $devinfo['area'];?>"/><?php endswitch;?>

                                </div>
                            </div>
							
							<div class="form-group">
                            	<label class="control-label">备注：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addDevice": ?><input id="remark" type="text" placeholder="备注"" class="form-control large" name="remark"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="remark" type="text" class="form-control large" name="remark" value="<?php echo $devinfo['remark'];?>"/><?php endswitch;?>

                                </div>
                            </div>                                                     	
							
							<div class="form-actions">

	                               	<?php switch($action_name): case "editDevice": ?><a href="javascript:;" class="btn btn-info" id="button_edit"><i
												class="icon-ok"></i>
											修改<?php break;?>
	                                    <?php case "addDevice": ?><a href="javascript:;" class="btn btn-info" id="button_add"><i
												class="icon-ok"></i>
											添加<?php break;?>
	                                	<?php default: ?>
											添加<?php endswitch;?>
                               	</a>
                                    
                                &nbsp;&nbsp;
                                    
                                <a href="<?php echo U('Admin/DeviceManage/devlist');?>" class="btn btn-info" id="button"><i
                                	class="icon-ok"></i>
                                    	返回
								</a>
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
<style type="text/css">
	em.error {
		background:url("/cms/Public/share/img/unchecked.gif") no-repeat 4px 4px;
		padding-left: 20px;
	}

	em.error { color: red; }
</style>
<script type="text/javascript">

$().ready(function() {
  $("#newdevice").validate({
    errorElement: "em",
	rules: {
		user_id: {
			required: true
		},
		serial: {
			required: true,
			rangelength:[4,4]
		},		
		auto_upgrade: {
			required: true
		},		
		disk_size: {
			required: true,
			digits: true
		},		
		upload_limit_day: {
			required: true,
			digits: true
		},		
		bwlimit: {
			required: true,
			digits: true
		},
		area: {
			maxlength: 128
		},
		remark: {
			maxlength: 128
		}
    },
    messages: {		
		user_id: {
			required: "请选择管理员"
		},		
		serial: {
			required: "请输入序列号",
			rangelength: "序列号长度必须为4个字符"
		},		
		auto_upgrade: {
			required:  "请选择自动升级类型"
		},		
		disk_size: {
			required: "请输入磁盘限制",
			digits: "请输入整数"
		},		
		upload_limit_day: {
			required: "请输入每日上传限制",
			digits: "请输入整数"
		},		
		bwlimit: {
			required: "请输入带宽限制",
			digits: "请输入整数"
		},
		area: {
			maxlength: "位置描述必需小于128个字符"
		},
		remark: {
			maxlength: "设备描述必需小于128个字符"
		}
    }
    });
	
	
	$("#button_add").click(function(){
		if($("#newdevice").valid()){
			$("#newdevice").submit();
		}
	});	
	
	$("#button_edit").click(function(){
		if($("#newdevice").valid()){
			$("#newdevice").submit();
		}
	});
});

</script>
</body>
</html>