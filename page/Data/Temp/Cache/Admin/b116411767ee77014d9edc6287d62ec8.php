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

.pic_list
{
	display: flex;
	display: -webkit-flex; 
	flex-wrap: wrap;

}

.pic_list img
{
	margin-left: 20px;
	margin-top: 25px;
	width: 200px;
	height: 200px;
	border:2px solid white;
}

.pic_list .pic_op
{
	margin-top: 5px;
	flex-direction: colume;
	margin-left: 20px;
}

.pic_list .file_name
{
	margin-top: 5px;
	flex-direction: colume;
	margin-left: 20px;
	color:rgb(28, 139, 193);
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
							<form action="<?php echo U('Admin/PictureManage/pictureEditHandle');?>"
								class="form-horizontal" id="newpicture" method="post">
							
								<input id="button_type" type="hidden" name="button_type" value="button_all"/>
								<input id="serial" type="hidden" name="serial" value="<?php echo ($devinfo['serial']); ?>"/>
								
								<div class="form-actions">
									<!--
									<a href="javascript:;" class="btn btn-info" id="button_all"><i
										class="icon-ok"></i>
											全部下载并删除
									</a>
									
									&nbsp;&nbsp;
									<a href="javascript:;" class="btn btn-info" id="button_part_delete"><i
										class="icon-ok"></i>
											删除选中项
									</a>
									
									&nbsp;&nbsp;								
									<a href="javascript:;" class="btn btn-info" id="button_part_download"><i
										class="icon-ok"></i>
											下载选中项
									</a>
									&nbsp;&nbsp;
									-->
									
									
									<a title='全部下载' class="btn btn-info" target='ajaxTodo' id="button_all" href="<?php echo U('Admin/PictureManage/pictureDownloadAll', array('serial' => $devinfo['serial']));?>">
													全部下载</a>									
									<a title='全部删除' class="btn btn-info" target='ajaxTodo' id="button_all" href="<?php echo U('Admin/PictureManage/pictureDelAll', array('serial' => $devinfo['serial']));?>">
													全部删除</a>
	
									<a title='删除选中项' class="btn btn-info" target='ajaxTodo' id="button_part_delete" href="<?php echo U('Admin/PictureManage/pictureOperate', array('serial' => $devinfo['serial'], 'type' => 'part_delete'));?>">
													删除选中项</a>
									<a title='下载选中项' class="btn btn-info" target='ajaxTodo' id="button_part_download" href="<?php echo U('Admin/PictureManage/pictureOperate', array('serial' => $devinfo['serial'], 'type' => 'part_download'));?>">
													下载选中项</a>
									
								</div>
								<!-- picture list -->
								<div class="pic_list">
									<?php if(is_array($piclist)): $i = 0; $__LIST__ = $piclist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><div>
											<a href="<?php echo U('Admin/PictureManage/pictureShow', array('serial' => $devinfo['serial'], 'filename' => $vo['file_name']));?>" target="_blank">
												<!--<img src="<?php echo ($vo['file_path_thumbnail']); ?>">-->
												<img src="/cms/photo.php?serial=<?php echo ($devinfo['serial']); ?>&filename=<?php echo ($vo['file_name']); ?>">
											</a>

											<div class="file_name">
												日期：<?php echo ($vo['file_time']); ?>
											</div>

											<div class="pic_op">
												<input type="checkbox" class="checkboxes" value="<?php echo ($vo['file_name']); ?>"
																					name="uid_chk_box[]"/>
												&nbsp;
												<a title='下载RAW文件' target='ajaxTodo' href="<?php echo U('Admin/PictureManage/pictureDownload', array('serial' => $devinfo['serial'], 'filename' => $vo['file_name']));?>">
													下载</a>
												&nbsp;
												<a title='删除' target='ajaxTodo' href="<?php echo U('Admin/PictureManage/pictureDelete', array('serial' => $devinfo['serial'], 'filename' => $vo['file_name']));?>">
													删除</a>

												<!--
												&nbsp;
												<?php echo ($vo['file_size']); ?>
												&nbsp;
												<?php echo ($vo['file_name']); ?>
												-->
											</div>
										</div><?php endforeach; endif; endif; ?>
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




<script type="text/javascript">


function toQrPay() {
	var parames = new Array();
	parames.push({ name: "userName", value: "admin88"});
	parames.push({ name: "token", value: "token"});

	Post("http://localhost:8080/qrPay/sys/tokenLogin", parames);

	return false;
}

/*
*功能： 模拟form表单的提交
*参数： URL 跳转地址 PARAMTERS 参数
*/
function doPost(URL, PARAMTERS) {

	console.log(URL);
	console.log(PARAMTERS);

	//创建form表单
	var temp_form = document.createElement("form");
	temp_form.action = URL;
	//如需打开新窗口，form的target属性要设置为'_blank'
	temp_form.target = "_self";
	temp_form.method = "post";
	temp_form.style.display = "none";
	//添加参数
	/*
	for (var item in PARAMTERS) {
		var opt = document.createElement("textarea");
		opt.name = PARAMTERS[item].name;
		opt.value = PARAMTERS[item].value;
		temp_form.appendChild(opt);
	*/
	var opt = document.createElement("textarea");
	opt.name = "photos";
	opt.value = JSON.stringify(PARAMTERS);
	temp_form.appendChild(opt);


	document.body.appendChild(temp_form);
	//提交数据
	temp_form.submit();
}

$().ready(function() {

	$("#button_part_download").click(function(){

		//console.log(this);
		//console.log($("#button_all").href);

		let checkedfiles = new Array();
		$(".pic_op").find('input:checkbox').each(function() { //遍历所有复选框
			//console.log(this);
    		if ($(this).prop('checked') == true) {
				checkedfiles.push($(this).val());
       	 		//console.log($(this).val()); //打印当前选中的复选框的值
   		 	}
		});

		//console.log(checkedfiles);
		//console.log($(this).prop('href'));

		doPost($(this).prop('href'), checkedfiles);
		return false;
    });
	
	$("#button_part_delete").click(function(){
		//console.log(this);
		//console.log($("#button_all").href);

		let checkedfiles = new Array();
		$(".pic_op").find('input:checkbox').each(function() { //遍历所有复选框
			//console.log(this);
		    if ($(this).prop('checked') == true) {
				checkedfiles.push($(this).val());
		       	//console.log($(this).val()); //打印当前选中的复选框的值
		   	}
		});

		//console.log(checkedfiles);
		//console.log($(this).prop('href'));

		doPost($(this).prop('href'), checkedfiles);
		return false;
	});

});

</script>
</body>
</html>