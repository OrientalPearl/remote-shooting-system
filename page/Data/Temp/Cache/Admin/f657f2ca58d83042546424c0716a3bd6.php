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
							<form method="POST" action="<?php echo U('Admin/DeviceManage/devlistSearch');?>" id="posts">
								<div class="portlet-body">
                                   <div class="row">
                                        <div class="col-md-12 margin">
										
	                                        <input type="text" name="search_serial" 
	                                     		placeholder="序列号" value="<?php echo ($_SESSION['search_field']['serial']); ?>">
	                                     	&nbsp;&nbsp;

	                                     	<input type="text" name="search_version"
	                                     		placeholder="固件版本" value="<?php echo ($_SESSION['search_field']['version']); ?>">
	                                     	&nbsp;&nbsp;
	                                     	
	                                     	<select name="search_user_id" tabindex="1" style=<?php echo ($user['type']==2?"display:none":""); ?>>
	                                     		<option value="">选择管理员</option>
		                                    	<?php if(is_array($list_user)): $i = 0; $__LIST__ = $list_user;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value="<?php echo ($vo["id"]); ?>" <?php if ($_SESSION['search_field']['user_id'] == $vo['id']) { echo "selected"; } ?> > <?php echo ($vo['name']); ?></option><?php endforeach; endif; endif; ?>
		                                    </select>
	                                     	&nbsp;&nbsp;
											
	                                     	<select name="search_status"  tabindex="1">
	                                     		<option value="">选择状态</option>
	                                     		<option value="1" <?php if ($_SESSION['search_field']['status'] == 1) { echo "selected"; } ?> >在线</option>
	                                     		<option value="2" <?php if ($_SESSION['search_field']['status'] == 2) { echo "selected"; } ?> >不在线</option>
		                                    </select>
	                                     	&nbsp;&nbsp;
			                                        	
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
							<form method="POST" action="<?php echo U('Admin/DeviceManage/deviceHandle');?>" id="posts">
								<div class="portlet-body">
                                   <div class="row">
                                        <div class="col-md-5 margin">
										   <div class="btn-group"  style=<?php echo ($user['type']==2?"display:none":""); ?>>
                                                <a href="<?php echo U('Admin/DeviceManage/deviceEdit');?>">
                                                    <button type="submit" class="btn btn-success" name="deviceAdd"
                                                            value='1'>
                                                        <i class="icon-plus"></i> 新增设备
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
	                                            <a title="导出" target="ajaxTodo" href="<?php echo U('Admin/DeviceManage/devExportXls');?>">
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
													<option value="/cms/index.php?m=admin&c=device_manage&a=devlist&page=20" <?php if ($page == 20) {echo selected;} ?> >20</option>
	                                                <option value="/cms/index.php?m=admin&c=device_manage&a=devlist&page=50" <?php if ($page == 50) {echo selected;} ?> >50</option>
	                                                <option value="/cms/index.php?m=admin&c=device_manage&a=devlist&page=100" <?php if ($page == 100) {echo selected;} ?> >100</option>
	                                                <option value="/cms/index.php?m=admin&c=device_manage&a=devlist&page=200" <?php if ($page == 200) {echo selected;} ?> >200</option>
                                            </select>
											<label>条</label>
                                        </div>
                                        
                                        <div class="col-md-3 margin">
                                            <label>总共<?php echo ($total_count); ?>条</label>
                                        </div>
                                    </div>
									<table class="table table-striped table-bordered table-hover">
									<thead>
										<tr>
											<th style="width: 8px;">
												<input type="checkbox" class="group-checkable" id="check-all" data-set="#post_table .checkboxes"/>
											</th>
											<th>序号</td>
											<th>序列号</td>
											
											
											<th style=<?php echo ($user['type']==2?"display:none":""); ?>>管理员</th>
											
											<th>拍摄状态</td>
											<th>固件版本</td>
											<th>自动升级</td>
											<th>光圈</td>
											<th>快门</td>
											<th>ISO</td>
											<th>市电状态</td>
											<th>温/湿度</td>
											<th style=<?php echo ($user['type']==2?"display:none":""); ?>>磁盘/带宽限制</th>
											<th>状态</td>
											<th>操作</td>
										</tr>
									</thead>
									<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr class="odd gradeX">
                                                <td><input type="checkbox" class="checkboxes" value="<?php echo ($vo["mac"]); ?>"
                                                           name="uid_chk_box[]"/>
                                                </td>
		                                        <td><?=$i?></td>
												<td><?php echo ($vo["serial"]); ?></td>
												
											
												<td style=<?php echo ($user['type']==2?"display:none":""); ?>><?php echo ($vo["user_name"]); ?></td>
											
		                                        <td><?php if ($vo['tasks_status'] != 0) {echo "正常";} else {echo "停止";} ?></td>
		                                        <td><?php echo ($vo["version"]); ?></td>
		                                        <td><?php if ($vo['auto_upgrade'] != 0) {echo "是";} else {echo "否";} ?></td>
		                                        <td><?php echo ($vo["aperture_current"]); ?></td>
												<td><?php echo ($vo["shutter_current"]); ?></td>
												<td><?php echo ($vo["iso_current"]); ?></td>
		                                        <td><?php echo ($vo["electricity"]); ?></td>
		                                        
		                                        <td><?php if ($vo['temperature']) {echo $vo['temperature'];} else {echo "-";} ?>/<?php if ($vo['humiture']) {echo $vo['humiture'];} else {echo "-";} ?></td>     
												
												<td style=<?php echo ($user['type']==2?"display:none":""); ?>><?php echo ($vo['disk_size']/1000); ?>GB/<?php echo ($vo["upload_limit_day"]); ?>MB/<?php echo ($vo['bwlimit']*8/1000); ?>Mbps</td>
												
												<td><?php if ($vo['device_ip'] != 0) {echo "在线";} else {echo "不在线";} ?></td>                                      
												<td>
													<a title="编辑" target="ajaxTodo" href="<?php echo U('Admin/DeviceManage/deviceEdit', array('serial' => $vo['serial']));?>">
			                                        	<img height='18px' src="/cms/Public/AdminLTE/img/editbtn.jpg"></a>                                                    
                                                   		
													<!--
			                                        <a title="事件日志" href="<?php echo U('Admin/DeviceManage/deveventlog', array('serial' => $vo['serial']));?>">
			                                        	<img height='18px' src="/cms/Public/AdminLTE/img/eventbtn.png"></a> 
					                                
													<a title="设定当前操作设备" target="ajaxTodo" onclick="javascript:return confirm('要设定这台设备为当前操作设备吗?')" href="<?php echo U('Admin/DeviceManage/deviceCurrent', array('serial' => $vo['serial']));?>">
			                                        	<img height='18px' src="/cms/Public/AdminLTE/img/timg.jpg"></a>
													-->
			                                        <a title="删除记录" target="ajaxTodo" onclick="javascript:return confirm('确实要删除这条记录吗?')" href="<?php echo U('Admin/DeviceManage/deviceDel', array('serial' => $vo['serial']));?>">
			                                        	<img height='18px' src="/cms/Public/AdminLTE/img/deletebtn.png"></a>
			                        
		                                    </div> 
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



</body>
</html>