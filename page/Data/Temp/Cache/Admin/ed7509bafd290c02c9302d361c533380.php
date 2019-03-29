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
                            <?php switch($action_name): case "editTask": ?><form action="<?php echo U('Admin/ShootingTask/taskEditHandle');?>"
                                    	class="form-horizontal" id="newtask" method="post"><?php break;?>
                                    
                                    <?php default: ?>
                                    <form action="<?php echo U('Admin/ShootingTask/taskAddHandle');?>"
                                          class="form-horizontal" id="newtask" method="post"><?php endswitch;?>
  
  
							<input type="hidden" name="id" value="<?php echo $info['id'];?>"/>
							
                            <div class="form-group">
                            	<label class="control-label">序列号 ：</label>
								   <?php switch($action_name): case "addTask": ?><div class="controls">
												<select id="serial" name="serial" <!--</select>onclick="change_serial()"--> class="large form-control" tabindex="1" >
													<option value="">请选择设备序列号 </option>
													<?php if(is_array($list_device)): $i = 0; $__LIST__ = $list_device;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["serial"]); ?> <?php if($info["serial"] == $vo['serial']): ?>selected<?php endif; ?>><?php echo ($vo["serial"]); ?></option><?php endforeach; endif; endif; ?>
												</select>
											</div><?php break;?>
			                            	
			                            <?php default: ?>                   
											<input type="hidden" name="serial" value="<?php echo $info['serial'];?>"/>										
											<div class="controls">
												<select id="serial" name="serial" <!--onclick="change_serial()"--> disabled="disabled" class="large form-control" tabindex="1" >
													<option value="">请选择设备序列号 </option>
													<?php if(is_array($list_device)): $i = 0; $__LIST__ = $list_device;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["serial"]); ?> <?php if($info["serial"] == $vo['serial']): ?>selected<?php endif; ?>><?php echo ($vo["serial"]); ?></option><?php endforeach; endif; endif; ?>
												</select>
											</div><?php endswitch;?>
									

                           	</div> 
							
							<!--
							
                            <div class="form-group" style=<?php echo ($_SESSION[C('USER_AUTH_INFO')]['type']!=1?"display:none":""); ?>>
                            	<label class="control-label">管理员：</label>
								   <?php switch($action_name): case "addTask": ?><div class="controls">
												<select id="user_id" name="user_id" class="large form-control" tabindex="1" >
													<option value="">请选择管理员</option>
													<?php if(is_array($list_user)): $i = 0; $__LIST__ = $list_user;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["id"]); ?> <?php if($info["user_id"] == $vo['id']): ?>selected<?php endif; ?>><?php echo ($vo["name"]); ?></option><?php endforeach; endif; endif; ?>
												</select>
											</div><?php break;?>
			                            	
			                            <?php default: ?>                                	
											<div class="controls">
												<select id="user_id" name="user_id" disabled="disabled" class="large form-control" tabindex="1" >
													<option value="">请选择管理员</option>
													<?php if(is_array($list_user)): $i = 0; $__LIST__ = $list_user;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["id"]); ?> <?php if($info["user_id"] == $vo['id']): ?>selected<?php endif; ?>><?php echo ($vo["name"]); ?></option><?php endforeach; endif; endif; ?>
												</select>
											</div><?php endswitch;?>

                           	</div> 
							-->
							
							
							<div class="form-group">
                            	<label class="control-label">任务类型 ：</label>

                           		<div class="controls">
									<select name="type" id="type"  class="large form-control" tabindex="1">
	                                    <option value="">选请择任务类型</option>
		                                <option value="0" <?php if($info["type"] == '0'): ?>selected<?php endif; ?> > 自动任务 </option>
										<option value="1" <?php if($info["type"] == '1'): ?>selected<?php endif; ?> > 手动任务 </option>
		                            </select>
							
                                </div>
                            </div>
											
							<div class="form-group" id="aperture_outer">
                            	<label class="control-label">光圈 ：</label>

                           		<div class="controls">
									 <select id="aperture" name="aperture" class="large form-control" tabindex="1" >
										<option value="">请选择光圈 </option>
										<!--
										<?php if(is_array($list_aperture)): $i = 0; $__LIST__ = $list_aperture;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["aperture"]); ?> <?php if($info["aperture"] == $vo['aperture']): ?>selected<?php endif; ?>><?php echo ($vo["aperture"]); ?></option><?php endforeach; endif; endif; ?>
										-->
		                              </select>
								<!--
								   <?php switch($action_name): case "addTask": ?><input id="aperture" type="text" placeholder="光圈" class="form-control large" name="aperture"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="aperture" type="text" class="form-control large" name="aperture" value="<?php echo $info['aperture'];?>"/><?php endswitch;?>
								-->
                                </div>
                            </div>
							<div class="form-group" id="shutter_outer">
                            	<label class="control-label">快门 ：</label>

                           		<div class="controls">									 
								
									<select id="shutter" name="shutter" class="large form-control" tabindex="1" >
										<option value="">请选择快门 </option>
										<!--
										<?php if(is_array($list_shutter)): $i = 0; $__LIST__ = $list_shutter;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["shutter"]); ?> <?php if($info["shutter"] == $vo['shutter']): ?>selected<?php endif; ?>><?php echo ($vo["shutter"]); ?></option><?php endforeach; endif; endif; ?>
										-->
		                              </select>
									<!--
								   <?php switch($action_name): case "addTask": ?><input id="shutter" type="text" placeholder="快门" class="form-control large" name="shutter"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="shutter" type="text" class="form-control large" name="shutter" value="<?php echo $info['shutter'];?>"/><?php endswitch;?>
									-->
                                </div>
                            </div>
							<div class="form-group" id="iso_outer">
                            	<label class="control-label">ISO ：</label>

                           		<div class="controls">									
								
									<select id="iso" name="iso" class="large form-control" tabindex="1" >
										<option value="">请选择ISO </option>
										<!--
										<?php if(is_array($list_iso)): $i = 0; $__LIST__ = $list_iso;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["iso"]); ?> <?php if($info["iso"] == $vo['iso']): ?>selected<?php endif; ?>><?php echo ($vo["iso"]); ?></option><?php endforeach; endif; endif; ?>
										-->
		                              </select>
									<!--
								   <?php switch($action_name): case "addTask": ?><input id="iso" type="text" placeholder="ISO" class="form-control large" name="iso"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="iso" type="text" class="form-control large" name="iso" value="<?php echo $info['iso'];?>"/><?php endswitch;?>
									-->
                                </div>
                            </div>
							<div class="form-group">
                            	<label class="control-label">拍摄时间 ：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addTask": ?><input id="shooting_time" type="text" placeholder="拍摄时间" class="form-control large" name="shooting_time"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="shooting_time" type="text" class="form-control large" name="shooting_time" value="<?php echo $info['shooting_time'];?>"/><?php endswitch;?>
									
                                </div>
                            </div>							
							<div class="form-group">
                            	<label class="control-label">拍摄张数 ：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addTask": ?><input id="shooting_number" type="text" placeholder="拍摄张数" class="form-control large" name="shooting_number"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="shooting_number" type="text" class="form-control large" name="shooting_number" value="<?php echo $info['shooting_number'];?>"/><?php endswitch;?>

                                </div>
                            </div>							
							<div class="form-group">
                            	<label class="control-label">拍摄间隔 ：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addTask": ?><input id="shooting_interval" type="text" placeholder="拍摄间隔" class="form-control large" name="shooting_interval"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input id="shooting_interval" type="text" class="form-control large" name="shooting_interval" value="<?php echo $info['shooting_interval'];?>"/><?php endswitch;?>

                                </div>
                            </div>
							
                           	<div class="form-actions">

	                               	<?php switch($action_name): case "editTask": ?><a href="javascript:;" class="btn btn-info" id="button_edit"><i
												class="icon-ok"></i>
											修改<?php break;?>
	                                    <?php case "addTask": ?><a href="javascript:;" class="btn btn-info" id="button_add"><i
												class="icon-ok"></i>
											添加<?php break;?>
	                                	<?php default: ?>
											添加<?php endswitch;?>
                               	</a>
                                    
                                &nbsp;&nbsp;
                                    
                                <a href="<?php echo U('Admin/ShootingTask/task');?>" class="btn btn-info" id="button"><i
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




