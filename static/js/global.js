/*+--------------------------------------+
* 名称: global
* 功能: 相关效果处理
* anwsin官方:http://www.anwsion.com/
* developer:诸葛浮云
* Email:globalbreak@gmail.com 
* Copyright © 2012 - Anwsion社区, All Rights Reserved 
* Date:2012-08-01
*+--------------------------------------+*/

(function($){
	
	//回复框
	var elements = $('<div/>');
	
	$.extend({
		
		zero: 0,
		
		//高级发起 > 选择分类 
		classArray : [],
		
		//搜索公用下拉
		data_list:$('<div/>'),
		
		//首页相关
		I:{
			//头部搜索
			width:400,
			callFun:function(){
				var s =$('#i_search') ,da = $('#data_lsit2'),  left = parseInt(s.offset().left)-122;
				
				da.css({left:left})
				
				$('#default_bd').length > 0 ? 
				$(document).unbind('scroll resize') : ($(document).bind('scroll resize',function(){
				da.css({
					left:left,
					top:s.offset().top +s.innerHeight()+6
				})
			}))
			
			},
			searchs: function(data){
				var el =  $('#i_search'),e = this,
				//
					callback = {
						elem: '<div id="search_global"><p class="s">输入关键字进行搜索...</p>'+
							  (G_USER_ID == 0 ? '' : '<p class="qs"><a class="i_green_but i_right" href="javascript:;" onclick="$.startQs({category_enable:'+data.attr('data-category_enable')+',search:\''+(el.val()=='搜索问题、话题或人'? '': el.val())+'\'});$(\'#data_lsit2\').hide();">发起问题</a></p>')+
							  '</div>'
					};
					
					
				el.focus(function(){
					var fg = $(this);
					fg.val() != '搜索问题、话题或人' ? '' :
					fg.val(''),
					$.dataList({flgs:el,html:callback.elem,parentsWidth:e.width},e.callFun),
					$('#data_lsit2').unbind('mouseenter mouseleave');
					
				})
				
				.blur(function(){
					var el = $(this);
					el.val() == '' ? 
					setTimeout(function(){ 
						el.val('搜索问题、话题或人');
					},200) :'';
					
				});

				return this;
			},
			
			search_global:function(){
				var el = $(this),index = 0,val = el.val();
				if(val.indexOf('>') >= 0 || val.indexOf('<') >= 0){
					val = val.replace(/</ig, '&lt;').replace(/>/ig, '&gt;');
				}
				
				var resultArr = [],
					es= true,
				    flg = {
						f0:'<p class="s">输入关键字进行搜索...</p>',
						f1:'<p class="s">请输入两个以上关键字...</p>',
						f2:'<p class="s" data-url="nothing">抱歉！暂时没有相关结果...</p>',
						f3:'<p class="s">请稍后，正在加载...</p>',
						elem: (G_USER_ID!=0 ? '<p class="qs" data-url="startQs"><a class="i_green_but i_right" href="javascript:;" onclick="$.startQs({category_enable:'+el.attr('data-category_enable')+',search:\''+val+'\'});$(\'#data_lsit2\').hide();">发起问题</a><b class="i_gltxtHide">'+val+'</b></p>':''),
						randomsort: function(a,b){
							return Math.random()>.5 ? -1 : 1;
						}
					};
				
				
				switch($.trim(val).length){
					case 0 :
						$.dataList({
							flgs:el,
							html:'<div id="search_global">'+flg.f0+flg.elem+'</div>'
							,parentsWidth:$.I.width
						},$.I.callFun)
						return ;
					break;
					case 1:
						$.dataList({
							flgs:el,
							html:'<div id="search_global">'+flg.f1+flg.elem+'</div>',
							parentsWidth:$.I.width
						},$.I.callFun)
						return ;
					break;
					default :
						if(val != '搜索问题、话题或人'){
							
							var elSval = '<p data-message="'+val+'" data-url="搜索"><a href="javascript:;" onclick="$(\'#global_search_btns\').click();">搜索：<b>'+val+'</b></a></p>';
							
							$.ajax({
								type:'GET',
								url:G_BASE_URL+'/search/ajax/search/?q='+encodeURIComponent(val)+'&limit=5',
								dataType:'json',
								success: function(result){
									
									if(result && result.length > 0){
										$(el).unbind('keyup');index = 0, es = true;
										
										$(result).each(function(ix, element) {
											var elemArr,idex = $(result)[ix],len = Number(idex.type);
											if(ix < 10 ){
												switch(len){
													//问题
													case 1:
														elemArr = '<p data-message="'+idex.name+'" data-url="'+G_BASE_URL+'/'+idex.url+'">'+(idex.detail.best_answer <= 0 ? '' : '<span class="best_answer" title="最佳回复"></span>')+
																  '<a class="s_txt_qs i_gltxtHide" href="'+G_BASE_URL+'/'+idex.url+'"  title="'+idex.name+'">'+(idex.name.replace(new RegExp(val,"ig"),'<b>'+val+'</b>'))+'</a>'+
																  '<span class="xs i_cl_02 i_gltxtHide">'+(idex.detail.answer_count == null ? 0 : idex.detail.answer_count)+' 个回复</span></a></p>';
													break;
													
													//话题
													case 2:
														elemArr = '<p data-message="'+idex.name+'" data-url="'+G_BASE_URL+'/'+idex.url+'"><a class="i_glotopic user_msg" href="'+G_BASE_URL+'/'+idex.url+'" data-message="&uid='+idex.sno+'&card=topic">'+idex.name+'</a>'+
														          '<span class="xs i_cl_02 i_gltxtHide">'+(idex.detail.discuss_count == null ? 0 : idex.detail.discuss_count)+'个问题</span></p>';
													break;
													
													//用户
													case 3:
														elemArr = '<p data-message="'+idex.name+'" data-url="'+G_BASE_URL+'/'+idex.url+'"><a href="'+G_BASE_URL+'/'+idex.url+'" class="user_header">'+
																  '<img class="user_msg i_radiu3" src="'+idex.detail.avatar_file+'" data-message="&uid='+idex.sno+'&card=user" ></a>'+
																  '<a href="'+G_BASE_URL+'/'+idex.url+'" title="'+idex.name+'">'+idex.name+'</a>'+
																  '<span class="xs i_cl_02 i_gltxtHide">'+(idex.detail.signature == null || idex.detail.signature =='' ? '暂无签名' : idex.detail.signature)+'</span></p>';
													break;
													default:
												}
											}
											
											resultArr.push(elemArr);
											
										})

										//
										$.dataList({
											flgs:el,
											html:'<div id="search_global">'+resultArr.join('')+elSval+flg.elem+'</div>'
											,parentsWidth:$.I.width
										},$.I.callFun)
										
										resultArr =[];
									}else{
										$.dataList({
											flgs:el,
											html:'<div id="search_global">'+flg.f2+flg.elem+'</div>'
											,parentsWidth:$.I.width
										},$.I.callFun)
									}
								},
								complete: function(){
									var flg = el, elemnts = $('#search_global >p');
									  elemnts.eq(index).addClass('cur');
									  var hotKeys = function(keys){
										  var Df = $.extend({
												  key:null,
												  el:null
												  
											  },keys||{});
											  
										  if(Df.key == 38){ //
											  index == 0 ? (index = Df.el.length-(G_USER_ID==0 ? 1 : 2)) : index--;
										  }else if(Df.key == 40){
											  index ==  Df.el.length-(G_USER_ID==0 ? 1 : 2) ? (index = 0) : index++;
										  }
										  Df.el.eq(index).addClass("cur").siblings().removeClass('cur'); 
									  };
									  $(flg).bind('keyup',function(e){
										  var key = e.keyCode;
												switch(key){
													case 38:
													  hotKeys({key:key,el:elemnts});
													  es = false;
													break;
													case 40:
													  hotKeys({key:key,el:elemnts});
													  es = false;
													break;
													case 13:
														var url = elemnts.eq(index).attr('data-url');
														if(url =='搜索'){
															$('global_search_btns').click();
															return ;
														}else if(url =='nothing'){
															$.noop();
															return ;
														}else{
															index == 0 && es ? $.noop() :
													   	    window.location.href = url;
														}
													   index = 0 ;
													   $(flg).unbind('keyup');
													break;
												}
										  })
										  
										  elemnts.hover(function(){
											  var el = $(this),es= false;
											  index = el.index();
											  el.addClass("cur").siblings().removeClass('cur');
											  
										  })
									
								}//end complete
							})//end ajax
							
							
						}
						
					break;
					
				}
				
			},
			
			
			//首页新通知折叠
			msg:function( x,el ){
				var obj = $(x);
				$(obj).parents(el)
				.find('.ex_more')
				.toggle('fast',function(){
					$(obj).attr('title')=='展开»' ? 
					$(obj).attr('title','收起»').html('收起') : 
					$(obj).attr('title','展开»').html('展开');
				});
			}, //end msg
			
			//通知下拉
			msgDropdown:function(el){
				var num = 245;	
				 G_UNREAD_NOTIFICATION == 0 ? ($('#data_lsit2').hide()) :	
				$.get(G_BASE_URL+'/notifications/ajax/list/flag-0__page-0__template-header_list',function(s){
					$.dataList({
						flgs:el,
						html:'<div id="msgDropdown">'+s+'</div>',
						parentsWidth:num
					},function(){
						$('#data_lsit2').css({
							left:parseInt(el.offset().left+el.innerWidth()/2)-(num/2)
						})
					})
				});
			},
			
			//删除通知操作
			removeNotmsg:function(o,id,e){
				var e = e ? e : window.event;
				   e.preventDefault();
				   e.stopPropagation ? e.stopPropagation() : e.returnValue = false;
				$.ajax({
					type:'GET',
					url:G_BASE_URL + '/notifications/ajax/read_notification/notification_id-' + id + '__read_type-1',
					dataType:"json",
					success: function(s){
						  check_notifications();
						  $(o).parents('p').remove();
						  var setTime = setTimeout(function(){
							 if($('#msgDropdown').find('p').length == 0){
								  $('#data_lsit2').hide();
							  }
							  clearTimeout(setTime);
							 },200)
					}
				})
			},
			
			//公告关闭
			notice_msg: function( o ){
				var _msg =  $(o).parents('.notice_msg');
				if($.browser.version==7.0){
					$('.R_sidebar').css('padding-top',$('.R_sidebar').find('.r_indexfiller > h3').innerHeight());
					$('.box_shadow').css('top',$('.R_sidebar').find('.r_indexfiller > h3').innerHeight());
				}
				if($('.notice_msg')[$.zero]){
					$(_msg).fadeOut('fast')
				}else{
					$(o).parents('.i_noticetxt').fadeOut('fast')
				}
			},
			
			//私信textarea
			privateLetter:function(x,flg){
				var num = 500 ,_Num;
				
				$.Q.replenish(x,flg);
				$(x).keyup(function(){
					_Num = num - $(x).val().length;
					$('#msg_num').html(_Num)
					if(_Num< 1 || $(x).val().length > 499){
						$('#msg_but2').attr('disabled',true).attr('class','i_gray_bt2');
					}else{
						
						$('#msg_but2').attr('class','i_green_bt2');
					}
				})
				
			},
			
			//私信
			privateLetters: function (flg){
				
				if($.trim($(flg).val()) == '搜索用户' || $.trim($(flg).val()).length <= 0){
				
					$.dataList({flgs:flg,html:'<p style="height:30px; line-height:30px;padding:0 5px;font-size:12px;">您可以输入关键字搜索...</p>'});
					$(document).unbind('click'); //卸载
				}
				$(flg).blur(function(){
					if( $.trim($(flg).val()).length <= 0){
						$('#data_lsit2').hide();
					}
				})
			},
				
			//私信用户搜索
			searchUser:function(flg,e,y){
				var elem = [];		
					
				if($.trim($(flg).val()).length <=1 ){
					$.dataList({flgs:flg,html:'<p class="s">请输入两个以上关键字...</p>'})
				}else{
					$.ajax({
						type:'GET',
						url:G_BASE_URL + '/search/ajax/search/',
						data:'type-user__q-'+encodeURIComponent($.trim($(flg).val()))+'__limit-10',
						dataType:'json',
						beforeSend: function(){
							$('#data_lsit2').show().html('<p class="s">请稍后，正在加载...</p>');
						},
						success: function(result){
							if(result&& result.length >0) {
								$(result).each(function(index, element) {
									var results= $(result)[index]; 
									elem.push('<p><a href="javascript:;" data-value="'+results.name+'" data-id="'+results.uid+'" title="'+results.detail.signature+'"><img class="i_radiu3 i_pic25" src="'+results.detail.avatar_file+'" /> '+results.name+'</a></p>');
								});
								 $.dataList({flgs:flg,html:elem.join('')}); //公用搜索下拉框
								 
							}else{
								$.dataList({flgs:flg,html:'<p class="s">暂时未找到相关的用户...</p>'})
							}
							
						},
						complete: function(){
							var elemnts = $('#data_list_v2 >p');
							if(elemnts.length > 0){
								elemnts.find('a').bind('click',$(flg).attr('data-rel') &&  
									$(flg).attr('data-rel') == '邀请' ? 
									$.invitation_user :  //站内信邀请
									function(){
										$.I.elemetsClcik(this); //私信
									});
							}
							elemnts.eq([$.index]).addClass('cur');
						}
				  }) //end ajax
			  }//end if 
			  
				if(e.keyCode == 8 && y != 'search'){ //y 邀请搜索
					$.trim($(flg).val()).length <= 0 ? 
					$.I.privateLetters(flg):'';
				} 
				
				
				
				function clearClass(el,i){
					el.each(function(){
							$(this).removeClass('cur');
					})
					el.eq(i).addClass('cur');
				}
			},
			
			//私信用户编辑
		   elemetsClcik :function( flgs ){
			  //privateLetterTXT_id
			  var elem = $(flgs);
			  if($('#privateLetter_user_id').length > 0 ){
				
				$('#privateLetterTXT_id').val(elem.attr('data-value')).hide();
			    $('#privateLetter_user_id')
				.attr('data-value',elem.attr('data-value'))
				.html(elem.attr('data-value')+'<em onclick="$.I.plCilckAddvalue();" title="编辑用户" class="q_editor i_small">编辑用户</em>').fadeIn('slow');
				
			  }else{
				  $('<span/>')
				  .attr('id','privateLetter_user_id')
				  .attr('data-value',elem.attr('data-value'))
				  .addClass('privateLetter_user i_show')
				  .html(elem.attr('data-value')+'<em onclick="$.I.plCilckAddvalue();" title="编辑用户" class="q_editor i_small">编辑用户</em>')
				  .appendTo($('#privateLetterTXT_id').val(elem.attr('data-value')).hide().parent());
			  }
			  elem.parents('#data_lsit2').hide();
		   },
		   
		   plCilckAddvalue:function(){
				$('#privateLetterTXT_id').val($('#privateLetter_user_id').attr('data-value')).fadeIn('slow');
				$('#privateLetter_user_id').hide();  
		   },
			
			//站内赞同操作 公用
			click_eve: function(options){
				var fls = true,sClass, flgs = $.extend({
						flg:null,              //自身
						appNum:0,              //赞同数
						uid:0,                 //跟用户名对应的ID
						username:'未知用户',   //用户名
						ready:0,               //是否已赞或者反对过该问题 {1：为已赞同过，-1：反对过，0：为未操作 }
						answer_id:null        //问题 id
						
					},options ||{});
				
				if(flgs.ready == 1){ //赞同
					sClass = 's_cur_z';
					
				}else if(!flgs.ready == -1){ //反对
					fls = false;
					sClass = 's_cur_f';
					
				}else if(flgs.ready == 0){ //无操作
					fls = null;
					sClass = ''
				}
					
				$('<p/>')
				.addClass('s_xs i_pas')
				.html(
					'<a class="s_zt i_prl '+(fls ? sClass :'')+'" href="javascript:;" onClick="$.I.addCast(this,\''+flgs.username+'\',\''+flgs.answer_id+'\')" data-id="'+flgs.uid+'"><span class="i_tips_black i_pas i_hide"><em></em>赞同回复</span></a>'+
					'<em title="被赞同'+flgs.appNum+'次" data-number="'+flgs.appNum+'" class="s">'+flgs.appNum+'</em>'+
					'<a class="s_fd i_prl '+(fls ? '' : (fls ==null ? '' :sClass))+'" href="javascript:;" onClick="$.I.oppCast(this,\''+flgs.username+'\',\''+flgs.answer_id+'\')" data-id="'+flgs.uid+'"><span class="i_tips_black i_pas i_hide"><em></em>对回复持反对意见</span></a>'
				)
				.appendTo($(flgs.flg).parents('div[class^="s_txt"]').css('min-height','65px'));
				
				var p = $(flgs.flg).parents('div[class^="s_txt"]').find('p[class^="s_endorsepeople"]');
				$(p).find('a[data-id]').length > 0 ? $(p).removeClass('i_hide') : '';
				$(flgs.flg).remove();
				
				return {
					removeElem : function(flg,em,elem,id){
						
						$(elem).find('a[data-id]').each(function(e) {
							if(G_USER_ID == $(this).attr('data-id')){
								var n = $(em).attr('data-number').toString();
								  $(em).attr('data-number',--n)
								  .html(n)
								  .attr('title','被赞同'+n+'次');
							  
								$(this).next('em').length <= 0 ? 
								$(this).parent().find('em:last').remove() : 
								$(this).next('em').remove();
								$(this).hide('slow',function(){
									$(this).remove();
									$(elem).find('a[data-id]').length <=0 ? $(elem).addClass('i_hide') : '';
								})
								
								return ;
							}
						});
						agree_vote(id, '-1');
						
					},
					addElem:function(flg,em,elem,username,id){
						
						elem.hasClass('i_hide') ? elem.removeClass('i_hide') :'' ;
						
						$('<a/>')
						.addClass('i_pas user_msg')
						.attr('data-id',G_USER_ID)
						.attr('href','javascript:;')
						.attr('data-message','&uid='+G_USER_ID+'&card=user')
						
						.css({
							left:'-65px',
							top:'-10px',
							opacity:0
						})
						
						.html(username)
						
						.insertAfter(elem.find('span:first'))
						
						.stop().animate({
							left:$(elem).find('span:first').width(),
							top:0,
							opacity:1
							
						},'slow',function(){
							
							$(this)
							.removeClass('i_pas').removeAttr('style');
							elem.find('a[data-id]').length > 1 ? $('<em/>').html('、').insertAfter(elem.find('a:first')) :'';
							
							var n = $(em).attr('data-number').toString();
								$(em).attr('data-number',++n)
								.html(n)
								.attr('title','被赞同'+n+'次');
								$(elem).removeClass('i_prl');
							
							agree_vote(id, '1');		
						})
					}
				}
			}, 
			
			
			//赞同回复
			addCast: function(flg,username,answer_id){
				var elem = $(flg).parents('div[class^="s_txt"]').find('p[class^="s_endorsepeople"]'),
					em = $(flg).siblings('em:first');
				
					$(elem).addClass('i_prl');
					
				if($(flg).hasClass('s_cur_z')){
					$(flg).removeClass('s_cur_z');
					this.click_eve().removeElem(flg,em,elem,answer_id);
				}else{
					$(flg).addClass('s_cur_z').siblings('a:first').removeClass('s_cur_f');;
					this.click_eve().addElem(flg,em,elem,username,answer_id);
				}
					
			},
			
			//反对意见
			oppCast:function(flg,username,answer_id){
				var elem = $(flg).parents('div[class^="s_txt"]').find('p[class^="s_endorsepeople"]'),
					 em = $(flg).siblings('em:first'),
					 n = $(em).attr('data-number').toString();
					 
				if(n > 0){
					if($(flg).hasClass('s_cur_f')){
						$(flg).removeClass('s_cur_f');
						agree_vote(answer_id, '-1');
					}else{
						
						$(flg).addClass('s_cur_f').siblings('a:first').removeClass('s_cur_z');
						this.click_eve().removeElem(flg,em,elem,answer_id);
					}
				}else if(n == 0){
					$(flg).hasClass('s_cur_f') ? $(flg).removeClass('s_cur_f') :$(flg).addClass('s_cur_f');
					agree_vote(answer_id, '-1');
				}
			} //
			
		}, //end I
		
		//问题页面相关 question
		Q:{
			//评论框
			txtarea: function( flg,mark){
				var elem = $( flg ).parent('div[class^="i_Mits"]').find('div.i_subiv'),
					t = 200,
					max_height = 500;
					min_height = 60;
					
				if( 
					$.trim( $( flg ).val() ).length > 0 ||
					$( flg ).val() !='评论一下...'
				){
					$( flg ).css({
						height:$( flg ).innerHeight() > max_height ? max_height : min_height,
						overflowY:$( flg ).innerHeight() > max_height ? 'scroll' : 'hidden'
						})
					.bind('keypress keyup',function(e){
						var s = $(this);
						if($.trim(s.val()).length > 0 ){
							
							$(elem).children(':eq(1)[class^="i_gray_bt2"]')
							.attr('class','i_green_bt2 ');
						}else{
							$(elem).children(':eq(1)[class^="i_green_bt2"]')
							.attr('class','i_gray_bt2');
						}
						
						var e = e ? e : window.event;
						 
						if(e.ctrlKey && e.keyCode == 13 || e.ctrlKey && e.keyCode == 10){
							
							if($.trim(s.val()).length <= 0){
								$.alert('评论不能为空！');
								return false;
							}else{
								elem.find('a[class*="i_green_bt2"]').click();
							}
						}
						
					})
					
					
					$(elem).fadeIn('slow');
					
				}else{
					$( flg ).animate({height:min_height},t,function(){
						$(elem).fadeIn('slow');
					})
				}
				
				
				$( flg ).blur(function(){
					if($( flg ).val()==''){
						$( flg ).height(20);
						$(elem).hide();
						//$(o).animate({height:20},t);
					}
				})
				this.replenish( flg,mark );
			},
			
			//快速评论动作,user为评论对象的用户名，id为textarea动态生成的ID
			reply:function( S ,user,id ){
				
				if($(S).parents('li').find('div#_userid_'+id).length >0){
					$(S).parents('li')
						.find('div#_userid_'+id)
						.children('form').css('display','block')
						.find('textarea:first')
						.focusEnd();
					 return ;
				}
				 $(elements)
				 	.attr('id','_userid_'+id)
					.html($('#'+id).html())
					.insertAfter($(S).parents('li').children(':last'))
					.children(':first').css('display','block')
					.find('div[class^="i_Mits"]')
					.removeClass('i_subBg')
					.children('div[class^="i_subiv"]').find('a:first').attr('onclick','$.Q.eleCancel()');
				
				 $(elements).find('textarea:first').val('@'+user+' ').focusEnd()
					
					
			},
			
			//取消评论
			eleCancel:function(){
				$(elements).children(':first').slideUp('fast');
			},
			
			//评论箭头处理
			reCallarrows:function(){
				var el = arguments[2];
				$('#'+arguments[1]+'_comments_'+arguments[0])
				.find('em[class^="i_arrows"]:first')
				.css({left:-($(arguments[2]).parent().offset().left - $(arguments[2]).offset().left)+($(arguments[2]).innerWidth()/2)});
				$(el).hasClass('cur') ? $(el).removeClass('cur') : $(el).addClass('cur');
			},
			
			//选择分类  高级发起问题vs快捷发起共用,默认为快捷发起 > 分类
			selectd: function(options){
				var flgs = {
						listId:$('#qs_data_list'),   //下拉框ID
						arrowsId :$('#qs_arr'),      //箭头
						selectId:$('#qs_select'),    //选择分类
						x : ''
					},
				
				 flg = $.extend(flgs,options || {}),
				 gmID = flgs.listId.attr('id')+'_mg_div';
				
				
				if($(flgs.listId).find('div.txtoverflow').length > 0){
					 flgs.listId.toggle();
					 return;
				}else{
					$.ajax({
						type:'POST',
						url:G_BASE_URL+"/publish/ajax/fetch_question_category/",
						dataType:"json",
						beforeSend: function(){
							flgs.listId.fadeIn('slow').html('<p class="s"><a href="javascript:;">请稍后，正在加载...</a></p>');
						},
						success: function(result){
							if(result&& result.length >0) {
								$(result).each(function(index, element) {
									var results= $(result)[index];
									    flgs.x += '<p class="i_list_p" ><a data-list="'+index+'" data-id="'+results.id+'" href="javascript:;" title="'+results.description+'">'+results.title+'</a></p>';
								});
								
								 flgs.listId.show().html('<em class="i_arrows"></em><div class="txtoverflow" id="'+gmID+'">'+flgs.x+'</div>')
								 .find('a').bind('click',selectedClick);
								 
							}else{
								flgs.listId.hide();
							}
						},
						error: function(msg){
							$.alert('选择分类：'+msg)
						},
						
						complete: function(){
							
							//高级发起 
							if(flgs.category_id && flgs.category_id.val() > 0){
								flgs.listId.hide();
								flgs.listId.find('a').each(function(){
									
									$(this).attr('data-id') == flgs.category_id.val() ? flg.selectId.html($(this).text()):''; 
								})
								
							}
							
							if(flgs.category_id_qs > 0){
								flgs.listId.hide();
								flgs.listId.find('a').each(function(){
									
									$(this).attr('data-id') == flgs.category_id_qs ? (flg.selectId.html($(this).text()),$('#category_id').val($(this).text())):''; 
								})
							}
							
						}
						
					})
				} //end if 
					
				
				function selectedClick(){
					var _root = $(this);
					flg.selectId.html(_root.html()).attr('data-type',_root.attr('data-list'));
						flg.listId.fadeOut('fast');
						flg.arrowsId.removeClass('cur');
						$(flg.fg).removeClass('i_cur');
						$('#category_id').val(_root.attr('data-id'));
				}
					
				var domClick = function(e){
					 $.domEvent(e,function(x){
						if($(x).attr('id') != $(flg.listId).attr('id') && 
						   $(x).attr('class') != 'i_list_p' && 
						   x.nodeName !='A' &&
						   $(x).attr('id') != $(flg.arrowsId).attr('id') &&
						   $(x).attr('id') != $(flg.selectId).attr('id')){
							   
								flg.listId.fadeOut('fast');
								flg.arrowsId.removeClass('cur');
								$(flg.fg).removeClass('i_cur');
								$(document).unbind('click',domClick);
						}
					});
				} //end domClick
				
				$(document).bind('click',domClick);
				$(flg.fg).hasClass('i_cur') ? $(flg.fg).removeClass('i_cur') :$(flg.fg).addClass('i_cur');
				flg.arrowsId.hasClass('cur') ?flg.arrowsId.removeClass('cur') :flg.arrowsId.addClass('cur');
			},
			
			//textArea自动扩展,mark屏蔽换行，默认为换行，
			replenish: function(x,mark,h){
				var _v2 = $(x),val,
					S = mark == null || mark == false ? false :mark;
					
				if(S){
					$(x).bind('keyup',function(e){
						var el = $(this);
						if(e.type =='keyup' && el.val().indexOf('\n') > 0){
							$('#startQSpts').val(el.val().replace(/\n/g,'').replace(/^\s+|\s*/g,''));
						}
						
					}).bind('keypress ',function(e){
						 var e = e ? e : window.event;
						 if(e.keyCode == 13){
							 e.preventDefault();
							 e.stopPropagation ? e.stopPropagation() : e.returnValue = false;
						}
					});
				};
					
				$(x).autoTextarea({maxHeight:h==null || h ==''? 500 : h});
			},
			
			//上传附件
			uploadFile: function(){
				if($('#i_uploadFiles')){
					$('#i_uploadFiles').click();
				}
			},
			
			addFlgs:function( flg){
				if($('#answer_content').length > 0){
					$(flg).keyup(function(){
						$('#answer_content').val($(this).text());
					})	
				}
			},
			
			//收藏夹
			favorite:function(answer_id){
					var flgElem = $('<div/>')
						.addClass('i_gloBoxl i_alert')
						.attr('id','i_gloBoxl')
						.html('<div class="i_glotitle">'+
								'<h3 class="i_prl"><p class="qs_bold i_white">收藏</p>'+
								'<a title="关闭" onclick="$.closeDefault(this);" class="i_right i_pas i_small i_closed" href="javascript:;">关闭</a></h3>'+
								'</div>'+
								'<div class="i_gloDiv" id="add_favorite_content">'+
								'<p align="center" style="padding: 15px 0"><img src="' + G_STATIC_URL + '/common/loading_b.gif"/></p>' + 
								
								'</div>')
						.appendTo(document.body);
						$.drag(flgElem.find('div[class^="i_glotitle"]:first'));
						
						$.get(G_BASE_URL + '/favorite/ajax/get_favorite_tags/', function (result) {
							html_data = '<form action="' + G_BASE_URL + '/favorite/ajax/update_favorite_tag/" method="post" onsubmit="return false;"><!--<div class="f_succeed"><em class="i_cl_01 i_small em">收藏成功</em><span class="i_cl_02 s">你可以在 “首页-我的收藏” 中找到它</span></div>-->'+
								'<div class="qs_txtare ">添加话题标签：'+
									'<input type="hidden" name="answer_id" value="' + answer_id + '" />'+
									'<input class="i_glotxtClass" style="width:200px;height:30px;line-height:30px;" name="tags" id="add_favorite_tags" />'+
								'</div>'+
								'<div class="f_topic" id="add_favorite_my_tags">常用标签: ';
								
							$.each(result, function (i, a) {
								html_data += '<a href="javascript:;" onclick="$(\'#add_favorite_tags\').val($(\'#add_favorite_tags\').val() + \'' + a.title + ',\');" class="i_glotopic user_msg">' + a.title + '</a>';
							});
							
							html_data += '</div>'+
								
								'<div class="qs_txtare i_tr">'+
									'<a onclick="$.closeDefault(this);" href="javascript:;">取消</a>&nbsp;&nbsp;&nbsp;&nbsp;'+
									'<a class="i_green_bt2 i_txtmiddle" onclick="ajax_post($(this).parents(\'form\'), _ajax_post_i_alert_processer)" href="javascript:;" title="确定">确定</a>'+
								'</div></form>';
							
							$('#add_favorite_content').html(html_data);
						}, 'json');
				
			}
			
			
		},//end Q
		
		
		/*---------------------------------------------*
		* 话题说明:
		* 1、问题话题 2、高级发起问题添加话题 3、收藏夹话题  共用
		*---------------------------------------------*/
		topic:{
			
			//话题编辑
			editor_topic:function(options){
				if(options.flg == null) return;
				var pop = options.pop ? '_pop': (options.answer_id != null ? options.answer_id : '' ),
					option = $.extend({
						    flg:null,
					topicHolder:$('#i_PublicTopic'+pop),
					topicEditor:$('#editor_input_handle_i_PublicTopic'+pop),
							txt:options.make ? '创建新的话题...':'创建或搜索添加新话题...', //make 为布尔值
							  f:options.make ? true :false,
					  answer_id:null //判断收藏夹话题
						
					},options||{});
					
					//options.pop 区分弹出层上添加话题功能
				
				if(option.topicHolder.length == 0 ) return;
				//
				(option.answer_id == null ? option.topicHolder.find('a') : option.topicHolder.find('span')).each(function(index){
					var el = $(this);
					
					if(el.find('em').length > 0){
						el.addClass('i_prl pd').find('em').show();
						
					}else{
						$('<em/>').addClass('handle i_pas')
						.attr('title','删除').html('×')
						.attr('onclick','$.topic.deleted_topic(this,event,'+option.f+','+option.answer_id+',\''+pop+'\'); return false;')
						.appendTo(el.addClass('i_prl pd'))
					}
				})
				
				
				
				if(option.topicEditor.length > 0){
					option.topicEditor.fadeIn('slow').find(':text').focus();
				}else{
					
					$('<div/>').addClass('editor_input i_hide')
					.attr('id','editor_input_handle_'+option.topicHolder.attr('id'))
					.html('<input type="text" id="editor_input'+pop+'" maxlength="13" onfocus="$.focus(this,\''+option.txt+'\');"  value="'+option.txt+'" class="i_glotxtClass"/>&nbsp;&nbsp;<a class="i_green_bt2" id="establish_topic'+pop+'" href="javascript:;" title="确定添加»" onclick="$.topic.establish_topic('+option.f+','+option.answer_id+',\''+pop+'\');">添加»</a>&nbsp;&nbsp;<a class="i_gray_bt2" '+
					'href="javascript:;" onclick="$.topic.countermand_topic('+option.f+',\'#editor_input_handle_'+option.topicHolder.attr('id')+'\',\'#'+option.topicHolder.attr('id')+'\','+option.answer_id+');">取消</a>')
					.insertAfter(option.topicHolder)
					.fadeIn('slow',function(){
						$(this).find(':text').bind('focus',function(){
							var el = $(this);
							if($.trim(el.val()).length == 0 || el.val() == option.txt){
									
									$.dataList({flgs:el,html:'<p class="s">'+option.txt+'</p>'});	
							}
						}).changeElement(function(){
							$.topic.topicKeyEve($(this),option.f,option.answer_id,pop);
						})
					})
					
				}
				$(option.flg).hide();
			},
			
			//添加话题
			establish_topic:function(txt,qsID,pop){
				
				var tx = txt ? '创建新的话题...':'创建或搜索添加新话题...';
				var el,eleminput;
				
					eleminput = $('#editor_input'+pop);//input

				if($.trim(eleminput.val()).length == 0 || eleminput.val() == tx){
					eleminput.focus();
					$.dataList({flgs:eleminput,html:'<p class="s">不能创建空话题！</p>'});
					
					return ;
				}else if($.trim(eleminput.val()).length >12 ){
					eleminput.focus();
					$.dataList({flgs:eleminput,html:'<p class="s">限 12 个关键字以内...</p>'});
					return ;
				}else{
					this.elemClciks(eleminput,txt,'input',qsID,pop);
				}
				
			},
			
			
			//添加话题
			elemClciks: function (flg,txt,val,qsID,pop){
				var flgval,indexof,comma_v2 =',',comma_v3 ='，',topicSpan,id,trues = true,
				editor_input;
				
				if(val == 'input'){ //input添加话题
					indexof = flg.val();
					if(indexof.indexOf(comma_v2) >= 0){
						flgval = subString(indexof,0,indexof.indexOf(comma_v2));
					}else if(indexof.indexOf(comma_v3) >= 0){
						flgval = subString(indexof,0,indexof.indexOf(comma_v3));
					}else{
						flgval = indexof;
					}
					
					if(flgval ==''){
						flg.val('').focus();
						$.dataList({flgs:flg,html:'<p class="s">输入有误,请重新输入...</p>'});
						return;
					}else if(flgval.length == 1){
						$.dataList({flgs:flg,html:'<p class="s">请输入两个以上关键字</p>'});
						flg.val(flgval).focus();
						return;
					}
				}else{
					txt && val == 'text' ? flgval = flg.attr('title') :'';
				}
				
				topicSpan = $('#i_PublicTopic'+pop);
				editor_input = $('#editor_input'+pop);
				
				if(txt){
					
					  topicSpan.find('a').each(function(){
							var fls = $(this),
							id = fls.attr('data-value');
							id == flgval ? trues = false :'';
							return ;
					  })
					 if(trues){
						 $('<a/>')
						  .css('display','none')
						  .attr('data-value',flgval)
						  .addClass('i_glotopic i_prl pd')
						  .html(flgval+'<em class="handle i_pas" onclick="$.topic.deleted_topic(this,event,true,'+qsID+',\''+pop+'\'); return false;" title="删除">×</em><input type="hidden" name="topics[]" value="'+flgval+'" />')
						  .appendTo(topicSpan)
						  .fadeIn('slow');
						  editor_input.val('').focus();
					 }else{
					 	 $.dataList({flgs: editor_input.val('').focus(),html:'<p class="s">该话题已存在...</p>'});
					 }
					 
				}else{
					if(val != 'input'){
						var msg = Number($(flg).attr('data-message').split('&')[1].split('uid=')[1]);
						topicSpan.find('a').each(function(){
							var fls = $(this),
							id = Number(fls.attr('data-message').split('&')[1].split('uid=')[1]);
							id == msg ? trues = false : '';
							
						})
					}
					
					 if(trues){
						  $.ajax({
							  type:'POST',
							  url:G_BASE_URL + (qsID=='' || qsID== null ? '/question/ajax/save_topic/question_id-'+QUESTION_ID : '/favorite/ajax/update_favorite_tag/'),
							  dataType:"json",
							  data:(qsID=='' || qsID== null ? 'topic_title=' : 'answer_id='+qsID+'&tags=')+(val == 'input' ? flgval : flg.text()),
							  success: function(result){
								  if(result.rsm){
									  if(val == 'input'){
									  	topicSpan.find('a').each(function(){
											var fls = $(this),
											id = Number(fls.attr('data-message').split('&')[1].split('uid=')[1]);
											id == Number(result.rsm.topic_id) ? trues = false :'';
										})
									  }
									  
									  if(trues){
										  $('<a/>')
										  .attr('data-message','&uid='+result.rsm.topic_id+'&card=topic')
										  .attr('href',G_BASE_URL+'/'+result.rsm.topic_url)
										  .css('display','none')
										  .addClass('i_glotopic i_prl pd')
										  .html((val == 'input' ? flgval : flg.text())+'<em class="handle i_pas" onclick="$.topic.deleted_topic(this,event,false,'+qsID+',\''+pop+'\'); return false;" title="删除">×</em>')
										  .appendTo(topicSpan)
										  .fadeIn('slow');
										  $.dataList( {flgs:editor_input.val('').focus(),html:'<p class="s">话题添加成功...</p>'});
									  }else{
									  	 $.dataList( {flgs:editor_input.val('').focus(),html:'<p class="s">该话题已存在...</p>'});
									  }
										
								  }else{
									  $('<span/>')
										  .attr('data-text',(val == 'input' ? flgval : flg.text()))
										  .css('display','none')
										  .addClass('i_glotopic i_prl pd')
										  .html((val == 'input' ? flgval : flg.text())+'<em class="handle i_pas" onclick="$.topic.deleted_topic(this,event,false,'+qsID+',\''+pop+'\'); return false;" title="删除">×</em>')
										  .appendTo(topicSpan)
										  .fadeIn('slow');
										  $.dataList( {flgs:editor_input.val('').focus(),html:'<p class="s">话题添加成功...</p>'});
								  }
								  
							  }//end success
						   }) //end ajax
					  }else{
						   $.dataList( {flgs:editor_input.val('').focus(),html:'<p class="s">该话题已存在...</p>'});
					  }
				 
				}
				
				function subString(str,start,end){
					return str.toString().substring(start,end);
				}
				
			  },
			
			//删除话题
			deleted_topic:function(flg,e,txt,qsID,pops){
				
				if(e.stopPropagation){
					 e.stopPropagation()
				}else{
					window.event.returnValue = false;
				}
				
				
				var flgs =  qsID=='' || qsID== null ? $(flg).parent('a') : $(flg).parent('span'),uid;
				var editor_input = $('#editor_input'+pops);
				
				
				flgs.hide('slow',function(){
					if(txt){
						flgs.remove();
						$('#data_lsit2').length > 0 ? 
						$('#data_lsit2').css({
							left:editor_input.offset().left,
							top:editor_input.offset().top +editor_input.innerHeight()+6,
							position:'absolute'
						}) :'';
					}else{
						 
						 $.ajax({
								type:'POST',
								url:G_BASE_URL + (qsID=='' || qsID== null ?'/question/ajax/delete_topic/': '/favorite/ajax/remove_favorite_tag/'),
								data:(qsID=='' || qsID== null ? ('topic_id='+(flgs.attr('data-message').split('&')[1].split('uid=')[1])+'&question_id='+QUESTION_ID) :('answer_id='+qsID+'&tags='+flgs.attr('data-text'))),
								success: function(result){
									flgs.remove();
									$('#data_lsit2').length > 0 ? 
									$('#data_lsit2').css({
										left:editor_input.offset().left,
										top:editor_input.offset().top +editor_input.innerHeight()+6,
										position:'absolute'
									}) :'';
								}
						}) //end ajax
				   }
				   
					
				})
				
			},
			
			//取消
			countermand_topic:function(txt,topicDivId,topicId,num){
				
				if(!txt){
					(num==''|| num ==null ? $(topicId).find('a >em') : $(topicId).find('span >em')).each(function(){
						var flgs = $(this);
						(num==''|| num ==null ? flgs.fadeOut('slow').parent('a') : flgs.fadeOut('slow').parent('span')).removeClass('i_prl pd');
					})
				}
				
				$(topicDivId).hide().find(':text:eq(0)').val('');
				$(topicDivId).parents().find('span.q_editor').show();
				
			},
			
			topicKeyEve:function( flg,txt,qsID,pop){
				var index = 0;
				var el = $(flg),myFunction,
					defaultic = {
						tx:txt ? '创建新的话题...':'创建或搜索添加新话题...',
						
						hotKeys: function(keys){
							var Df = $.extend({
									key:null,
									el:null
									
								},keys||{});
								
							if(Df.key == 38){ //
								index == 0 ? (index = Df.el.length-1) : index--;
							}else if(Df.key == 40){
								index ==  Df.el.length-1 ? (index = 0) : index++;
							}
							Df.el.eq(index).addClass("cur").siblings().removeClass();
							if(!$.browser.msie){$(flg).val(Df.el.eq(index).find('a').attr('title'));}
						},
						
						elem:[] 
					}
				
				if($.trim(el.val()).length == 0 || el.val() == defaultic.tx){
					$.dataList({flgs:el,html:'<p class="s">'+defaultic.tx+'</p>'});
					return;
				}else if($.trim(el.val()).length == 1){
					$.dataList({flgs:el,html:'<p class="s">请输入两个以上关键字...</p>'});
					return;
				}else if($.trim(el.val()).length > 12){
					$.dataList({flgs:el,html:'<p class="s">限 12 个关键字以内...</p>'});
					return;
				}else{
					if($.trim(el.val()).indexOf(',') >=0 || $.trim(el.val()).indexOf('，') >= 0){
						el.parent().find('#establish_topic'+pop).click();
						return;
					}
					 //if(!txt){
						 $.ajax({
						    type:'GET',
							url:G_BASE_URL + '/search/ajax/search/',
							data:'type-topic_add__q-'+encodeURIComponent($.trim($(el).val()))+'__limit-10',
							dataType:'json',
							beforeSend: function(){
								$.dataList({flgs:el,html:'<p class="s">请稍后，正在加载...</p>'});
							},
							success: function(result){
								var results;
								if(result&& result.length >1) {
									$(flg).unbind('keyup');
									$(result).each(function(index, element) {
										index > 0 && index <=10 ? 
										( results= $(result)[index],
										  defaultic.elem.push('<p><a href="javascript:;" data-message="&uid='+results.detail.topic_id+'&card=topic"  title="'+results.name+'">'+results.name+'</a></p>')) : '';
										  										
									});
									 $.dataList({flgs:el,html:defaultic.elem.join('')}); //公用搜索下拉框
									 elem =[];
									 
								}else{
									if(result[0].exist == 0){
										$.dataList({flgs:el,html:'<p class="s">暂时未找到相关的话题...</p>'});
									}else{
										$('#data_lsit2').hide();
									}
								}
								
							},
							complete: function(){
								var elemnts = $('#data_list_v2 >p') ,a = $('#data_list_v2 a');
								elemnts.eq(index).addClass('cur');
								if(elemnts.length > 0){
									
									a.bind('click',function(){
										 $.topic.elemClciks($(this),txt,'text',qsID,pop);
									})
									
								$(flg).bind('keyup',function(e){
		 							var key = e.keyCode;
										  switch(key){
											  case 38:
											  	defaultic.hotKeys({key:key,el:elemnts});
											  break;
											  case 40:
											  	defaultic.hotKeys({key:key,el:elemnts});
											  break;
											  case 13:
											  	 a.eq(index).click();
												 
												 index = 0 ;
												 $(flg).unbind('keyup');
											  break;
										  }
									})
									
									elemnts.hover(function(){
										var el = $(this);
										index = el.index();
										el.addClass("cur").siblings().removeClass('cur');
										if(!$.browser.msie){
											$(flg).val(el.find('a').attr('title'));	
										}
									})
								}
								
								
							} //end complete
						})//end ajax
					/*}else{
						$.dataList({flgs:el,html:'<p class="s">使用逗号快速添加话题...</p>'});
					}*/
						
				}
				
				
			},
			
			
			//话题修改记录
			hideTopic:function(flg,topic){
				var em = $(flg).find('em');
				em.hasClass('x3') ? em.removeClass('x3') : em.addClass('x3');
				topic.toggle();
			},
			
			//话题发问
			akQues:function(flg){
				var flgs = $(flg),txt = '该问题相关的问题...';
				if(flgs.val() == txt){
					flgs.val('')
					.css('color','#666');
				}
				flgs.parent('p').addClass('i_cur');
				
				flgs.blur(function(){
					var flgs = $(this);
					if(flgs.val() ==''){
						flgs.val(txt)
						.css('color','#999');
					}
					flgs.parent('p').removeClass('i_cur');
				})
			},
			
			//话题内容修改
			container: function(flgs){
				var s = $.extend({
						topicId:$('#topic_contents'),       //话题内容
						topictainer: $('#topic_container'), //话题修改容器
						editorId :$('#editor_topic_txt'),	//话题编辑
						flgElems: $('#topic_container').find('textarea').eq($.zero),
						editor: function(){
							//this.topictainer.show();
							this.editorId.hide();
						    this.flgElems.val( this.topicId.hide().html().replace(/^\s+|\s*/g,'').replace(/<br>+/g,'\n'))
							.parent().show();
							
						},
						elmHide : function(x){
							this.topicId.html( this.flgElems.val().replace(/\n/g,'<br>')).show();
							this.topictainer.hide();
							this.editorId.show()
						}
										 	
					},flgs||{});
					
					if(s.topicId && s.topictainer && s.editorId){
						!s.flg ? s.editor() : s.elmHide();
					}
			}	
		},
		
		//页面加载...
		anwsion: function(){
			var defaultElem = {
					Search: $('#i_search'), //搜索
					userMsg:$('a[class*="user_msg"],img[class*="user_msg"]'),//小卡片
					bd: $('#default_bd'),//blue masterplate
					notNum:$('#notifications_num'), //通知显示条数
					notMsg:$('#not_message'), //绑定通知ID
					dataList:$('#data_lsit2') //下拉框(通知调用)
				};
				
			if(defaultElem.Search.length > 0){
				$.I.searchs(defaultElem.Search);
				this.backTop();
				defaultElem.Search.changeElement($.I.search_global);
			}
			
			//绑定小卡片
			defaultElem.userMsg.live('mouseenter mouseleave',function(event){
				var el = $(this);
				$.containerCard({flgs:el,events:event});
			});
			
			//消息通知跳转定位 全站通用
			if(window.location.hash.indexOf('!') > 0){
				var top = $('a[name='+window.location.hash.split('!')[1]+']').offset().top -(defaultElem.bd.length > 0 ? 0 : 76);
				$("html,body").animate({scrollTop:top},'slow');
			}
			
			//新通知下拉
			defaultElem.notNum.length > 0 && G_USER_ID != 0 ? defaultElem.notNum.attr('data-value') :'';
			Number(defaultElem.notNum) <= 0 ? '' :
			defaultElem.notMsg.bind('mouseenter mouseleave',function(event){
				var data = $('#data_lsit2'), el = $(this);
				data.addClass('notsMsg');
				$.containerCard({
					flgs:el,
					events:event,
					eventCard:$.I.msgDropdown(el),
					timeOut:600,
					eventCardTips:$('.notsMsg')
				});
			});
			
			//选择分类 > 子分类
			if($('#q_category_nav').length > 0 ){
				function q_category_nav(el,child){
					if(child.length > 0){
						$.dataList({
							  flgs:el,
							  html:child.html(),
							  parentsWidth:el.innerWidth()
						})
					}
					
				};
				$('div[class^="q_nav_tx"],#q_category_nav > li').live('mouseenter mouseleave',function(event){
					var el = $(this);
					$.containerCard({
						flgs:el,
						events:event,
						eventCard:q_category_nav(el,el.find('div.i_data_list')),
						eventCardTips:$('#data_lsit2'),
						timeOut:0
					});
				});
			}

		},
		
		//返回顶部
		backTop: function(){
			var domWidth = 960;
			var s = $('<span/>')
				.attr('title','返回顶部')
				.addClass('backTop i_hide')
				.css({left:($(window).width() - domWidth) /2 + domWidth})
				.appendTo(document.body)
				.click(function(){
					$("html,body").animate({scrollTop:$.zero},'slow');
				});
				
				$(window).scroll(function(){
					$(document).scrollTop() > $.zero ? $(s).fadeIn('slow') : $(s).fadeOut('slow');
				});
				$(window).bind('resize',function(){
					s.css({left:($(window).width() - domWidth) /2 + domWidth});
				})
			
		}, //end backTop
		
		//评论
		focus:function(o,txt,fn){
			
			var flgs = $(o);
			if(flgs.val() == txt){
				flgs.val('')
				.css('color','#666');
			}
			flgs.addClass('i_cur');
			
			$(o).blur(function(){
				var flgs = $(this);
				if(flgs.val() ==''){
					flgs.val(txt)
					.css('color','#999');
				}
				flgs.removeClass('i_cur');
			})
			
			fn == null ? '' : fn();
		},
		
		//doc events
		domEvent: function(e,fn){
			var e = e ? e : window.event;
			var tag = e.target || e.srcElement ;
			if(fn!=null){
				fn(tag);
			}
		},
		
		//tab切换 公用
		tabs: function(flgId,fn){
			var elem = $( flgId );
			
			$( flgId +'>a').each(function(i,e) {
                $(this).click(function(){
					$(this).addClass('cur').siblings().removeClass('cur');
					if($( flgId +'_'+i) != null ){
						$( flgId +'_'+i).show().siblings().hide();
						fn !=null ? fn.call(this,i) :'';
					}
				})
            });
		},
		
		domClick:function(e){
			
			 $.domEvent(e,function(elemTag){
				var el = $(elemTag);
				if(el.attr('class') != 'i_data_list' && el.parents('div').attr('class') != 'data_list_v2' && 
				   el.attr('id') !='establish_topic' && //话题编辑 --添加话题按钮
				   el.attr('id') !='establish_topic_pop' && //话题编辑 --弹出框
				   el.attr('id') !='editor_input' && //话题编辑
				   el.attr('id') !='editor_input_pop' && //话题编辑
				   el.attr('id') !='editor_topicBtns' &&
				   el.attr('id') !='i_search' && //搜索
				   el.parents('div').attr('id') !='search_global' &&
				   el.attr('id') != 'startQSpts' && //发起问题
				   el.parents('div').attr('id') != 'msgDropdown' //通知下拉框
				 ){
					$('#data_lsit2').hide();
					$(document).unbind('click',$.domClick); //卸载
				}
			})
		 },
		
		//公用搜索下拉
		dataList:function(options,callback){
			var elem,
				flg = $.extend({
					flgs:null,
					fh:6,
					html:null,
					parentsWidth:147,
					position:'absolute',
					parentsDiv:$('#data_lsit2'),
					childrens:$('#data_list_v2')
					
				},options||{})
			
			elem = $(flg.flgs);
			if(flg.childrens.length > 0){
				flg.parentsDiv
				.css({
					left:elem.offset().left,
					top:elem.offset().top +elem.innerHeight()+flg.fh,
					position:flg.position,
					width:flg.parentsWidth
				})
				.show();
				
				flg.childrens.html( flg.html )
			}else{
				
				$.data_list
				.addClass('i_data_list')
				.attr('id','data_lsit2')
				.css({
					left:elem.offset().left,
					top:elem.offset().top +elem.innerHeight()+flg.fh,
					zIndex:1000,
					position:flg.position,
					width:flg.parentsWidth
				})
				.html('<em class="i_arrows"></em><div id="data_list_v2" class="data_list_v2">'+flg.html +'</div>')
				.appendTo(document.body)
				.show();
			}
			$(document).bind('click',$.domClick);
			$(document).bind('scroll resize',function(){
				flg.parentsDiv.css({
					left:elem.offset().left,
					top:elem.offset().top +elem.innerHeight()+flg.fh,
					position:flg.position,
					width:flg.parentsWidth
				});
			})
			
			//
			
			callback !=null ? callback() :'';
			
		},
		
		//问题重定向搜索
		question_reset:function(){
			
			var el  = $('#question_reset_input'),elem=[] ,index = 0 ,xf =false;
			if(el.length > 0 ){
				el.changeElement(function(){
					if($.trim(el.val()).length == 0 || el.val() =='搜索问题'){
						$('#data_lsit2').length > 0 ? $('#data_lsit2').hide():'';
						return;
					}else if($.trim(el.val()).length == 1){
						$.dataList({flgs:el,html:'<p class="s">请输入两个以上关键字...</p>'});
						return;
					}else{
						$.ajax({
							type:"GET",
							url:G_BASE_URL+"/search/ajax/search/?q="+encodeURIComponent($.trim(el.val()))+'&type=question&limit=30',
							dataType:"json",
							success: function(s){
								if(s && s.length > 0){
									$(el).unbind('keyup');index = 0;
									$(s).each(function(index, element) {
										var e = $(s)[index];
										index > 10 ? '' : elem.push('<p><a title="'+e.name+'" href="javascript:;" style="width:350px;" onclick="ajax_request(G_BASE_URL +\'/question/ajax/redirect/\', \'item_id='+QUESTION_ID+'&target_id='+e.sno+'\');">'+e.name+'</a></p>');
									});
									$.dataList({flgs:el,html:elem.join(''),parentsWidth:350});
									elem = [];
								}else{
									$.dataList({flgs:el,html:'<p class="s">暂时未找到相关结果...</p>'});
								}
							},
							complete: function(){
								
								var flg = el, elemnts = $('#data_list_v2 >p');
								elemnts.eq(index).addClass('cur');
								var hotKeys = function(keys){
									var Df = $.extend({
											key:null,
											el:null
											
										},keys||{});
										
									if(Df.key == 38){ //
										index == 0 ? (index = Df.el.length-1) : index--;
									}else if(Df.key == 40){
										index ==  Df.el.length-1 ? (index = 0) : index++;
									}
									Df.el.eq(index).addClass("cur").siblings().removeClass();
									
								};
								$(flg).bind('keyup',function(e){
		 							var key = e.keyCode;
										  switch(key){
											  case 38:
											  	hotKeys({key:key,el:elemnts});
											  break;
											  case 40:
											  	hotKeys({key:key,el:elemnts});
											  break;
											  case 13:
												 elemnts.eq(index).find('a').click();
												 index = 0 ;
												 $(flg).unbind('keyup');
											  break;
										  }
									})
									
									elemnts.hover(function(){
										$(flg).unbind('keyup');
										var el = $(this);
										index = el.index();
										el.addClass("cur").siblings().removeClass('cur');
										
									})
									
									
								
							}
							
						})
					} //end if
				})//end changeElement
				
			}//end if
		},
		
		//站内邀请
		invitation_user:function(options){

			var flgs = $.extend({
					num:Number($('#invi_User').attr('rel') == 0 || $('#invi_User').attr('rel') == null ? 0: $('#invi_User').attr('rel')),
					dataList:$('#data_lsit2'),
					tipsErr:$('#tips_err'),
					userInvi: $('#invi_inputUser'),
					userInvi_msg: $('#invi_User')
				},options ||{});
			
			if(flgs.ex == 'help'){ //可能帮到你的人 邀请
				
				$.ajax({
						  type:"POST",
						  url:G_BASE_URL+"/question/ajax/save_invite/",
						  data: "question_id="+QUESTION_ID+"&uid="+flgs.uid,
						  dataType:"json",
						  success: function(s){
							  if(s && s.errno ==1){
								  $('#i_tabs_0').css('display') == 'none' ? $('#i_tabs').find('a:eq(0)').click() :'';
								  
								  $('<li/>')
									.html('<a href="javascript:;" class="pic_xa i_left">'+
										  '<img class="user_msg" data-message="&uid='+flgs.uid+'&card=user" src="'+flgs.url+'"></a>'+
										  '<a class="i_gray_bt2 i_right" href="javascript:;" onclick="$.disinvite_user('+flgs.uid+',this,\''+flgs.user+'\')">取消邀请</a>'+
										  '<span class="username_list" title="'+flgs.user+'">'+flgs.user+'</span>')
								   .appendTo($('#invitation_user_list'));
								   
								   flgs.dataList.hide();
								  // flgs.tipsErr.show().html(flgs.user+' 邀请成功...');
								   flgs.userInvi.val('').focus();
								   flgs.userInvi_msg.show().html(++flgs.num).attr('rel',flgs.num);
								   $(flgs.fg).removeAttr('onclick').css('color','#666');
							 }else if(s.errno == -1){
									$.alert(s.err);
							 }
						}
					})//end ajax
				
			}else{
				var flg = $(this);
				if($('#invitation_user_list').length > 0 ){
					$.ajax({
						  type:"POST",
						  url:G_BASE_URL+"/question/ajax/save_invite/",
						  data : "question_id="+QUESTION_ID+"&uid="+flg.attr('data-id'),
						  dataType:"json",
						  success: function(s){
							  if(s && s.errno ==1){
								  $('<li/>')
									.attr('data-id',flg.attr('data-id'))
									.html('<a href="javascript:;" class="pic_xa i_left">'+
										  '<img class="user_msg" data-message="&uid='+flg.attr('data-id')+'&card=user" src="'+flg.find('img:eq(0)').attr('src')+'"></a>'+
										  '<a class="i_gray_bt2 i_right" href="javascript:;" data-id="'+flg.attr('data-id')+'" onclick="$.disinvite_user('+flg.attr('data-id')+',this,\''+flg.attr('data-value')+'\')">取消邀请</a>'+
										  '<span class="username_list" title="'+flg.attr('data-value')+'">'+flg.attr('data-value')+'</span>')
								   .appendTo($('#invitation_user_list'));
								   flgs.dataList.hide();
								  // flgs.tipsErr.show().html(flg.attr('data-value')+' 邀请成功...');
								   flgs.userInvi.val('').focus();
								   flgs.userInvi_msg.show().html(++flgs.num).attr('rel',flgs.num);
							 }else if(s.errno == -1){
									flgs.dataList.hide();
									flgs.tipsErr.show().html(s.err);
									flgs.userInvi.val('').focus();
							 }
						}
					})
					
				}
			}//end if
			
		},//end invitation_user
		
		//取消邀请
		disinvite_user:function(user_id,flg,user){
			var n = Number($('#invi_User').attr('rel'));
			$.ajax({
				type:"GET",
				url:G_BASE_URL+"/question/ajax/cancel_question_invite/question_id-"+QUESTION_ID+"__recipients_uid-"+user_id,
				dataType:"json",
				success: function(s){
					$(flg).parent('li').slideDown('slow',function(){$(this).remove();})
					 --n == 0 ? $('#invi_User').hide().html(n).attr('rel',n) : $('#invi_User').show().html(n).attr('rel',n);
				}
		  })
		},
		
		//取消操作
		exit_operate:function(flgId){
			var elem = $( flgId );
			
			$( flgId +'>a').each(function(i,e) {
                $(this).click(function(){
					$(this).hasClass('cur') ? $(this).removeClass('cur') : $(this).addClass('cur').siblings().removeClass('cur');
					var el  = $( flgId +'_'+i);
					if(el != null ){

						el.css('display') == 'none' ?  el.show().siblings().hide() : el.hide();
					}
				})
            });
		},
		
		//公用问题发起的搜索ajax查询
		searchEventQs:function(s){
			if($.cookie('data_list_v2') == 'true') return;
			var el = $(s), 
				index= 0,
				val = $.trim(el.val()),
				flx = false,
				flg = {
					f0:'<p class="s">输入关键字进行搜索...</p>',
					f1:'<p class="s">请输入两个以上关键字...</p>',
					f2:'<p class="s">正在加载，请稍后...</p>',
					elem : $('#data_lsit2'),
					elemsArr:[]
				};
				
				el.focus(function(){			 
					flg.elem.css('display') == 'none' ? ($(el).unbind('keyup'),flx = true) : '';
				});
				
				if(val == '发起问题的标题...' || val.length == 0 ){
					flg.elem.hide();
				}else if(val.length == 1){
					 $.dataList({
					  flgs:el,
					  html:flg.f1,
					  parentsWidth:el.innerWidth()
				  });
				}else if(val.indexOf('*') >= 0){
					flg.elem ? flg.elem.hide() :'';
				}else if(val.length > 1 && val.length < 10){
					
					$.ajax({
					  type:'GET',
					  url:G_BASE_URL+'/search/ajax/search/?type=question&q='+encodeURIComponent(val),
					  dataType:'json',
					  success: function(result){
						  if(result && result.length > 0){
							 $(el).unbind('keyup'),flx = true;
							  $(result).each(function(index, element) {
								  var elResult = $(result)[index];
								  index <= 8 ? 
								  flg.elemsArr.push('<p class="'+(index==0 ? 'closex':'')+'"><a href="'+G_BASE_URL+'/'+elResult.url+'" title="'+elResult.name+'">'+(elResult.name.replace(new RegExp(val,"ig"),'<b>'+val+'</b>'))+(index==0?'<em title="关闭" class="e" onclick="$.mpopClose(this,event);">x</em>':'')+'</a></p>') :'';
							  })
							  $.dataList({
								  flgs:el,
								  html:flg.elemsArr.join(''),
								  parentsWidth:el.innerWidth()
							  });
							   flg.elemsArr =[];
							  
						  }else{
							flg.elem.hide().find('#data_list_v2').html('');
						  }
					  },
					  complete: function(){
								
						  var flg = el, elemnts = $('#data_list_v2 >p');
						  if(elemnts.length > 0 ){
							  elemnts.eq(index).addClass('cur');
							  var hotKeys = function(keys){
								  var Df = $.extend({
										  key:null,
										  el:null
										  
									  },keys||{});
									  
								  if(Df.key == 38){ //
									  index == 0 ? (index = Df.el.length-1) : index--;
								  }else if(Df.key == 40){
									  index ==  Df.el.length-1 ? (index = 0) : index++;
								  }
								  Df.el.eq(index).addClass("cur").siblings().removeClass();
							  };
							  $(flg).bind('keyup',function(e){
								  var key = e.keyCode;
										switch(key){
											case 38:
											  hotKeys({key:key,el:elemnts});
											  flx = false;
											break;
											case 40:
											  hotKeys({key:key,el:elemnts});
											  flx = false;
											break;
											case 13:
											   flx && index== 0 ? $.noop() :
											   window.location.href = elemnts.eq(index).find('a').attr('href');
											   index = 0 ;
											break;
											case 8:
												val.length != 0 ? $.noop(): flg.elem.hide(), $(flg).unbind('keyup');
											break;
										}
								  })
								  
								  elemnts.hover(function(){
									  var el = $(this),flx = false;
									  index = el.index();
									  el.addClass("cur").siblings().removeClass('cur');
								  })
							  
						  }else{
							  
						  	 $(flg).unbind('keyup');
							 
						  }//end if
					  } 
							
					}); //end ajax
				}
				
		},
		
		mpopClose:function(flg,e){
			var event = e ? e : window.event;
			event.preventDefault();
			event.stopPropagation ? event.stopPropagation() : event.returnValue = false;
			$(flg).parents('div#data_lsit2').hide();
			$.cookie('data_list_v2', 'true');
		},
		
		//插入图片 弹出框
		uploadPicture:function(){
			var flgElem= $('<div/>')
				.addClass('i_gloBoxl i_alert')
				.attr('id','i_gloBoxl')
				.html('<div class="i_glotitle">'+
						'<h3 class="i_prl"><p class="qs_bold i_white">插入图片</p>'+
						'<a title="关闭" onclick="$.closeDefault(this);" class="i_right i_pas i_small i_closed" href="javascript:;">关闭</a></h3>'+
						'</div>'+
						'<div class="i_gloDiv" id="i_globalContant">'+
						'<form id="addTxtForms"><div class="qs_txtare" style="padding:0!important;">图片链接地址：'+
							'<div class="add_label ">'+
								'<input type="text" name="imgsUrl" class="i_glotxtClass upload_txt"  value="http://" onfocus="$.focus(this,\'\');"/>'+
							'</div>'+
						'</div>'+
						'<div class="qs_txtare">图片描述：'+
							'<div class="add_label ">'+
								'<input type="text" name="imgsAlt" class="i_glotxtClass upload_txt" value="" onfocus="$.focus(this,\'\');"/>'+
							'</div>'+
						'</div></form>'+
						
						'<div class="qs_txtare i_tr">'+
							'<a onclick="$.closeDefault(this);" href="javascript:;" id="closeDefault">取消</a>&nbsp;&nbsp;&nbsp;&nbsp;'+
							'<a class="i_green_bt2 i_txtmiddle" id="qsSubmite_form1"  href="javascript:;" onclick="$.addTextpicTure()" title="确定">确定</a>'+
						'</div>'+
						
						'</div>')
				.appendTo(document.body);
				$.drag(flgElem.find('div[class^="i_glotitle"]:first'));
		},
		//![图片标题](http://www.anwsion.com) 
		
		//插入操作
		addTextpicTure:function(){
			$.fn.insertAtCaret = function(textFeildValue){ 
					var textObj = $(this).get(0); 
					if(document.all && textObj.createTextRange && textObj.caretPos){ 
						var caretPos=textObj.caretPos; 
						caretPos.text = caretPos.text.charAt(caretPos.text.length-1) == '' ? 
							textFeildValue+'' : textFeildValue; 
					} 
					else if(textObj.setSelectionRange){ 
						var rangeStart=textObj.selectionStart; 
						var rangeEnd=textObj.selectionEnd; 
						var tempStr1=textObj.value.substring(0,rangeStart); 
						var tempStr2=textObj.value.substring(rangeEnd); 
						textObj.value=tempStr1+textFeildValue+tempStr2; 
						textObj.focus(); 
						var len=textFeildValue.length; 
						textObj.setSelectionRange(rangeStart+len,rangeStart+len); 
						textObj.blur(); 
					} 
					else { 
						textObj.value+=textFeildValue; 
					} 
			} //
			
			var textFeildValue = '\n!['+($('#addTxtForms :input[name="imgsAlt"]').val())+']('+$('#addTxtForms :input[name="imgsUrl"]').val()+')';
			$(arkItUpPreviewId) == null ? '' :
			$(arkItUpPreviewId).insertAtCaret(textFeildValue),$('#closeDefault').click();
		}
		
	}); //end  extend
})(jQuery)