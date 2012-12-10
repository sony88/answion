/*+--------------------------------------+
* 名称: common
* 功能: 基础组件、站内插件定制及公用接口
* anwsin官方:http://www.anwsion.com/
* developer:诸葛浮云
* Email:globalbreak@gmail.com 
* Copyright © 2012 - Anwsion社区, All Rights Reserved 
* Date:2012-08-01
*+--------------------------------------+*/
(function($){
	
//拖拽
$.drag = function( flg ){

  var flgs =false,flgx,flgy,
	  s = $( flg );
  
	  s.mousedown(function(e){
		  flgs=true;
		  flgx = e.pageX- parseInt(s.parents('div[class^="i_gloBoxl"]').css("left"));
		  flgy =e.pageY- parseInt(s.parents('div[class^="i_gloBoxl"]').css("top"));
		  
		  var setTimes =  setTimeout(function(){
			  	if(flgs){
					s.parents('div[class^="i_gloBoxl"]').fadeTo(50, 0.8);
					clearTimeout(setTimes);
				}
			  },200)
	  })
	  
	  $(document).mousemove(function(e){
		  var el = s.parents('div[class^="i_gloBoxl"]');
		  if( flgs ){
			  var x,y,maxWidth,minWidth,maxHeight,minHieght;
			   x =e.pageX- flgx;
			   y =e.pageY- flgy;
			   maxWidth = $(window).innerWidth()+parseInt(el.css('margin-left'));
			   minWidth = -(parseInt(el.css('margin-left')));
			   maxHeight = $(window).innerHeight()-parseInt($(el).innerHeight());
			   minHieght = 0;

			  el.css({top:y,left:x})
			  
			  
			  
		  }
	  })
	  
	  .mouseup(function(){
		  flgs = false;
		  s.parents('div[class^="i_gloBoxl"]').fadeTo("fast", 1);
	 });
} ;// end drag

$.alert = function( html ){
	if($('#i_globalContant').length > 0){
		$('#i_globalContant').html('<em class="i_small i_em"></em>'+html);
		return false;
	}else{
		
		var flgElem= $('<div/>')
			.addClass('i_gloBoxl i_alert')
			.attr('id','i_gloBoxl')
			.html('<div class="i_glotitle">'+
					'<h3 class="i_prl"><p class="qs_bold i_white">提示信息</p>'+
					'<a title="关闭" onclick="$.closeDefault(this);" class="i_right i_pas i_small i_closed" href="javascript:;">关闭</a></h3>'+
					'</div><div class="i_gloDiv" id="i_globalContant"><em class="i_small i_em"></em>'+html+'</div>')
			.appendTo(document.body);
			$.drag(flgElem.find('div[class^="i_glotitle"]:first'));
	}
};//end alert


$.fn.extend({
	color:function(val){
		return $(this).css('color',val);
	},
	setCursorPosition :function(position){
		if(this.lengh == 0) return this;
		return $(this).setSelection(position, position);
	},
	
	setSelection : function(selectionStart, selectionEnd) {
		if(this.lengh == 0) return this;
		input = this[0];
	
		if (input.createTextRange) {
			var range = input.createTextRange();
			range.collapse(true);
			range.moveEnd('character', selectionEnd);
			range.moveStart('character', selectionStart);
			range.select();
		} else if (input.setSelectionRange) {
			input.setSelectionRange(selectionStart, selectionEnd);
			input.focus();
		}
	
		return this;
	},
	
	focusEnd : function(){
		this.setCursorPosition(this.val().length);
		return this;
	},
	autoTextarea: function(options){
		
		var option = $.extend({
				maxHeight:null,
				minHeight:$(this).height(),
				flg :$(this)
			},options ||{});
			
		return $(this).each(function() {
			$(this).bind('keyup focus',function(event){
				var height,el= $(this);
				el.css('height',option.minHeight);
				if (el[0].scrollHeight > option.minHeight) {
					if (option.maxHeight && el[0].scrollHeight > option.maxHeight) {
						height = option.maxHeight;
						el.css('overflowY','scroll');
					} else {
						height = $.browser.chrome ? (event.type == 'focus' ? (el[0].scrollHeight-el.css('padding-top')-el.css('padding-bottom')):el[0].scrollHeight) : el[0].scrollHeight;
						el.css('overflowY','hidden');
					}
					el.css('height',height);
				}
			});
		});
		
	},
	autogrow :function(options) {
		var shadow = $('#autoGrow').css('line-height',$(this).css('line-height'));		
		return $(this).each(function() {
	
			var $this       = $(this),
				minHeight   = $this.height();
			var update = function() {
				var val = this.value.replace(/\n/g, '<br/>');
	
				shadow.html(val);
				$(this).css('height', Math.max(shadow.innerHeight() + 60, minHeight));
	
			}
	
			$(this).change(update).keyup(update).keydown(update);
	
		})
	
	},
	
	changeElement: function(fn){
		var flgs = $(this);
		if($.browser.msie && Number($.browser.version) <= 8){ 
			
		  flgs.bind("propertychange",function(event){ //ie
			 fn == null ? $.noop(): fn.call(this,flgs,event);
		  })
		}else{
		 flgs.bind("input",function(event){ //ff 
			fn == null ? $.noop(): fn.call(this,flgs,event);
		 })
	  }
	  return this;
	}
});

//站内公用链接
var baseUrl = typeof G_BASE_URL == 'undefined' ? '' : G_BASE_URL;

$.extend({
	
	//发起问题...
	elemQs:$('<div/>'),
	
	//小卡片
	mosueBl:true,
	
	maskQs:$('<div/>'),
	
	topArr:[-900,120],
	
	callback:function(fn){
		$.drag($.elemQs.find('div[class^="i_glotitle"]:first'))
		typeof fn == 'function' && fn != null ? fn() : '';
	},
	//快捷键登陆ctrl+enter || enter
	 keyEve: function(fn){
		  $(document).keypress(function(e){
			  var e = e ? e : window.event;
			  
			  //兼容ie 
			  if((e.ctrlKey && e.keyCode == 13) || (e.ctrlKey && e.keyCode == 10) || e.keyCode == 13){
				  if(fn!=null){
					  fn(); 
				  }
			  }
		  });
	 },
	
	//hover类事件公用接口，默认为小卡片
	containerCard: function(options){
		 $.mosueBl = true;
		 var  flg = $.extend({
					  flgs:null,
			mouseenterTime:null,
			mouseleaveTime:null,
				   timeOut:200, //延迟
			eventCardTips:$('#eventCardTips'),
			   mouseleave:'mouseleave',
			   mouseenter:'mouseenter',
				   events:null,
				  eventCard: $.eventCard(options.flgs)
			 },options ||{});
		 
		  if(flg.events.type == flg.mouseenter){
			  flg.mouseenterTime = setTimeout(function(){
				  clearTimeout(flg.mouseleaveTime);
				  $.mosueBl ? flg.eventCard : '';				
			  },flg.timeOut);
		  
		  }else if(flg.events.type == flg.mouseleave){
			  $.mosueBl =false;
			  flg.eventCardTips.bind('mouseenter mouseleave',function(event){
				  event.type == flg.mouseenter ? $.mosueBl =true : ($.mosueBl = false,$(this).hide(),flg.eventCardTips.hasClass('notsMsg') ? flg.eventCardTips.unbind('mouseenter mouseleave').removeClass('notsMsg'):'');
				  
			  })
			  
			  flg.mouseleaveTime = setTimeout(function(){
				  $.mosueBl ? '' : (flg.eventCardTips.hide(),flg.eventCardTips.hasClass('notsMsg') ? flg.eventCardTips.unbind('mouseenter mouseleave').removeClass('notsMsg'):'');
			  },flg.timeOut);
		  }
			
	  },
	  
	//发起 、私信 、问ta 搜索发起该问题..
	
	/*+--------------------------------------+
		[参数赋值说明：]
		flgs:{1:为私信，2:分享[微博、私信、邮件]，3:分享,让更多人看到你的问题，4:为搜索发起该问题，5:编辑回复内容，6:问题重定向，7:举报问题，8:修改话题记录，默认0:为发起问题 }
		Example：$.startQs({flgs:1});
	 *+--------------------------------------+*/
	startQs: function(options){
		var s = $.zero,z =1,
			defaults = $.extend({
				baseUrl:baseUrl,  //url地址
				postHash:typeof G_POST_HASH == 'undefined' ? false: true,
				autoCode:G_QUICK_PUBLISH_HUMAN_VALID == '' ? 0 : G_QUICK_PUBLISH_HUMAN_VALID,  //验证码
				flgs:0
				
			},options || {}),
		
		//高级模式
		quoteHtml = '<a href="javascript:;" onclick="$(\'form#quick_publish\').attr(\'action\', G_BASE_URL + \'/publish/\'); document.getElementById(\'quick_publish\').submit();">高级模式</a>',
		
		//私信
		plHtml = '你还可以输入<big id="msg_num" class="plBigstyle">500</big>个字',
		
		//
		startQsHtml = function(){
			var html = null ,elem;
			
			elem = '<div class="qs_txtare" style="padding-top:5px!important;">'+
					   '<input class="a_txtClass i_glotxtClass i_txtClss" id="privateLetterTXT_id" onfocus="$.focus(this,\'搜索用户\',$.I.privateLetters(this));" name="recipient" onKeyUp="$.I.searchUser(this,event);" value="'+(defaults.username ? defaults.username :'搜索用户')+'" style="display:'+(defaults.username ? 'none':'block')+'" />'+(defaults.username ? '<span id="privateLetter_user_id" data-value="'+defaults.username+'" class="privateLetter_user i_show">'+defaults.username+'<em class="q_editor i_small" title="编辑用户" onclick="$.I.plCilckAddvalue();">编辑用户</em></span>':'')+'</div><div class="qs_txtare"><textarea class="i_glotxtClass" maxlength="500" onfocus="$.focus(this,\'私信内容...\',$.I.privateLetter(this,true));" id="question_privateLetter" name="message" >私信内容...</textarea></div>';
					   
			if(defaults.flgs == 0){ //发起问题
			
				defaults.postHash ? 
				html = '<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />':'';
				
				defaults.username && defaults.user_id ? 
				html +='<input type="hidden" name="ask_user_id" value="'+defaults.user_id+'" />':'';
	 
				//内容部分
				html += 
				'<div class="qs_txtare" style="padding-top:0 !important"> <textarea class="i_glotxtClass advanced_editor" maxlength="200" style="min-height:60px;" id="startQSpts" onfocus="$.focus(this,\'发起问题的标题...\',$.Q.replenish(this,true));" name="question_content" title="发起问题的标题...">'+(defaults.search ? defaults.search :'发起问题的标题...')+'</textarea></div>';
				
				//添加话题
				html +='<div class="qs_txtare"><span onclick="$(\'#startMatter\').toggle();" title="添加问题描述" class="q_editor i_small" style="visibility:visible;margin-left:0;">添加问题描述 »</span><textarea class="i_glotxtClass i_hide " id="startMatter" style="margin-top:10px;" onfocus="$.focus(this,\'\',$.Q.replenish(this));" name="question_detail" title="对此问题进行补充说明..."></textarea></div>';
				
				//选择分类
				html += defaults.category_enable > 0 ?
				'<div class="qs_txtare"><div class="a_qsAskQuestion">'+
					'<div class="a_qsClassList i_prl">'+
						'<span onclick="$.Q.selectd({fg:this,category_id_qs:'+(defaults.category_id!=''&& defaults.category_id > 0 ? defaults.category_id : '0')+'});" id="category_container_start" class="a_qsSelect  a_txtClass i_glotxtClass i_prl"><big class="i_gltxtHide" id="qs_select" data-type="-1">选择分类</big><em id="qs_arr" class="i_pas i_small" title="选择分类"></em></span><input type="hidden" id="category_id" value="" name="category_id">'+
						'<div id="qs_data_list" class="i_data_list i_pas">'+
						'</div>'+
				  '  </div>'+
				  '</div>'+
				'</div>'+(defaults.topic_title ? '<input type="hidden" value="'+defaults.topic_title+'" name="topics[]">':''):'<div class="qs_txtare"><div class="q_topicManage"><span id="i_PublicTopic_pop" class="q_topicMuster">'+(defaults.topic_title ? '<a style="" data-value="'+defaults.topic_title+'" class="i_glotopic i_prl pd">'+defaults.topic_title+'<em title="删除" onclick="$.topic.deleted_topic(this,event,true,null,\'_pop\'); return false;" class="handle i_pas">×</em><input type="hidden" value="'+defaults.topic_title+'" name="topics[]"></a>':'')+'</span><span id="editor_topicBtns_pop" onclick="$.topic.editor_topic({flg:this,make:true,pop:true});" title="话题编辑" class="q_editor i_small" style="visibility:visible;margin-left:0;">添加话题 »</span></div></div>'; //end class="qs_txtare"
	  
				defaults.autoCode > 0 ?
				html += '<div class="qs_txtare"><input type="text" name="seccode_verify" class="i_glotxtClass qs_txtClass" onfocus="$.focus(this,\'验证码\');" value="验证码" >&nbsp;&nbsp;<img id="captcha" onclick="this.src = \'' + defaults.baseUrl + '/account/captcha/rnd-\' + Math.floor(Math.random() * 10000);" title="点击换一张" src="' + defaults.baseUrl + '/account/captcha/" /></a></div>':'';
				
				//[title_txt：标题文字,btn_txt：按钮文字,html：内容区];
				//[qestionHeader(title_txt,btn_txt,html)]
				
				return qestionHeader('发起问题','发起',html);
	  
			}else if(defaults.flgs == 1){ //私信
			
				return qestionHeader('发起私信','发送',elem);
				
			}else if(defaults.flgs == 2){ //分享 邀请
				var elem_html;

				var url = '';

				switch(defaults.share_type)
				{
					//请求分享内容
					case 'question' : 
						url = baseUrl + "/question/ajax/question_share_txt/question_id-" + defaults.target_id;
						share_title = "分享问题";
						break;
					case 'answer' : 
						url = baseUrl + "/question/ajax/answer_share_txt/answer_id-" + defaults.target_id;
						share_title = "分享回复";
						break;
					case 'topic' : 
						url = baseUrl + "/question/ajax/topic_share_txt/topic_id-" + defaults.target_id;
						share_title = "分享话题";
						break;
					default: 
						return false;
				}

				//微博
				elem_html = '<div id="clickEve_v2_0"  class="i_clear"><p style="padding: 15px 0" align="center"><img src="' + G_STATIC_URL + '/common/loading_b.gif" alt="" /></p></div>'; 
				
				if (G_USER_ID > 0)
				{
					//私信
					elem_html += '<div id="clickEve_v2_1" class="i_hide">'+elem+'<div class="qs_txtare i_tr"><a href="javascript:;" onclick="$.endQs();">取消</a>&nbsp;&nbsp;&nbsp;&nbsp;<a title="发送" href="javascript:;" onclick="$(\'#quick_publish\').attr(\'action\', \'' + baseUrl + '/inbox/ajax/send/type-clickEve\');" id="qsSubmite_form1" class="i_replay_but2">发送</a></div></div>'; 
				}
				
				//邮件
				elem_html += '<div id="clickEve_v2_'+(G_USER_ID > 0 ? '2':'1')+'" class="i_hide"><input type="hidden" name="model_type" value="' + defaults.share_type + '"/><input type="hidden" name="target_id" value="' + defaults.target_id + '"/><div class="qs_txtare" style="padding-top:5px!important;">'+
					   '<input class="a_txtClass i_glotxtClass i_txtClss" name="email_address" onfocus="$.focus(this,\'请输入收件人邮箱地址..\');"  value="请输入收件人邮箱地址.." /></div>'+
					   '<div class="qs_txtare"><textarea class="i_glotxtClass" maxlength="500" onfocus="$.focus(this,\'邮件内容...\',$.I.privateLetter(this,true));" name="email_message" >邮件内容...</textarea></div><div class="qs_txtare i_tr"><a href="javascript:;" onclick="$.endQs();">取消</a>&nbsp;&nbsp;&nbsp;&nbsp;<a title="发送" href="javascript:;" onclick="$(\'#quick_publish\').attr(\'action\', \'' + baseUrl + '/question/ajax/send_share_email/\');" id="qsSubmite_form2" class="i_replay_but2">发送</a></div></div>';

				$.get(url, function(result)
				{
					if (result.errno == '1')
					{
						$('#clickEve_v2_0').html('<div id="bdshare" class="bdshare_t bds_tools get-codes-bdshare" data="{text:\'' + result.rsm.share_txt.sns_share +  '\',url:\'' + result.rsm.share_txt.url + '\', \'bdPic\': \'\'}"><a class="bds_qzone">QQ空间</a><a class="bds_tsina">新浪微博</a><a class="bds_tqq">腾讯微博</a><a class="bds_hi">百度空间</a><a class="bds_t163">网易微博</a><a class="bds_tqf">朋友网</a><a class="bds_kaixin001">开心网</a><a class="bds_renren">人人网</a><a class="bds_douban">豆瓣网</a><a class="bds_taobao">淘宝</a><a class="bds_fbook">Facebook</a><a class="bds_twi">Twitter</a><a class="bds_ms">Myspace</a><a class="bds_deli">Delicious</a><a class="bds_linkedin">linkedin</a></div><script type="text/javascript">var bds_config = {\'snsKey\':{\'tsina\':\'' + result.rsm.share_txt.sina_akey + '\', \'tqq\': \'' + result.rsm.share_txt.qq_app_key + '\'}, \'review\':\'off\'}</script><script type="text/javascript" src="http://bdimg.share.baidu.com/static/js/bds_s_v2.js?cdnversion=' + new Date().getHours() + '"></script>');
						G_USER_ID > 0 ? $('#clickEve_v2_1 [name=message]').val(result.rsm.share_txt.message) :'';
						$('#clickEve_v2_'+(G_USER_ID > 0 ? '2':'1')+' [name=email_message]').val(result.rsm.share_txt.mail);
					}
				}, 'json');
				
				return qestionHeader(share_title,null,'<div>'+elem_html+'</div>');
				
			}else if(defaults.flgs == 3){
				var elmHtml =''
				return qestionHeader('让更多人看到你的问题 »',null,'<div>'+elmHtml+'</div>');
				
			}else if(defaults.flgs == 4){
				
				
			}else if(defaults.flgs == 5){ 
				
			elem_html = '<div class="a_question" style="margin:0;padding-bottom:0;">'+
							'<div class="a_qsAsk" style="padding:0;">'+
								'<textarea class="i_glotxtClass i_txtHeight a_qstxt advanced_editor" style="width:596px;" id="advanced_editor_reply" name="answer_content" onfocus="$.focus(this,\'\',$.Q.replenish(this,false,200))"></textarea>'+
								'<div class="a_qsReplenish">'+
									'<div class="i_acceMain" style="padding:3px 0 15px;">'+
										'<div id="file_uploader_question_answer" class="i_uploadMain"></div>'+
									'</div>'+
								'</div>	'+
							'</div>'+
							'<div class="a_qsAsk i_tr">'+
									'<span class="i_anonymity"><input type="checkbox" id="i_do_delete" name="do_delete" value="1" /><label for="i_do_delete">删除回复</label></span>'+
									'<a onclick="ajax_post($(\'#quick_publish\'));" title="确定提交" href="javascript:;" class="i_replay_but2">确定提交</a>'+
							'</div>'+
						'</div>';
				
				return qestionHeader('编辑回复',null,elem_html);	
			}else if(defaults.flgs == 6){
				
				elem_html = '<div class="re_Ction"><h3>将问题跳转至</h3><div class="s_qs"><input type="text" id="question_reset_input" class="i_glotxtClass" onfocus="$.focus(this,\'搜索问题\')" value="搜索问题"></div><p class="give_up"><a href="javascript:;" onclick="$.endQs();" class="i_gray_but">放弃操作</a></p></div>';
				return qestionHeader('问题重定向',null,elem_html);	
			}else if(defaults.flgs == 7){//话题修改记录
				
				return qestionHeader('话题修改记录',null,defaults.html);
			}else if(defaults.flgs == 8){//举报问题
				elem_html = '<input type="hidden" name="type" value="' + defaults.report_type
 + '"/><input type="hidden" name="target_id" value="' + defaults.target_id + '"/><h2 class="i_bold" id="report_reason">举报理由：</h2>';
				
				$.get(baseUrl + '/question/ajax/get_report_reason/', function(result)
				{
					if(result.errno == 1 && result.rsm.length > 0)
					{
						var report_reason = '<select id="report_reason" class="a_txtClass i_glotxtClass" style="height:30px; line-height:30px;" onChange="$(this).next().find(\'[name=reason]\').val($(this).val())"><option value="">请选择</option>';
						
						$(result.rsm).each(function (i, d)
						{
							report_reason += '<option value="' + d + '">' + d + '</option>';
						});
						
						report_reason += '</select>';
						
						$('#report_reason').after(report_reason);
					}
				}, 'json');
				
				//内容
				elem_html += '</select><div class="qs_txtare"><textarea name="reason" id="question_report" onfocus="$.focus(this,\'举报内容...\',$.I.privateLetter(this,true));" maxlength="500" class="i_glotxtClass">举报内容...</textarea></div>';
				//发送
				elem_html +='<div class="qs_txtare i_tr"><a onclick="$.endQs();" href="javascript:;">取消</a>&nbsp;&nbsp;&nbsp;&nbsp;<a id="qsSubmite_form" class="i_replay_but2" onclick="$(\'#quick_publish\').attr(\'action\', \'' + baseUrl + '/question/ajax/save_report/\');" href="javascript:;" title="提交">提交</a></div>';
				return qestionHeader('举报问题',null,elem_html);
			}
			
			
		};
		
		$.elemQs
			.addClass('i_gloBoxl')
			.css({top:$.topArr[z],left:'50%',width:450,marginLeft:-225})
			.html(startQsHtml)
			.appendTo(document.body)
			.bind($.callback(function(){
				var n = defaults.flgs;
				
				switch(n){
					case 0://发起问题
						var startQs = $('#startQSpts');
						startQs.focus().bind('keyup',hide_tips_Err).changeElement($.searchEventQs);
						$('#qs_select').parent().bind('click',hide_tips_Err);
						$('#qsSubmite_form').bind('click',qsSubmite_form); //提交
						//category_id:'3', topic_title:
						defaults.category_id != '' && defaults.category_id > 0 ?
						($('#category_container_start').click()):''; //根据分类id发起问题
						
						//分类标题
						
						if(defaults.username){
							$('#insert_hanger_v2').find('p:first').html('向 '+defaults.username+' 发起问题');
						}
						defaults.search ?( startQs.focusEnd(),$('#i_search').val('') ):'';
					break;
					
					//私信 邮件 微博
					case 1:
					
						!defaults.msg ? '' :  $('#quick_publish').attr('action', baseUrl + '/inbox/ajax/send/');
						$('#qsSubmite_form').bind('click',function(){
							if($('#privateLetterTXT_id').val() =='搜索用户' || $('#privateLetterTXT_id').val().length == 0 ){
								show_tips_Err('发私信对象用户不能为空！');
								return ;
							}else if($('#question_privateLetter').val() == '私信内容...' || $('#question_privateLetter').val().length <= 0){
								show_tips_Err('私信内容不能为空！');
								return ;
							}else{
								ajax_post($('#quick_publish'), _quick_publish_processer);
							}
						});
						
						
					break;
					
					case 2: //分享
						var clickEve_v2_html = '<a href="javascript:;" class="i_small i_topRadius5 cur weibo">站外</a>';
						G_USER_ID > 0 ? clickEve_v2_html += '<a href="javascript:;" class="i_small i_topRadius5 sixin">私信</a>':'';
						clickEve_v2_html += '<a href="javascript:;" class="i_small i_topRadius5 maill">邮件</a>';
						var dClick = $('<p/>')
						.addClass('fx_title i_pas')
						.attr('id','clickEve_v2')
						.html(clickEve_v2_html)
						.appendTo($('#insert_hanger_v2')).bind($.tabs('#clickEve_v2'));
						$(dClick).find('a').eq(G_USER_ID == 0 && defaults.number == 2 ? 1 : defaults.number).click();
						
						$('#qsSubmite_form1').bind('click',form_submit);
						$('#qsSubmite_form2').bind('click',form_submit);

					break;
					case 3: //分享,让更多人看到你的问题
						//do something
					break;
					case 5: //编辑回复
						$.elemQs.css({marginLeft:-340,width:680});
						var txtArea =  $('#advanced_editor_reply');
						
						$.ajax({
							type:'GET',
							url:baseUrl+"/question/ajax/fetch_answer_data/"+defaults.anwsion_id,
							dataType:"json",
							beforeSend: function(){
								
							},
							success: function(s){
								txtArea.val(htmldecode(s.answer_content));
							},
							complete: function(){
								txtArea[0].scrollHeight >= 200 ? txtArea.css({height:150,overflowY:'scroll'}) :'';
							}
						})
						
						init_fileuploader('file_uploader_question_answer', baseUrl + '/publish/ajax/attach_upload/id-answer__attach_access_key-' + ATTACH_ACCESS_KEY);
						
						if ($("#file_uploader_question_answer ._ajax_upload-list").attr('class') && G_UPLOAD_ENABLE == 'Y'){
							$.post(baseUrl + '/publish/ajax/answer_attach_edit_list/', "answer_id=" + defaults.anwsion_id, function (data) {
							if (data['err']){
								return false;
							}else{
								$.each(data['rsm']['attachs'], function(i, v){
									_ajax_uploader_append_file("#file_uploader_question_answer ._ajax_upload-list", v);
								});
							}
							}, 'json');
						}
						
						//修改表单
						$('<input/>').attr('type','hidden').attr('name', 'attach_access_key').attr('value', ATTACH_ACCESS_KEY).appendTo($('#quick_publish').attr('action', baseUrl + '/question/ajax/update_answer/answer_id-' +　defaults.anwsion_id))
						//编辑器
						if (typeof(markdownSettings) != 'undefined'){
							markdownSettings.replyPre=true 
							$('#advanced_editor_reply').markItUp(markdownSettings); 
						}
					break; 
					case 6:
						if(defaults.seif){
							$(defaults.seif).parents('div.i_data_list').hide();
						}
						
						$.question_reset(); //重定向搜索
						
					break;
					//
					case 8:
						$('#qsSubmite_form').bind('click',form_submit);
					
				}
				
				
			}));
				
			//遮罩
			$.maskQs
			.addClass('i_mask i_pas i_alpha_login').attr('id','WS_Mask_x2')
			.css({
				height:$(document).innerHeight(),
				width:$(document).innerWidth()
			})
			.appendTo(document.body)
			.bind('click',function(event){
				$(this).unbind('click');
				$('#WS_close_x2').click();
			})
			
			
		
		//

		function form_submit(){
			ajax_post($('#quick_publish'), _pm_processer);
		}

		function qestionHeader(title_txt,btn_txt,html){
			
			var m = 
			'<form action="' + defaults.baseUrl + '/publish/ajax/publish_question/" method="post" id="quick_publish" onsubmit="return false">' + 
			'<div class="i_glotitle">'+
			'<h3 class="i_prl" id="insert_hanger_v2"><p class="qs_bold i_white">'+title_txt+'</p>'+
			'<a href="javascript:;" class="i_right i_pas i_small i_closed" id="WS_close_x2" onclick="$.endQs();" title="关闭">关闭</a></h3></div>'+
			'<div class="i_gloDiv">'+
			'<p class="i_global_err i_small i_setErr i_err_bg i_hide" id="tips_Err"><em class="e"></em><span id="glo_Err">请选择分类</span></p>'+html;
			
			if(btn_txt != null){
				m+= '<div class="qs_txtare i_tr">';
				
				//发起
				if(defaults.flgs == 0){
					m+= '<span class="i_left">'+quoteHtml+'</span>';
				}else if(defaults.flgs == 1){
					m+= '<span class="i_left">'+plHtml+'</span>';
				}
				
				m += '<a href="javascript:;" onclick="$.endQs();">取消</a>&nbsp;&nbsp;&nbsp;&nbsp;';
				m +='<a title="'+btn_txt+'" href="javascript:;" id="qsSubmite_form" class="i_replay_but2">'+btn_txt+'</a></div>'; //end qs_txtare
			}
			
			m +='</div>'+ //end  i_gloDiv
			'</form>';
			
			return m;
		}
		
		//发起问题提交表单检测
		function qsSubmite_form(){
			if($('#startQSpts').val() == '发起问题的标题...'){
				
				show_tips_Err('发起问题标题不能为空！');
				return ;
			}/*else if($('#qs_select').attr('data-type') == -1){
				
				show_tips_Err('请选择问题分类！');
				return ;
			}*/else{
				ajax_post($('#quick_publish'), _quick_publish_processer);
			}
		}
		function show_tips_Err( html){
			$('#tips_Err').fadeIn('slow').find('span#glo_Err').html( html );
		}
		
		function _quick_publish_processer(result) {
			if (typeof(result.errno) == 'undefined'){
				show_tips_Err(result);
			}
			else if (result.errno != 1){
				show_tips_Err(result.err);
			}else{		
				if (result.rsm && result.rsm.url){
					window.location = decodeURIComponent(result.rsm.url);
				}else{
					window.location.reload();
				}
			}
		}
		
		function _pm_processer(result) {
			if (typeof(result.errno) == 'undefined'){
				show_tips_Err(result);
			}
			else if (result.errno != 1){
				show_tips_Err(result.err);
			}else{		
				$.alert(result.err);
				$.endQs();
			}
		}
		
		function hide_tips_Err(){
			$('#tips_Err').hide();
		}

	},
	
	//关闭提示框
	endQs:function( flg ){
		
		$.elemQs
			.animate({opacity:0},'fast',function(){
				$(this).unbind($.callback()).remove();
				$.maskQs.remove();
				typeof markdownSettings == 'undefined' ? '':	
				markdownSettings.replyPre ? markdownSettings.replyPre =false :'' //编辑回复预览
			});
	},
	
	closeDefault:function(flg){
		var parents = $(flg).parents('div[class$="i_alert"]');
		$(parents).remove();
	},
	
	cacheData:[],
	//小卡片 
	eventCard:function( x,flgs ){
		
		var elem,left,top,num,eventCardTips,
			flg = $.extend({
				   msg:x.attr('data-message'), 
				    t1:1,
				    t2:2,
				   uid:null,		  //用户或话题id
			user_topic:null,          //用户或话题
			   userUrl:baseUrl + '/people/ajax/user_info/',
			  topicUrl:baseUrl + '/topic/ajax/topic_info/',
		 	  domWidth:$(document).innerWidth(),
			 domHeight:$(window).innerHeight(),
		  domScrolltop:$(document).scrollTop(),
			
			//小卡片
			addDomCard : function(elem){
				
				num = flg.user_topic == 'topic' ? 270 : 300;
				flg.domWidth - (x.offset().left+ num) < 70 ? 
					left = x.offset().left- num + x.innerWidth():
					left = x.offset().left;
				
				if($('#eventCardTips') && $('#eventCardTips').length > 0){
					eventCardTips = $('#eventCardTips');
					eventCardTips
					.attr('class',flg.user_topic == 'topic' ? 'i_callingCard_tips i_callingCard_topic': 'i_callingCard_tips')
					.css({left:left,top:x.offset().top+ x.innerHeight()+2})
					.html(elem)
					.after(function(){
						if(flg.domHeight+flg.domScrolltop-x.offset().top < eventCardTips.innerHeight()+30){
							eventCardTips.css('top', x.offset().top -eventCardTips.innerHeight()-5);
						}
						$(this).show();
					})
				}else{
					//
					$('<div/>')
					.css({left:left,top:x.offset().top+ x.innerHeight()+2})
					.attr('class',flg.user_topic == 'topic' ? 'i_callingCard_tips i_callingCard_topic': 'i_callingCard_tips')
					.attr('id','eventCardTips')
					.html(elem)
					.appendTo(document.body);
				
				}
			},
			
			//用户跟话题公用
			ajaxSet: function(url,data){
				$.ajax({
						type:'GET',
						url:url,
						data:data,
						dataType:'json',
						beforeSend: function(){
							flg.addDomCard('<p style="color:#333;">请稍后，正在加载...</p>');
						},
						success: flg.callBack
				});
			},
			
			//用户
			 user_html: function(el){
					elem = '<div class="i_mod">';
							  
						elem += '<a class="i_userHead" href="'+el.url+'"><img alt="'+el.user_name+'" title="'+el.user_name+'" src="'+el.avatar_file+'"></a>';
						
						elem += '<p class="i_userName i_gltxtHide"><a href="'+el.url+'" title="点击进入'+el.user_name+' 的首页">'+el.user_name+(el.verified == 1 ? '<em title="已认证" class="v i_small"></em>':'')+'</a></p>';
						
						elem += '<p class="i_info">威望 <span class="i_linkGreen">'+el.reputation+'</span>&nbsp;&nbsp;• 赞同  <span class="u_linkCly">'+el.agree_count+'</span><!--<em title="top50" class="c_topten i_small"></em>--></p></div>';
					
					//签名
						elem += el.signature== null || el.signature == '' ? '<p class="i_autograph i_gltxtHide">暂无签名...</p>' : '<p title="'+el.signature+'" class="i_autograph i_gltxtHide">'+el.signature+'</p>';
					
					if (el.uid != G_USER_ID && G_USER_ID > 0){
							elem += '<div class="i_bside">';
								elem += '<p class="i_right">';
					
									elem += '<a title="给ta发私信..." href="javascript:;" onclick="$.startQs({flgs:1,username:\''+el.user_name+'\',msg:true});">私信</a><a title="向ta发起提问..." onclick="$.startQs({username:\''+el.user_name+'\',user_id:'+el.uid+',category_enable:'+el.category_enable+'}); return false;" href="javascript:;">问Ta</a></p>';
										elem += '<a title="'+(el.focus ? '取消关注':'关注Ta')+'" class="i_green_bt2 '+(el.focus ? 'cur':'')+'" href="javascript:;" onclick="follow_people($(this), $(this), ' + el.uid + ');$.bufferMom('+el.focus+',\''+el.type+'\','+el.uid+');">'+(el.focus ? '取消关注':'关注')+'</a></div>';
						}else if(el.uid == G_USER_ID){
							elem += '<div class="i_bside">我自己</div>';
						}else if(G_USER_ID == 0){
							
							elem += '<div class="i_bside">要关注、联系本人请先<a href="'+baseUrl+'/account/login/">登录</a>或<a href="'+baseUrl+'/account/register/">注册</a></div>'; 
						}
					
			},
			
			 //话题
			 topic_html: function(el){
					//话题资料
					elem = '<div class="i_mod"><a class="i_userHead" href="'+baseUrl+'/topic/'+el.topic_id+'"><img alt="'+el.topic_title+'" title="'+el.topic_title+'" src="'+el.topic_pic+'"></a><p class="i_userName"><a href="'+baseUrl+'/topic/'+el.topic_id+'">'+el.topic_title+'</a></p><p title="'+el.topic_description+'" class="i_info">'+((el.topic_description).substring(0,28))+'...</p></div>';
					
					//关注
						elem +='<div class="i_bside"><p class="'+(G_USER_ID > 0 ? 'i_right' : '')+' fx">问题数 '+el.discuss_count+'  •  关注者 '+el.focus_count+'</p>';
					
					if (G_USER_ID > 0){
						elem += '<a title="'+(el.focus==1 ? '取消关注':'关注')+'" class="i_green_bt2 '+(el.focus==1 ? 'cur':'')+'" href="javascript:;" onclick="focus_topic($(this), $(this), ' + el.topic_id + ');$.bufferMom('+el.focus+',\''+el.type+'\','+el.topic_id+');">'+(el.focus==1 ? '取消关注':'关注')+'</a></div>';
					}
					
					
			},
			callBack:function(result){
				var s = result;
					if(flg.user_topic == 'topic'){
						flg.topic_html(s);
						$.cacheData.push({
						   "topic_id":s.topic_id,               //话题ID           
						"topic_title":s.topic_title,            //话题标题
				  "topic_description":s.topic_description,      //话题描述
						  "topic_pic":s.topic_pic,              //话题logo
						"focus_count":s.focus_count,            //关注人数
							  "focus":s.focus,                  //关注
					  "discuss_count":s.discuss_count,          //问题数
							   "type":s.type                    //话题
						  });
					}else{
						flg.user_html(s);
						$.cacheData.push({
							  "uid":s.uid,                //用户ID           
						"user_name":s.user_name,          //用户名
						      "url":s.url,          	  //用户链接
					  "avatar_file":s.avatar_file,        //用户头像
							 "area":s.area,               //用户地域
							  "job":s.job,                //用户职业
						"signature":s.signature,          //用户签名
						 "integral":s.integral,           //用户积分
					  "award_count":s.award_count,        //获奖次数
					   "reputation":s.reputation,         //威望
					  "agree_count":s.agree_count,        //赞同
					 "thanks_count":s.thanks_count,       //感谢
							"focus":s.focus,              //关注
							"is_me":s.is_me,              //本人
							 "type":s.type,               //用户名片夹
				  "category_enable":s.category_enable,    //选择分类
					     "verified":s.verified            //认证
					  
					  });
					}
					
					flg.addDomCard(elem); //插入DOM
			}
			
			
			
		},flgs||{});
			
		if(flg.msg != null && flg.msg.indexOf('&') >= 0){
			var k , n , el = true ,
				flgAlt = flg.msg.split('&');
				
			flg.uid = flgAlt[flg.t1].split('uid=')[flg.t1];  //取id
			flg.user_topic = flgAlt[flg.t2].split('card=')[flg.t1]; //检测是用户还是话题
			
			//检测是否被缓存
			if($.cacheData.length > 0){
				for(k in $.cacheData){
					n = $.cacheData[k];
					if(n.uid == flg.uid && flg.user_topic =='user'){
						flg.user_html(n);
						flg.addDomCard(elem);
						return false;
					}else if(n.topic_id == flg.uid && flg.user_topic == 'topic'){
						flg.topic_html(n);
						flg.addDomCard(elem);
						return false;
					}
				}
			} // end if 
			
			flg.user_topic == 'topic' ? flg.ajaxSet(flg.topicUrl,'topic_id-'+flg.uid) : flg.ajaxSet(flg.userUrl,'uid-'+flg.uid);
			
	  }else{
		  $('#eventCardTips').hide()
		
	  }
		
	}, //end eventCard
	
	//关注缓存
	bufferMom:function(focus,el,id){
		if($.cacheData.length > 0){
			for(k in $.cacheData){
				n = $.cacheData[k];
				if(Number(n.uid) == id && n.type == el){
					n.focus = focus == 0 ? 1 : 0;
					return false;
				}else if(Number(n.topic_id) == id && n.type == el){
					n.focus = focus == 0 ? 1 : 0;
					return false;
				}
			}
		}
			
	}
		
});	

})(jQuery);

