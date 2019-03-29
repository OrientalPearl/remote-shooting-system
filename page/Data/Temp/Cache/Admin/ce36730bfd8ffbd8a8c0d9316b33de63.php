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

                           <form action="<?php echo U('Admin/System/emailUpdate');?>"
                               class="form-horizontal" method="post" id="email_form">
							
                               		
                            <div class="form-group-list">
								<div class="controls">
								    <label class="control-label">邮件服务器地址：</label>
                                    <input type='text' placeholder="邮件服务器地址" class="form-control large" value="<?php echo ($data['email_server_address']); ?>" name='email_server_address' id='email_server_address'/>
                                </div>
								&nbsp;
								
								<div class="controls">
								    <label class="control-label">邮件服务器端口：</label>
                                    <input type='text' placeholder="邮件服务器端口, 一般使用587端口" class="form-control large" value="<?php echo ($data['email_server_port']); ?>" name='email_server_port' id='email_server_port'/>
                                </div>
								&nbsp;
								
								<div class="controls">
								    <label class="control-label">发件人地址：</label>
                                    <input type='text' placeholder="发件人地址" class="form-control large" value="<?php echo ($data['email_sender']); ?>" name='email_sender' id='email_sender'/>
                                </div>
								&nbsp;
								
								<div class="controls">
								    <label class="control-label">发件人显示名：</label>
                                    <input type='text' placeholder="发件人显示名" class="form-control large" value="<?php echo ($data['email_sender_show']); ?>" name='email_sender_show' id='email_sender_show'/>
                                </div>
								&nbsp;
								
								<!--
								<div class="controls">
									<label class="control-label">是否需要认证：</label>
									
									<select name="email_auth" id="email_auth" class="large form-control">
										<option value="">选请择认证方式</option>
										<option value="0" <?php if($data["email_auth"] == '0'): ?>selected<?php endif; ?> > 不需要认证 </option>
										<option value="1" <?php if($data["email_auth"] == '1'): ?>selected<?php endif; ?> > 需要认证 </option>
									</select>
								
								</div>

								&nbsp;
								<div class="controls" id='email_auth_user_outer'>
								    <label class="control-label">用户名：</label>
                                    <input type='text' class="form-control large" value="<?php echo ($data['email_auth_user']); ?>" name='email_auth_user' id='email_auth_user'/>
                                </div>
								&nbsp;
								-->
								
								<div class="controls" id='email_auth_passwd_outer'>
								    <label class="control-label">第三方登陆授权码：</label>
                                    <input type='text' placeholder="第三方登陆授权码，请到邮箱进行设置获取" class="form-control large" value="<?php echo ($data['email_auth_passwd']); ?>" name='email_auth_passwd' id='email_auth_passwd'/>
                                </div>
								&nbsp;

								<div class="controls">
								    <label class="control-label">邮件标题：</label>
                                    <input type='text' placeholder="邮件标题" class="form-control large" value="<?php echo ($data['email_subject']); ?>" name='email_subject' id='email_subject'/>
                                </div>
								&nbsp;

								<div class="controls">
								    <label class="control-label">收件人地址：</label>
                                    <textarea  placeholder="收件人地址" rows="8" class="form-control large" name='email_auth_receiver' id='email_auth_receiver'><?php echo ($data['email_auth_receiver']); ?> </textarea>
									<p style="display:inline">一行一个邮件地址</p>
                                </div>
								&nbsp;
							</div>
							
                           	<div class="form-actions">
                                <button type="submit" id="button_email" class="btn btn-info submit">确定</button>
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
    </script><style type="text/css">
	em.error {
		background:url("/cms/Public/share/img/unchecked.gif") no-repeat 4px 4px;
		padding-left: 20px;
	}

	em.error { color: red; }
</style>

<script type="text/javascript">

$().ready(function() {

  $("#email_form").validate({
    errorElement: "em",
	rules: {
		email_server_address: {
			required: true
		},
		email_server_port: {
			required: true
		},		
		email_sender: {
			required: true
		},
		email_sender_show: {
			required: true
		},		
		email_auth: {
			required: true
		},		
		email_auth_user: {
			required: true
		},
		email_auth_passwd: {
			required: true
		}
    },
    messages: {		
		email_server_address: {
			required: "请输入邮件服务器地址"
		},
		email_server_port: {
			required: "请输入邮件服务器端口"
		},
		email_sender: {
			required: "请选发件人发地址"
		},
		email_sender_show: {
			required: "请输入发件人显示名"
		},
		email_auth: {
			required: "请选择是否需要认证"
		},
		email_auth_user: {
			required: "请输入用户名"
		},
		email_auth_passwd: {
			required: "请输入第三方登陆授权码"
		}
    }
    });
	
	
	$("#button_email").click(function(){
		if($("#email_form").valid()){
			$("#email_form").submit();
		}
	});	

	$('#email_auth').change(function(){ 
		var p1 = $(this).children('option:selected').val();

		if (p1 == "0") {
			$("#email_auth_user_outer").hide();
			$("#email_auth_passwd_outer").hide();
		} else if (p1 == "1"){
			$("#email_auth_user_outer").show();
			$("#email_auth_passwd_outer").show();
		}
	});
	
	
	if ($("#email_auth").val() == "0") {
		$("#email_auth_user_outer").hide();
		$("#email_auth_passwd_outer").hide();
	} else if ($("#email_auth").val() == "1"){
		$("#email_auth_user_outer").show();
		$("#email_auth_passwd_outer").show();
	}
});

</script>
</body>
</html>