<style type="text/css">
	em.error {
		background:url("/cms/Public/share/img/unchecked.gif") no-repeat 4px 4px;
		padding-left: 20px;
	}

	em.error { color: red; }
</style>
<script type="text/javascript">

$('#serial').change(function(){ 
	var i;
	//var options=$("#serial option:selected");
	var serial = $(this).children('option:selected').val();

	//console.log(options);

	//console.log(serial);
	
	if (!serial){
		$("#aperture").empty();
		var b = '<option value="">请选择光圈</option>';
		$("#aperture").append(b);
		
		$("#shutter").empty();
		var b = '<option value="">请选择快门</option>';
		$("#shutter").append(b);
		
		$("#iso").empty();
		var b = '<option value="">请选择ISO</option>';
		$("#iso").append(b);
		return;
	}
	
	
	var json_aperture_str = '<?php echo ($list_json_aperture); ?>';
	var aperture_str = eval('(' + json_aperture_str + ')')[serial];
	var aperture_arr = aperture_str.split(',')
	var current_aperture = '<?php echo ($info["aperture"]); ?>'
	
	$("#aperture").empty();
	
	var b = '<option value="">请选择光圈</option>';
	for (i = 0;i < aperture_arr.length; i++){
		if (aperture_arr[i] == current_aperture)
			b+="<option selected value='"+aperture_arr[i]+"'>"+aperture_arr[i]+"</option>";
		else
			b+="<option value='"+aperture_arr[i]+"'>"+aperture_arr[i]+"</option>";
	}

    $("#aperture").append(b);
	
	
	
	
	
	
	var json_shutter_str = '<?php echo ($list_json_shutter); ?>';
	var shutter_str = eval('(' + json_shutter_str + ')')[serial];
	var shutter_arr = shutter_str.split(',')
	var current_shutter = '<?php echo ($info["shutter"]); ?>'
	
	$("#shutter").empty();
	
	var b = '<option value="">请选择快门</option>';
	for (i = 0;i < shutter_arr.length; i++){
		if (shutter_arr[i] == current_shutter)
			b+="<option selected value='"+shutter_arr[i]+"'>"+shutter_arr[i]+"</option>";
		else
			b+="<option value='"+shutter_arr[i]+"'>"+shutter_arr[i]+"</option>";
	}

    $("#shutter").append(b);
	
	
	
	
	
	
	var json_iso_str = '<?php echo ($list_json_iso); ?>';
	var iso_str = eval('(' + json_iso_str + ')')[serial];
	var iso_arr = iso_str.split(',')
	var current_iso = '<?php echo ($info["iso"]); ?>'
	
	$("#iso").empty();
	
	var b = '<option value="">请选择ISO</option>';
	for (i = 0;i < iso_arr.length; i++){
		if (iso_arr[i] == current_iso)
			b+="<option selected value='"+iso_arr[i]+"'>"+iso_arr[i]+"</option>";
		else
			b+="<option value='"+iso_arr[i]+"'>"+iso_arr[i]+"</option>";
	}

    $("#iso").append(b);
	return;
});
	
