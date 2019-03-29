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
							<form method="POST" action="<?php echo U('Admin/DeviceManage/deveventlogSearch');?>" id="posts">
								<div class="portlet-body">
                                   <div class="row">
                                        <div class="col-md-12 margin">
	                                        <input type="text" name="search_serial" 
	                                     		placeholder="序列号" value="<?php echo ($_SESSION['search_field']['serial']); ?>">
			                                        	
	                                     	<button title='查找' type="submit" name="search" class='btn' value='1'> 
												<img height='18px' src="/cms/Public/AdminLTE/img/searchbtn.jpg"></button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="box box-warning">
                        <!-- /.box-header -->
                        <div class="box-body">
							<form method="POST" action="<?php echo U('Admin/DeviceManage/deveventlog');?>" id="posts">
								<div class="portlet-body">
                                   <div class="row">
                                        <div class="col-md-3 margin">
                                            <label>每页显示<?php echo C('PAGER');?></label>
                                            <select style="width:60px"
                                                    onchange="self.location.href=options[selectedIndex].value"
                                                    size="1"
                                                    name="mlink2">
													<option value="/cms/index.php?m=admin&c=device_manage&a=deveventlogsearch&page=20" <?php if ($page == 20) {echo selected;} ?> >20</option>
	                                                <option value="/cms/index.php?m=admin&c=device_manage&a=deveventlogsearch&page=50" <?php if ($page == 50) {echo selected;} ?> >50</option>
	                                                <option value="/cms/index.php?m=admin&c=device_manage&a=deveventlogsearch&page=100" <?php if ($page == 100) {echo selected;} ?> >100</option>
	                                                <option value="/cms/index.php?m=admin&c=device_manage&a=deveventlogsearch&page=200" <?php if ($page == 200) {echo selected;} ?> >200</option>
                                            </select>
											<label>条</label>
                                        </div>
										<div class="col-md-3 margin">
                                            <label>总共<?php echo ($total_count); ?>条</label>
                                        </div>
                                    </div>

		                            <h4><?php echo ($listname); ?>列表</h4>
		                            <!-- BEGIN EXAMPLE TABLE PORTLET-->
		
		                            <table
		                                    class="table table-striped table-bordered table-hover">
		                                <thead>
		                                <tr>
		                                	<th>序号</td>
		                                    <th>序列号</td>
		                                    <th colspan="5">日志信息</td>
		                                    <th>记录时间</td>
		                                </tr>
		                                </thead>
		                                <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr aid="<?php echo ($vo["id"]); ?>">
		                                    	<td><?=$i?></td>
		                                        <td><?php echo ($vo["serial"]); ?></td>
		                                        <td colspan="5"><?php echo ($vo["event"]); ?></td>
		                                        <td><?php echo ($vo["time"]); ?></td>
		                                    </tr><?php endforeach; endif; endif; ?>
		                            </table>
		
					    			<div class="pagination">
		                                <ul class="pagination inline"> <?php echo ($pager_bar); ?></ul>
		                            </div>
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



</body>
</html>