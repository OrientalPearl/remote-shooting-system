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
	<style type="text/css">
		.divpreview{ 
			border:1px solid #000; 
			width:100%; 
			overflow:hidden
		} 
		.divpreview img{
			max-width:100%;
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
							<form action="<?php echo U('Admin/ShootingControl/parameterEditHandle');?>"
								class="form-horizontal" id="newparameter" method="post">
							
							
							<div class="form-group">
                            	<label class="control-label">序列号：</label>

                           		<div class="controls">
								  <input id="serial" type="text" readonly="readonly" class="form-control large" name="serial" value="<?php echo $devinfo['serial'];?>"/>
                                </div>
                            </div>
														                                           	
							<div class="form-group">
                            	<label class="control-label">预览光圈 ：</label>

                           		<div class="controls">
									 <select id="aperture_preview" name="aperture_preview" class="large form-control" tabindex="1" >
										<option value="">请选择光圈 </option>
										<?php if(is_array($list_aperture)): $i = 0; $__LIST__ = $list_aperture;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["aperture"]); ?> <?php if($devinfo["aperture_preview"] == $vo['aperture']): ?>selected<?php endif; ?>> <?php echo ($vo["aperture"]); ?> </option><?php endforeach; endif; endif; ?>
		                              </select>
                                </div>
                            </div>
							
							<div class="form-group">
                            	<label class="control-label">预览快门 ：</label>

                           		<div class="controls">									 
								
									<select id="shutter_preview" name="shutter_preview" class="large form-control" tabindex="1" >
										<option value="">请选择快门 </option>
										<?php if(is_array($list_shutter)): $i = 0; $__LIST__ = $list_shutter;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["shutter"]); ?> <?php if($devinfo["shutter_preview"] == $vo['shutter']): ?>selected<?php endif; ?>> <?php echo ($vo["shutter"]); ?> </option><?php endforeach; endif; endif; ?>
		                              </select>
                                </div>
                            </div>
							<div class="form-group">
                            	<label class="control-label">预览ISO ：</label>

                           		<div class="controls">									
								
									<select id="iso_preview" name="iso_preview" class="large form-control" tabindex="1" >
										<option value="">请选择ISO </option>
										<?php if(is_array($list_iso)): $i = 0; $__LIST__ = $list_iso;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["iso"]); ?> <?php if($devinfo["iso_preview"] == $vo['iso']): ?>selected<?php endif; ?>> <?php echo ($vo["iso"]); ?> </option><?php endforeach; endif; endif; ?>
		                              </select>
                                </div>
                            </div>
							
							<div class="form-group">
                            	<label class="control-label">光圈范围 ：</label>
								
                           		<div class="controls">
									 <select id="aperture_min" name="aperture_min" class="small form-control" tabindex="1" >
										<option value="">光圈范围起始 </option>
										<?php if(is_array($list_aperture)): $i = 0; $__LIST__ = $list_aperture;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["aperture"]); ?> <?php if($devinfo["aperture_min"] == $vo['aperture']): ?>selected<?php endif; ?>> <?php echo ($vo["aperture"]); ?> </option><?php endforeach; endif; endif; ?>
		                              </select>
									  &nbsp;&nbsp;
									  <-->
									  &nbsp;&nbsp;
									   <select id="aperture_max" name="aperture_max" class="small form-control" tabindex="1" >
										<option value="">光圈范围结束 </option>
										<?php if(is_array($list_aperture)): $i = 0; $__LIST__ = $list_aperture;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["aperture"]); ?> <?php if($devinfo["aperture_max"] == $vo['aperture']): ?>selected<?php endif; ?>> <?php echo ($vo["aperture"]); ?> </option><?php endforeach; endif; endif; ?>
		                              </select>
								</div>
							</div>
							
							<div class="form-group">
                            	<label class="control-label">快门范围 ：</label>
								
                           		<div class="controls">
									 <select id="shutter_min" name="shutter_min" class="small form-control" tabindex="1" >
										<option value="">快门范围起始 </option>
										<?php if(is_array($list_shutter)): $i = 0; $__LIST__ = $list_shutter;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["shutter"]); ?> <?php if($devinfo["shutter_min"] == $vo['shutter']): ?>selected<?php endif; ?>> <?php echo ($vo["shutter"]); ?> </option><?php endforeach; endif; endif; ?>
		                              </select>
									  
									  &nbsp;&nbsp;
									  <-->
									  &nbsp;&nbsp;
									  
									 <select id="shutter_max" name="shutter_max" class="small form-control" tabindex="1" >
										<option value="">快门范围结束 </option>
										<?php if(is_array($list_shutter)): $i = 0; $__LIST__ = $list_shutter;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["shutter"]); ?> <?php if($devinfo["shutter_max"] == $vo['shutter']): ?>selected<?php endif; ?>> <?php echo ($vo['shutter']); ?> </option><?php endforeach; endif; endif; ?>
		                              </select>
                                </div>
							</div>
							
							
							<div class="form-group">
                            	<label class="control-label">ISO范围 ：</label>
								
                           		<div class="controls">
									 <select id="iso_min" name="iso_min" class="small form-control" tabindex="1" >
										<option value="">ISO范围起始 </option>
										<?php if(is_array($list_iso)): $i = 0; $__LIST__ = $list_iso;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["iso"]); ?> <?php if($devinfo["iso_min"] == $vo['iso']): ?>selected<?php endif; ?>> <?php echo ($vo["iso"]); ?> </option><?php endforeach; endif; endif; ?>
		                              </select>									 
									  
									  &nbsp;&nbsp;
									  <-->
									  &nbsp;&nbsp;
									  
									  <select id="iso_max" name="iso_max" class="small form-control" tabindex="1" >
										<option value="">ISO范围结束 </option>
										<?php if(is_array($list_iso)): $i = 0; $__LIST__ = $list_iso;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value=<?php echo ($vo["iso"]); ?> <?php if($devinfo["iso_max"] == $vo['iso']): ?>selected<?php endif; ?>> <?php echo ($vo["iso"]); ?> </option><?php endforeach; endif; endif; ?>
		                              </select>
                                </div>
							</div>						
							
							<input id="button_type" type="hidden" name="button_type" value="button_preview"/>
							
							<div class="form-actions">

								<a href="javascript:;" class="btn btn-info" id="button_preview"><i
									class="icon-ok"></i>
										拍摄预览
                               	</a>
								&nbsp;&nbsp;
								
								<a href="javascript:;" class="btn btn-info" id="button_apply"><i
									class="icon-ok"></i>
										设为拍摄参数
                               	</a>
								
                                &nbsp;&nbsp;
                                    
                                <a href="<?php echo U('Admin/ShootingControl/parameterlist');?>" class="btn btn-info" id="button"><i
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
		
		
		
		<section class="content">
            <div class="row">
                <div class="col-md-10">
                    <div class="box box-warning">
						<div class="divpreview" align="center">
							<a href="<?php echo U('Admin/ShootingControl/parameterPreviewSrc', array('serial' => $devinfo['serial']));?>" style="display:nome" id="preview_img_url"></a>
							<img class="img" id="preview_img" class="divpreview" src="" alt="等待预览">
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

