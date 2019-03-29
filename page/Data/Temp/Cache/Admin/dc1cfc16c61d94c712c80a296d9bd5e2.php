<?php if (!defined('THINK_PATH')) exit();?><!-- header logo: style can be found in header.less -->
<header class="header">
<div style="display:none;" class="logo">GreenCMS</div>
<a href="<?php echo U('Admin/index/index');?>" class="logo"  id="logo" style="font-size: 30px">YSF</a>
<!-- Header Navbar: style can be found in header.less -->
<nav class="navbar navbar-static-top" role="navigation">

    <!-- Sidebar toggle button-->
    <a href="#" class="navbar-btn sidebar-toggle" data-toggle="offcanvas" role="button">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </a>
	
	

    <div class="navbar-right">
        <ul class="nav navbar-nav">
            <!-- User Account: style can be found in dropdown.less -->
            <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <i class="glyphicon glyphicon-user"></i>
                    <span> <?php if ($user['type'] == 1) { echo '系统管理员'; } else { echo '设备管理员'; } ?> <i class="caret"></i></span>
                </a>
                <ul class="dropdown-menu">
                    <!-- User image -->
                    <li class="user-header bg-light-blue">
                        <img src="/cms/Public/baracktocat.jpg" class="img-circle" alt="User Image"/>
				
                        <p>
                            <small>注册时间:<?php echo ($user["create_time"]); ?></small>
                            <small>登录名:<?php echo ($user["name"]); ?></small>
                        </p>
                    </li>
                   
                    <!-- Menu Footer-->
                    <li class="user-footer">
                        <div class="pull-right">
                            <a href="<?php echo U('Admin/Login/logout');?>" class="btn btn-default btn-flat">安全退出</a>
                        </div>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
</header>