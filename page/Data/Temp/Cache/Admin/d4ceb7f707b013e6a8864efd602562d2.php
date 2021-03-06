<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html class="bg-black">
<head>
    <meta charset="UTF-8">
    <title>管理面板 | <?php echo C('WRT_TITLE');?> </title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- bootstrap 3.0.2 -->
    <link href="/cms/Public/AdminLTE/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <!-- font Awesome -->
    <!--<link href="/cms/Public/AdminLTE/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>-->
    <!-- Theme style -->
    <link href="/cms/Public/AdminLTE/css/AdminLTE.css" rel="stylesheet" type="text/css"/>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="/cms/Public/share/js/html5.js"></script>
    <script src="/cms/Public/share/js/respond.min.js"></script>
    <![endif]-->
</head>
<body class="bg-black">

<div class="form-box" id="login-box">
    <div style="display:none;" class="header">GreenCMS</div>
    <div class="header">YSF 管理登陆</div>
    <form action="<?php echo U('Admin/Login/login');?>" method="POST">
        <div class="body bg-gray">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="用户名"/>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="密码"/>
            </div>
			<!--
            <div class="form-group">
                    <input  type="text" name="vertify" class="form-control" placeholder="输入下方验证码"/>

                </div>


                <div class="form-group">
                    <img id="imgVerify" src="<?php echo U('Admin/Login/vertify');?>" onclick="fleshVerify();"/>
                </div>
			-->

            <div style="display:none;" class="form-group">
                <input type="checkbox" name="remember" value="1"/> 记住我
            </div>
        </div>
        <div class="footer">
            <button type="submit" class="btn bg-olive btn-block">立即登录</button>
        </div>
    </form>

    <div  class="margin text-center">
        <div class="copyright">
            2019-<?php echo date('Y');?> &copy; YSF <?php echo C('software_version');?>
        </div>
		
		<div style="display:none;" class="copyright">
            GreenCMS 绿荫工作室 出品
        </div>
		
    </div>
</div>


<!-- jQuery 2.0.2 -->
<script type="text/javascript" src="/cms/Public/share/js/jquery-2.0.2.min.js"></script>
<!-- Bootstrap -->
<script src="/cms/Public/AdminLTE/js/bootstrap.min.js" type="text/javascript"></script>

<script>
    function fleshVerify() {
        //重载验证码
        var time = Math.random();        
        var src = '<?php echo U("Admin/Login/vertify");?>';
        
        document.getElementById('imgVerify').src = src + '&tm=' + time;
    }

</script>
<script>
    $(document).ready(function () {

        setTimeout(fleshVerify,1000);

    });
</script>

</body>
</html>