function do_get_preview(){
	var ret = 0;
	var url_address = $("#preview_img_url").prop('href')
	$.ajax({
		url: url_address,
		type: "POST",
		success: function(resp){
			//console.log(resp);
			$("#preview_img").attr("src", resp);
		},
		error : function(o){	
			console.log(o);
		}
	})
	
	return ret;
}

setInterval(do_get_preview, 6000);

$.validator.addMethod("aperture_min_not_null",function(value,element,params){
	if($("#button_type").val() == "button_preview")
		return true;
		
	if($("#aperture_min").val() != ""){
		return true;
	}
	else{
		return false;
	}

},"");

$.validator.addMethod("aperture_max_not_null",function(value,element,params){
	if($("#button_type").val() == "button_preview")
		return true;
		
	if($("#aperture_max").val() != ""){
		return true;
	}
	else{
		return false;
	}

},"");



$.validator.addMethod("shutter_min_not_null",function(value,element,params){
	if($("#button_type").val() == "button_preview")
		return true;
		
	if($("#shutter_min").val() != ""){
		return true;
	}
	else{
		return false;
	}

},"");

$.validator.addMethod("shutter_max_not_null",function(value,element,params){
	if($("#button_type").val() == "button_preview")
		return true;
		
	if($("#shutter_max").val() != ""){
		return true;
	}
	else{
		return false;
	}

},"");



$.validator.addMethod("iso_min_not_null",function(value,element,params){
	if($("#button_type").val() == "button_preview")
		return true;
		
	if($("#iso_min").val() != ""){
		return true;
	}
	else{
		return false;
	}

},"");

$.validator.addMethod("iso_max_not_null",function(value,element,params){
	if($("#button_type").val() == "button_preview")
		return true;
		
	if($("#iso_max").val() != ""){
		return true;
	}
	else{
		return false;
	}

},"");


$().ready(function() {
  $("#newparameter").validate({
    errorElement: "em",
	rules: {
		serial: {
			required: true,
			rangelength:[4,4]
		},
		aperture_preview: {
			required: true
		},		
		shutter_preview: {
			required: true
		},		
		iso_preview: {
			required: true
		},
		aperture_max: {
			aperture_min_not_null: "",
			aperture_max_not_null: ""
		},
		shutter_max: {
			shutter_min_not_null: "",
			shutter_max_not_null: ""
		},
		iso_max: {
			iso_min_not_null: "",
			iso_max_not_null: ""
		}
    },
    messages: {		
		serial: {
			required: "请输入序列号",
			rangelength: "序列号长度必须为4个字符"
		},
		aperture_preview: {
			required: "请选择预览光圈"
		},
		shutter_preview: {
			required: "请选择预览快门"
		},
		iso_preview: {
			required: "请选择预览ISO"
		},		
		aperture_max: {
			aperture_min_not_null: "请选择光圈范围起始",
			aperture_max_not_null: "请选择光圈范围结束"
		},
		shutter_max: {
			shutter_min_not_null: "请选择光圈范围起始",
			shutter_max_not_null: "请选择光圈范围结束"
		},
		iso_max: {
			iso_min_not_null: "请选择ISO范围起始",
			iso_max_not_null: "请选择ISO范围结束"
		}
    }
    });
	
	
	$("#button_preview").click(function(){
		$("#button_type").val("button_preview");
		if($("#newparameter").valid()){
			$("#newparameter").submit();
		}
	});	
	
	$("#button_apply").click(function(){
		$("#button_type").val("button_apply");
		if($("#newparameter").valid()){
			$("#newparameter").submit();
		}
	});
	
	do_get_preview();
});

</script>
</body>
</html>