function change_serial(){
	var i;
	var options=$("#serial option:selected");
	var serial = options.val();

	console.log(options);

	console.log(serial);
	
	if (!serial){
		$("#aperture").empty();
		var b = '<option value="">请选择光圈</option>';
		$("#aperture").append(b);
		
		$("#shutter").empty();
		var b = '<option value="">请选择快门</option>';
		$("#shutter").append(b);
		
		$("#iso").empty();
		var b = '<option value="">请选择ISO</option>';
		$("#iso").append(b);
		return;
	}
	
	
	var json_aperture_str = '<?php echo ($list_json_aperture); ?>';
	var aperture_str = eval('(' + json_aperture_str + ')')[serial];
	var aperture_arr = aperture_str.split(',')
	var current_aperture = '<?php echo ($info["aperture"]); ?>'
	
	$("#aperture").empty();
	
	var b = '<option value="">请选择光圈</option>';
	for (i = 0;i < aperture_arr.length; i++){
		if (aperture_arr[i] == current_aperture)
			b+="<option selected value='"+aperture_arr[i]+"'>"+aperture_arr[i]+"</option>";
		else
			b+="<option value='"+aperture_arr[i]+"'>"+aperture_arr[i]+"</option>";
	}

    $("#aperture").append(b);
	
	
	
	
	
	
	var json_shutter_str = '<?php echo ($list_json_shutter); ?>';
	var shutter_str = eval('(' + json_shutter_str + ')')[serial];
	var shutter_arr = shutter_str.split(',')
	var current_shutter = '<?php echo ($info["shutter"]); ?>'
	
	$("#shutter").empty();
	
	var b = '<option value="">请选择快门</option>';
	for (i = 0;i < shutter_arr.length; i++){
		if (shutter_arr[i] == current_shutter)
			b+="<option selected value='"+shutter_arr[i]+"'>"+shutter_arr[i]+"</option>";
		else
			b+="<option value='"+shutter_arr[i]+"'>"+shutter_arr[i]+"</option>";
	}

    $("#shutter").append(b);
	
	
	
	
	
	
	var json_iso_str = '<?php echo ($list_json_iso); ?>';
	var iso_str = eval('(' + json_iso_str + ')')[serial];
	var iso_arr = iso_str.split(',')
	var current_iso = '<?php echo ($info["iso"]); ?>'
	
	$("#iso").empty();
	
	var b = '<option value="">请选择ISO</option>';
	for (i = 0;i < iso_arr.length; i++){
		if (iso_arr[i] == current_iso)
			b+="<option selected value='"+iso_arr[i]+"'>"+iso_arr[i]+"</option>";
		else
			b+="<option value='"+iso_arr[i]+"'>"+iso_arr[i]+"</option>";
	}

    $("#iso").append(b);
	return;
	
}

