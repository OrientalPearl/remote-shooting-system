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
                            <?php switch($action_name): case "editUser": ?><form action="<?php echo U('Admin/System/adminEditHandle');?>"
                                    	class="form-horizontal" id="newuser" method="post"><?php break;?>
                                    
                                    <?php default: ?>
                                    <form action="<?php echo U('Admin/System/adminAddHandle');?>"
                                          class="form-horizontal" id="newuser" method="post"><?php endswitch;?>
  
                            <input type="text" style="display:none" name="user_id" value="<?php echo $info['id'];?>"/>
                            

                            <div class="form-group">
                            	<label class="control-label">用户名：</label>

                           		<div class="controls">
								   <?php switch($action_name): case "addUser": ?><input id="user_login" type="text" placeholder="用户名(必填)"" class="form-control large" name="user_login" value="<?php echo $info['name'];?>"/><?php break;?>
			                            	
			                            <?php default: ?>                                	
										<input type="text" disabled="disabled" class="form-control large" name="user_login" value="<?php echo $info['name'];?>"/><?php endswitch;?>

                                </div>
                            </div>
                                                     
                            <div class="form-group" <?php if($action_name != 'editUser'): ?>style="display:none"<?php endif; ?>>
								<label class="control-label">修改密码：</label>
								<div class="controls">
									<input type="checkbox" name="modify_pwd" id="modify_pwd" checked="checked" value="1" /> 
				                        <span class="help-inline"></span>
	                           	</div>
                            </div>
                            
                            <div id='div_password' name='div_password'>
	                            <div class="form-group">
		                            <?php switch($action_name): case "editUser": ?><label class="control-label">新密码：</label><?php break;?>
			                            	
			                            <?php default: ?>
											<label class="control-label">密码：</label><?php endswitch;?>
		                            <div class="controls">
		                                <input type="password" placeholder="******(必填)""
			                                		class="form-control large" name="password" id="password" value=""/>
										<span class="help-inline"></span>
	                            	</div>
	                           	</div>
	                           	<div class="form-group">
	                                <?php switch($action_name): case "addUser": ?><label class="control-label">确认密码：</label><?php break;?>
	                                    	 
	                                    <?php default: ?>
	                                    	<label class="control-label">确认新密码：</label><?php endswitch;?>
	                                <div class="controls">
	                                	<input type="password" placeholder="******(必填)""
	                                    	class="form-control large" name="rpassword" id="rpassword" value=""/>
	                                    <span class="help-inline"></span>
	                                </div>
	                            </div>
                            </div>
                            
                            </div>
                            
							<div class="form-group">
                           		<label class="control-label">邮箱地址：</label>
                            	<div class="controls">
                                	<input id="email" type="text" placeholder="邮箱地址" class="form-control large"
                                    	name="email" value="<?php echo $info['email'];?>"/>
                                </div>
                            </div>
							
                            <div class="form-group">
                           		<label class="control-label">备注：</label>
                            	<div class="controls">
                                	<input id="remark" type="text" placeholder="备注" class="form-control large"
                                    	name="user_remark" value="<?php echo $info['remark'];?>"/>
                                </div>
                            </div>
                            
                                
                           	<div class="form-actions">

	                               	<?php switch($action_name): case "editUser": ?><a href="javascript:;" class="btn btn-info" id="button_edit"><i
												class="icon-ok"></i>
											修改<?php break;?>
	                                    <?php case "addUser": ?><a href="javascript:;" class="btn btn-info" id="button_add"><i
												class="icon-ok"></i>
											添加<?php break;?>
	                                	<?php default: ?>
											添加<?php endswitch;?>
                               	</a>
                                    
                                &nbsp;&nbsp;
                                    
                                <a href="<?php echo U('Admin/System/admin');?>" class="btn btn-info" id="button"><i
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

$.validator.addMethod("passwd_same",function(value,element,params){
    if($("#password").val() == $("#rpassword").val()){
        return true;
    }
	else{
        return false;
    }
},"两次输入的密码不一致");

$.validator.addMethod("passwd_no_null",function(value,element,params){
	if($("#form_action").val() == "editUser"){    
		return true;
	}
	if(value != ""){
		return true;
	}
	else{
		return false;
	}

},"密码不能为空");

$().ready(function() {
    $('#modify_pwd').on('ifChecked', function (event) {
    	document.getElementById('div_password').style.display = "";
    });
    
    $('#modify_pwd').on('ifUnchecked', function (event) {
    	document.getElementById('div_password').style.display = "none";
    });
  $("#newuser").validate({
    errorElement: "em",
	rules: {
		user_login: {
			required: true,
			maxlength: 64
		},	
		password: {
			minlength: 3,
			maxlength: 16,
			passwd_no_null: ""
		},		
		rpassword: {
			minlength: 3,
			maxlength: 16,
			passwd_no_null: "",
			passwd_same: ""
		},
		remark: {
			maxlength: 128
		}
    },
    messages: {		
		user_login: {
			required: "请输入用户名",
			maxlength: "用户名必需小于64个字符"
		},				
		password: {
			minlength: "密码必需大于2个字符",
			maxlength: "密码必需小于16个字符",
			passwd_no_null: "密码不能为空"
		},		
		rpassword: {
			minlength: "密码必需大于2个字符",
			maxlength: "密码必需小于16个字符",
			passwd_no_null: "密码不能为空",
			passwd_same: "两次输入的密码不一致"
		},
		remark: {
			maxlength: "用户名描述必需小于128个字符"
		}
    }
    });
	
	
	$("#button_add").click(function(){
		if($("#newuser").valid()){
			$("#newuser").submit();
		}
	});	
	
	$("#button_edit").click(function(){
		if($("#newuser").valid()){
			$("#newuser").submit();
		}
	});
});

</script>

</body>
</html>