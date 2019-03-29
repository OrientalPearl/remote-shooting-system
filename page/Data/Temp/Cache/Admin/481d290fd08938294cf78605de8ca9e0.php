<?php if (!defined('THINK_PATH')) exit();?>	<!DOCTYPE html>
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
							<form method="POST" action="<?php echo U('Admin/ShootingTask/taskSearch');?>" id="posts">
								<div class="portlet-body">
                                   <div class="row">
                                        <div class="col-md-12 margin">
	                                        <input type="text" name="search_serial" 
	                                     		placeholder="序列号" value="<?php echo ($_SESSION['search_field']['serial']); ?>">
	                                     	
	                                     	&nbsp;&nbsp;	                                     	
											
											<select name="search_user_id" style=<?php echo ($user['type']==2?"display:none":""); ?>>
	                                     		<option value="">选择管理员</option>
		                                    	<?php if(is_array($list_user)): $i = 0; $__LIST__ = $list_user;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value="<?php echo ($vo["id"]); ?>" <?php if ($_SESSION['search_field']['user_id'] == $vo['id']) { echo "selected"; } ?> > <?php echo ($vo['name']); ?></option><?php endforeach; endif; endif; ?>
		                                    </select>
											
	                                     	&nbsp;&nbsp;
			                                        												<select name="search_type">	                                     		<option value="">选择任务类型</option>		                                    	<option value="0" <?php if ($_SESSION['search_field']['type'] === "0") { echo "selected"; } ?> > 自动任务 </option>												<option value="1" <?php if ($_SESSION['search_field']['type'] === "1") { echo "selected"; } ?> > 手动任务 </option>		                                    </select>																																	&nbsp;&nbsp;																						
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
							<form method="POST" action="<?php echo U('Admin/ShootingTask/taskHandle');?>" id="posts">
								<div class="portlet-body">
                                   <div class="row">
                                        <div class="col-md-5 margin">
                                            <div class="btn-group">
                                                <a href="<?php echo U('Admin/ShootingTask/taskAdd');?>">
                                                    <button type="submit" class="btn btn-success" name="taskAdd"
                                                            value='1'>
                                                        <i class="icon-plus"></i> 新增拍摄任务
                                                    </button>
                                                </a>
                                            </div>

                                            <div class="btn-group">
                                                <button type="submit" class="btn btn-danger" name="delAll" value='1'><i
                                                        class="icon-remove icon-white">
                                                </i> 批量删除
												
                                                </button>
                                            </div>
                                            <!--
                                            <div class="btn-group">
	                                            <a title="导出" target="ajaxTodo" href="<?php echo U('Admin/System/adminExportxls');?>">
			                                        <img height='40px' src="/cms/Public/AdminLTE/img/printbtn.png"></a>
		                                    </div>    
											-->											
                                        </div>


                                        <div class="col-md-3 margin">
                                            <label>每页显示<?php echo C('PAGER');?></label>
                                            <select style="width:60px"
                                                    onchange="self.location.href=options[selectedIndex].value"
                                                    size="1"
                                                    name="mlink2">
													<option value="/cms/index.php?m=admin&c=shooting_task&a=task&page=20" <?php if ($page == 20) {echo selected;} ?> >20</option>
	                                                <option value="/cms/index.php?m=admin&c=shooting_task&a=task&page=50" <?php if ($page == 50) {echo selected;} ?> >50</option>
	                                                <option value="/cms/index.php?m=admin&c=shooting_task&a=task&page=100" <?php if ($page == 100) {echo selected;} ?> >100</option>
	                                                <option value="/cms/index.php?m=admin&c=shooting_task&a=task&page=200" <?php if ($page == 200) {echo selected;} ?> >200</option>
                                            </select>
											<label>条</label>
                                        </div>
                                        <div class="col-md-3 margin">
                                            <label>总共<?php echo ($total_count); ?>条</label>
                                        </div>
                                    </div>

									<!-- BEGIN EXAMPLE TABLE PORTLET-->		
									<table class="table table-striped table-bordered table-hover">
										<thead>
										<tr>
											<th style="width: 8px;">
												<input type="checkbox" class="group-checkable" id="check-all" data-set="#post_table .checkboxes"/>
											</th>
											<th>序号</th>
											<th>序列号</th>																						<th  style=<?php echo ($user['type']==2?"display:none":""); ?> > 管理员</th>																							<th>任务类型</th>
											<th>光圈</th>
											<th>快门</th>
											<th>ISO</th>
											<th>拍摄时间</th>
											<th>拍摄张数</th>
											<th>拍摄间隔</th>
											<th>操作</th>
										</tr>
										</thead>
										<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr class="odd gradeX">
                                                <td><input type="checkbox" class="checkboxes" value="<?php echo ($vo["id"]); ?>"
                                                           name="uid_chk_box[]"/>
                                                </td>
												<td><?=$i?></td>
												<td><?php echo ($vo["serial"]); ?></td>												<td  style=<?php echo ($user['type']==2?"display:none":""); ?>><?php echo ($vo["user_name"]); ?></td>																								<td><?php if ($vo['type'] == 0) {echo "自动任务";} else {echo "手动任务";} ?></td>
												<td><?php if ($vo['type'] == 0) {echo "-";} else {echo $vo['aperture'];} ?></td>
												<td><?php if ($vo['type'] == 0) {echo "-";} else {echo $vo['shutter'];} ?></td>
												<td><?php if ($vo['type'] == 0) {echo "-";} else {echo $vo['iso'];} ?></td>
		                                        <td><?php echo ($vo["shooting_time"]); ?></td>
		                                        <td><?php echo ($vo["shooting_number"]); ?></td>  
												<td><?php echo ($vo["shooting_interval"]); ?></td> 
												<td>
			                                    	<a title="编辑" target="ajaxTodo" href="<?php echo U('Admin/ShootingTask/taskEdit', array('id' => $vo['id']));?>">
			                                        	<img height='18px' src="/cms/Public/AdminLTE/img/editbtn.jpg"></a>                                                    
                                        
			                                        <a title="确实要删除这条记录吗?" target="ajaxTodo" onclick="javascript:return a_confirm()" href="<?php echo U('Admin/ShootingTask/taskDel', array('id' => $vo['id'], 'serial' => $vo['serial']));?>">
			                                        	<img height='18px' src="/cms/Public/AdminLTE/img/deletebtn.png"></a>
                                                </td>
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



<script>
    $(document).ready(function () {

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