$().ready(function() {

  $("#newtask").validate({
    errorElement: "em",
	rules: {
		serial: {
			required: true
		},
		user_id: {
			required: true
		},		
		type: {
			required: true
		},
		aperture: {
			required: true
		},		
		shutter: {
			required: true
		},		
		iso: {
			required: true
		},
		shooting_time: {
			required: true,
			maxlength: 16
		},		
		shooting_number: {
			required: true,
			maxlength: 16
		},
		shooting_interval: {
			required: true,
			maxlength: 16
		}
    },
    messages: {		
		serial: {
			required: "请选择序列号"
		},
		user_id: {
			required: "请选择管理员"
		},
		type: {
			required: "请选择任务类型"
		},
		aperture: {
			required: "请选择光圈"
		},
		shutter: {
			required: "请选择快门"
		},
		iso: {
			required: "请选择ISO"
		},
		shooting_time: {
			required: "请输入拍摄时间",
			maxlength: "拍摄时间必需小于16个字符"
		},		
		shooting_number: {
			required: "请输入拍摄张数",
			maxlength: "拍摄张数必需小于16个字符"
		},		
		shooting_interval: {
			required: "请输入拍摄间隔",
			maxlength: "拍摄间隔必需小于16个字符"
		}
    }
    });
	
	
	$("#button_add").click(function(){
		if($("#newtask").valid()){
			$("#newtask").submit();
		}
	});	
	
	$("#button_edit").click(function(){
		if($("#newtask").valid()){
			$("#newtask").submit();
		}
	});
	
	
	change_serial();


	$('#type').change(function(){ 
		var p1 = $(this).children('option:selected').val();

		if (p1 == "0") {
			$("#aperture_outer").hide();
			$("#shutter_outer").hide();
			$("#iso_outer").hide();
		} else if (p1 == "1"){
			$("#aperture_outer").show();
			$("#shutter_outer").show();
			$("#iso_outer").show();
		}
	});
	
	if ($('#type').val() == "0") {
		$("#aperture_outer").hide();
		$("#shutter_outer").hide();
		$("#iso_outer").hide();
	} else if ($('#type').val() == "1"){
		$("#aperture_outer").show();
		$("#shutter_outer").show();
		$("#iso_outer").show();
	}
	
});

laydate.render({
	elem: '#shooting_time',
	type: 'time'
});

</script>

</body>
</html>