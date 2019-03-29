<?php if (!defined('THINK_PATH')) exit();?><!-- Left side column. contains the logo and sidebar -->
<aside class="left-side sidebar-offcanvas">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="pull-left image">
                <img src="/cms/Public/baracktocat.jpg" class="img-circle" alt="User Image"/>
            </div>
            <div class="pull-left info">
                <p>你好, <?php echo ($user["name"]); ?></p>
                <a href="#" alt="点击锁屏"><i class="fa fa-circle text-success"></i> 在线</a>
            </div>
        </div>

        <ul class="sidebar-menu">

            <?php echo ($menu); ?>

            <?php echo hook('adminSideBar', 'menu');?>

             </ul></li>
        </ul>


    </section>
    <!-- /.sidebar -->
 
</aside>