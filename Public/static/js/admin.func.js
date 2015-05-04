/**
 * 后台公共js方法，函数名称以globel开头
 */

/* 点击按钮直接上传 */
window.globelUploadOnClick = {
	action      : null,  //上传地址
	id          : null,
	onload      : false,
	callbackfunc: null,
	checkfunc   : null,
	dialog      : null,

	//初始化方法，前两个参数一般为必传参数
	init: function(action, callback, check){
		//关闭弹出层
		if(this.dialog){
			$(this.dialog).dialog('close');
			this.dialog = null;
		}
		if(this.id) $('#' + this.id).remove();
		
		this.id           = 'globel_upload_with_click_div_' + new Date().getTime();
		this.onload       = false;
		this.callbackfunc = null;
		this.checkfunc    = null;
		

		if(action === null){
			action = this.action;
		}else if(typeof action == 'function'){
			check  = action;
			action = this.action;
		}
		if(!action || typeof action != 'string'){
			$.messager.alert('提示信息', '未设置上传地址！', 'error');
			return false;
		}

		var html = [];
		html.push('<div id="' + this.id + '" style="display:block;margin:0;padding:0;width:0;height:0;overflow:hidden;">');
		html.push('<iframe onload="globelUploadOnClick.callback(this)" name="ajax_upload_iframe" style="display:none"></iframe>');
		html.push('<form style="padding:15px 10px;text-align:center" method="post" action="' + action + '" enctype="multipart/form-data" target="ajax_upload_iframe">');
		html.push('<input type="file" name="filedata" onchange="globelUploadOnClick.submit(this)" />');
		html.push('</form>');
		html.push('</div>');
		
		$(html.join('')).appendTo('body');
		
		if(typeof callback == 'function') this.callbackfunc = callback;
		if(typeof check == 'function')    this.checkfunc    = check;
		
		//IE由于安全限制不允许直接用js选择文件并上传
		if($.browser.msie){
			this.dialog = $('#' + this.id).dialog({
				title: '请选择文件',
				iconCls: 'icons-application-application_form_add',
				width: 280,
				modal: true
			});
		}else{
			$('#' + this.id).find('form > input[type="file"]').eq(0).click();
		}
		return false;
	},

	//表单提交方法(内部调用)
	submit: function(that){
		//关闭弹出层
		if(this.dialog){
			$(this.dialog).dialog('close');
			this.dialog = null;
		}
		
		var check = true;
		//验证上传文件函数
		if(typeof this.checkfunc == 'function'){
			if(!this.checkfunc($('#' + this.id).find('form > input[type="file"]').eq(0).val())){
				check = false;
			}
		}
		//验证通过后直接上传
		if(check){
			this.onload = true;
			try{
				$(that).parent('form').eq(0).submit();
				$.messager.progress({text:'正在上传，请稍候...'});
			}catch(e){
				$('#' + this.id).remove();
				$.messager.alert(e.message);
			}
			
		}
	},

	//表单提交回调方法(内部调用)
	callback: function(that){
		if(!this.onload) return false;
		
		$.messager.progress('close');
		
		var text = that.contentWindow.document.body.innerHTML;
		$('#' + this.id).remove();
		
		try{
			var obj = $.parseJSON(text);
		}catch(e){}
		
		if(!obj){
			$.messager.alert('提示信息', '数据返回格式有误', 'error');
			return false;
		}
		
		//上次成功后执行回调函数
		if(typeof this.callbackfunc == 'function') return this.callbackfunc(obj);
	}
};