// 算子
(function(_$){

	_$.extend( _$.easing,{
		def: 'easeOutQuad',
		swing: function (x, t, b, c, d) {
			return _$.easing[_$.easing.def](x, t, b, c, d);
		},
		easeInQuad: function (x, t, b, c, d) {
			return c*(t/=d)*t + b;
		},
		easeOutQuad: function (x, t, b, c, d) {
			return -c *(t/=d)*(t-2) + b;
		},
		easeInOutQuad: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t + b;
			return -c/2 * ((--t)*(t-2) - 1) + b;
		},
		easeInCubic: function (x, t, b, c, d) {
			return c*(t/=d)*t*t + b;
		},
		easeOutCubic: function (x, t, b, c, d) {
			return c*((t=t/d-1)*t*t + 1) + b;
		},
		easeInOutCubic: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t*t + b;
			return c/2*((t-=2)*t*t + 2) + b;
		},
		easeInQuart: function (x, t, b, c, d) {
			return c*(t/=d)*t*t*t + b;
		},
		easeOutQuart: function (x, t, b, c, d) {
			return -c * ((t=t/d-1)*t*t*t - 1) + b;
		},
		easeInOutQuart: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t*t*t + b;
			return -c/2 * ((t-=2)*t*t*t - 2) + b;
		},
		easeInQuint: function (x, t, b, c, d) {
			return c*(t/=d)*t*t*t*t + b;
		},
		easeOutQuint: function (x, t, b, c, d) {
			return c*((t=t/d-1)*t*t*t*t + 1) + b;
		},
		easeInOutQuint: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t*t*t*t + b;
			return c/2*((t-=2)*t*t*t*t + 2) + b;
		},
		easeInSine: function (x, t, b, c, d) {
			return -c * Math.cos(t/d * (Math.PI/2)) + c + b;
		},
		easeOutSine: function (x, t, b, c, d) {
			return c * Math.sin(t/d * (Math.PI/2)) + b;
		},
		easeInOutSine: function (x, t, b, c, d) {
			return -c/2 * (Math.cos(Math.PI*t/d) - 1) + b;
		},
		easeInExpo: function (x, t, b, c, d) {
			return (t==0) ? b : c * Math.pow(2, 10 * (t/d - 1)) + b;
		},
		easeOutExpo: function (x, t, b, c, d) {
			return (t==d) ? b+c : c * (-Math.pow(2, -10 * t/d) + 1) + b;
		},
		easeInOutExpo: function (x, t, b, c, d) {
			if (t==0) return b;
			if (t==d) return b+c;
			if ((t/=d/2) < 1) return c/2 * Math.pow(2, 10 * (t - 1)) + b;
			return c/2 * (-Math.pow(2, -10 * --t) + 2) + b;
		},
		easeInCirc: function (x, t, b, c, d) {
			return -c * (Math.sqrt(1 - (t/=d)*t) - 1) + b;
		},
		easeOutCirc: function (x, t, b, c, d) {
			return c * Math.sqrt(1 - (t=t/d-1)*t) + b;
		},
		easeInOutCirc: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return -c/2 * (Math.sqrt(1 - t*t) - 1) + b;
			return c/2 * (Math.sqrt(1 - (t-=2)*t) + 1) + b;
		},
		easeInElastic: function (x, t, b, c, d) {
			var s=1.70158;var p=0;var a=c;
			if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
			if (a < Math.abs(c)) { a=c; var s=p/4; }
			else var s = p/(2*Math.PI) * Math.asin (c/a);
			return -(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
		},
		easeOutElastic: function (x, t, b, c, d) {
			var s=1.70158;var p=0;var a=c;
			if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
			if (a < Math.abs(c)) { a=c; var s=p/4; }
			else var s = p/(2*Math.PI) * Math.asin (c/a);
			return a*Math.pow(2,-10*t) * Math.sin( (t*d-s)*(2*Math.PI)/p ) + c + b;
		},
		easeInOutElastic: function (x, t, b, c, d) {
			var s=1.70158;var p=0;var a=c;
			if (t==0) return b;  if ((t/=d/2)==2) return b+c;  if (!p) p=d*(.3*1.5);
			if (a < Math.abs(c)) { a=c; var s=p/4; }
			else var s = p/(2*Math.PI) * Math.asin (c/a);
			if (t < 1) return -.5*(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
			return a*Math.pow(2,-10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )*.5 + c + b;
		},
		easeInBack: function (x, t, b, c, d, s) {
			if (s == undefined) s = 1.70158;
			return c*(t/=d)*t*((s+1)*t - s) + b;
		},
		easeOutBack: function (x, t, b, c, d, s) {
			if (s == undefined) s = 1.70158;
			return c*((t=t/d-1)*t*((s+1)*t + s) + 1) + b;
		},
		easeInOutBack: function (x, t, b, c, d, s) {
			if (s == undefined) s = 1.70158; 
			if ((t/=d/2) < 1) return c/2*(t*t*(((s*=(1.525))+1)*t - s)) + b;
			return c/2*((t-=2)*t*(((s*=(1.525))+1)*t + s) + 2) + b;
		},
		easeInBounce: function (x, t, b, c, d) {
			return c - _$.easing.easeOutBounce (x, d-t, 0, c, d) + b;
		},
		easeOutBounce: function (x, t, b, c, d) {
			if ((t/=d) < (1/2.75)) {
				return c*(7.5625*t*t) + b;
			} else if (t < (2/2.75)) {
				return c*(7.5625*(t-=(1.5/2.75))*t + .75) + b;
			} else if (t < (2.5/2.75)) {
				return c*(7.5625*(t-=(2.25/2.75))*t + .9375) + b;
			} else {
				return c*(7.5625*(t-=(2.625/2.75))*t + .984375) + b;
			}
		},
		easeInOutBounce: function (x, t, b, c, d) {
			if (t < d/2) return _$.easing.easeInBounce (x, t*2, 0, c, d) * .5 + b;
			return _$.easing.easeOutBounce (x, t*2-d, 0, c, d) * .5 + c*.5 + b;
		}
	});
})(jQuery);
