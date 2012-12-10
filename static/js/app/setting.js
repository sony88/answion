/*+--------------------------------------+
* 名称: setting
* 功能: 账户设置
* anwsin官方:http://www.anwsion.com/
* developer:诸葛浮云
* Email:globalbreak@gmail.com 
* Copyright © 2012 - Anwsion社区, All Rights Reserved 
* Date:2012-08-31
*+--------------------------------------+*/



$.set = {
	
	//
	flgUl:null,
	
	//添加教育经历
	addElem:function(options){
		var elemUl,elems,elemntArr,elemtxt,option,evnClick;

		option = $.extend({
			 flg: null,
			zero:0,
			  fg:1,
	   eventType:'load',                //事件类型
		 changer:false,                                      //展示用户资料开关
		 ulClass:'v_group_bd i_clear i_white_bg i_pas '       //新元素样式
		 
		},options || {});
		
		evnClick = option.eventType ? option.eventType : window.event;

		if(!option.changer && evnClick.type == 'click'){
			
			elemUl = $(option.flg).parents('ul[class*="v_group_bd"]'),
			elemtxt = $(elemUl).find(":text");
			
			if($.trim(elemtxt.eq(option.zero).val()) =='' || 
			   elemtxt.eq(option.zero).val() =='如:xx大学...'
			){
				
				$.alert('请填写学校名称！');
				return false;
				
			}else if(
				$.trim(elemtxt.eq(option.fg).val()) =='' || 
				elemtxt.eq(option.fg).val()=='如:工程学院计算机系...'
			){
	
				$.alert('请填写所在院系！');
				return false;
				
			}else if($(elemUl).find(":selected:eq(0)").val() == ''){
				
				$.alert('请选择入学年份！');
				return false;
				
				
			}else{
				$.post(G_BASE_URL + '/account/ajax/add_edu/', $('#v_education_formReset').formToArray(), function (result)
				{
					if (result.err)
					{
						$.alert(result.err);
					}
					else
					{
						$('<ul/>')
						
						//css3底部圆角处理
						.addClass(
							 $('#v_education')
							 .find('ul[class*="v_group_bd"]').length > 
							 option.fg ? option.ulClass :option.ulClass+(option.changer ? '':'i_bottomRadius5'))
							 
						.css({
							left:$(elemUl).offset().left,
							top:$(elemUl).offset().top-$(elemUl).innerHeight(),
							opacity:option.zero
						})
						.html('<li class="s1" data-txt="'+elemtxt.eq(option.zero).val()+'">'+elemtxt.eq(option.zero).val()+'</li>'+
							  '<li class="s1" data-txt="'+elemtxt.eq(option.fg).val()+'">'+elemtxt.eq(option.fg).val()+'</li>'+
							  '<li class="s2" data-txt="'+$(elemUl).find("select").eq(option.zero).val()+'">'+$(elemUl).find("select").eq(option.zero).val()+'</li>'+
							  '<li class="s2" data-theme="handle">'+
							  '<a href="javascript:;" onclick="$.set.deleteElem(this,'+result.rsm.id+');" class="v_delect">删除</a>'+
							  '<a href="javascript:;" onclick="$.set.amendElem(this, '+result.rsm.id+');">修改</a></li>')
							  
						.insertAfter($('#v_education >form:first'))
						
						//清除动画
						.stop()
						
						//向下漂移
						.animate({
							top:$(elemUl).offset().top + $(elemUl).innerHeight(),
							opacity:option.fg
						},'slow','easeInOutBounce',function(){
							
							//清除浮动
							$(this)
							.removeClass('i_white_bg i_pas')
							.removeAttr('style').wrap('<form class="eduForm_Edit" action="' + G_BASE_URL + '/account/ajax/edit_edu/' + result.rsm.id + '" method="post" onsubmit="return false"></form>');
						});
						
						//清空表单
						$('#v_education_formReset')[option.zero].reset();
						elemtxt.eq(option.zero).focus();
						$(elemUl).hasClass('i_bottomRadius5') ? $(elemUl).removeClass('i_bottomRadius5') : 
						$(elemUl)
					}
				}, 'json');
			  }
		  
		}else{
			
			if(option.changer){
				
			   elemntArr=[],elems = $('#v_education_formReset').find(':only-child') ;option.array.reverse();
			   $(option.array).each(function(index, element) {
				   var elem = $(option.array)[index];
						elemntArr.push('<li class="s1" data-txt="'+elem.school_name+'">'+elem.school_name+'</li>');
						elemntArr.push('<li class="s1" data-txt="'+elem.departments+'">'+elem.departments+'</li>');	
						elemntArr.push('<li class="s2" data-txt="'+elem.education_years+'">'+elem.education_years+'</li>');
					    elemntArr.push('<li class="s2" data-txt="'+elem.education_id+'"><a class="v_delect" onclick="$.set.deleteElem(this,'+elem.education_id+');" href="javascript:;">删除</a><a onclick="$.set.amendElem(this,'+elem.education_id+');" href="javascript:;">修改</a></li>');
					   $('<ul/>')
						.addClass('v_group_bd i_clear ')
						.html(elemntArr)
						.insertAfter($('#v_education >form:first'))
						.wrap('<form class="eduForm_Edit" action="' + G_BASE_URL + '/account/ajax/edit_edu/' + elem.education_id + '" method="post" onsubmit="return false"></form>');
						elemntArr=[];
			    });
			  
			  elems.hasClass('i_bottomRadius5') ? elems.removeClass('i_bottomRadius5') : '';
			  $('#v_education').find('ul:last').addClass('i_bottomRadius5')
		  }
		}//end if 
	},
	//删除
	deleteElem: function(s, id) {
		
		$.post(G_BASE_URL + '/account/ajax/remove_edu/', 'id=' + id, function (result){
			if (result.err){
				$.alert(result.err);
				
			}else{
				
				var flg = $(s).parents('ul[class*="v_group_bd"]');
				flg.stop().slideUp('slow','easeOutBounce',function(){
					
					$(this).remove();
					
					//底部圆角添加
					$('#v_education ul:last').hasClass('i_bottomRadius5') ? '' :
					$('#v_education ul:last').addClass('i_bottomRadius5');	
				});
			}
		}, 'json');
	},
	
	//修改
	amendElem:function(s, id){
		var S = $(s).parents('ul[class*="v_group_bd"]');
		
		S.children(':first')
			.html('<input name="school_name" type="text" value="'+S.children(':first').attr('data-txt')+'">')
			
			//所在院系
			.siblings(':eq(0)')
			.html('<input type="text" name="departments" value="'+S.children(':eq(1)').attr('data-txt')+'">')
			
			//
			.siblings(':eq(1)')
			.html($('#v_groupSelects').html());
			$(S).find('li:eq(2)').find('select:first').val($(S).find('li:eq(2)').attr('data-txt'))
			
		$(s).html('确定').attr('onclick','$.set.sureElem(this, '+id+')');
		
	},
	
	//确定修改
	sureElem:function(s, id){
		$.post($(s).parents('form.eduForm_Edit').attr('action'), $(s).parents('form.eduForm_Edit').formToArray(), function (result) {
			if (result.err){
				$.alert(result.err);
				
			}else{
				var ul = $(s).parents('ul');
				ul.children(':first')
				.attr('data-txt',ul.find('li:first').find(':only-child').val())
				.html(ul.find('li:first').find(':only-child').val())
				
				//
				ul.find('li:eq(1)')
				.attr('data-txt',ul.find('li:eq(1) >:text').val())
				.html(ul.find('li:eq(1) >:text').val())
				
				//
				.siblings(':eq(1)')
				.attr('data-txt',ul.find(':selected:first').val())
				.html(ul.find(':selected:first').val());
				
				$(s).html('修改').attr('onclick','$.set.amendElem(this, '+ id +')');
			}
		});
	},
	
	
	//工作经历
	addjobs:function(options){
		var evnClick,option,elemntArr,elems,
		
		option = $.extend({
				flg:null,
				zero:0,
				flgUl:$('#user_Jobs_Ul'),
				uClass:'v_group_bd i_clear ',//新增模块样式
				company:$('#Job_company_name'),//公司
				jobs:$('#work_jobs_list'),//工作职位
				start:$('#work_start_year'), //工作起始年限
				end:$('#work_end_year'),  
				manipulate:$('#user_Manipulates'),  //操作
				changer:false,
				eventType:'load'
				
			},options ||{});
			
		evnClick = option.eventType ? option.eventType : window.event;
		
		if(!option.changer && evnClick.type == 'click'){
			
			if(
				$.trim(option.company.val()) == '' ||
				option.company.val() =='xx上市公司...'
			){
				
				$.alert('公司名称未填写！！');
				return;
			}else if(option.jobs.val() == ''){
				
				$.alert('所在职位未填写！！');
				return;
			}else if( option.start[option.zero].selectedIndex <= 0 || option.end[option.zero].selectedIndex <= 0 ){
			
				$.alert('工作时间填写完整！！');	
				return ;
					
			}else{
					
					$.post(G_BASE_URL + '/account/ajax/add_work/', $('#v_job_formReset').formToArray(), function (result){
						
						if (result.err){
							$.alert(result.err);
						}
						else{
							
							$('<div/>')
							.addClass('i_white_bg i_pas')
							.css({
								left:option.flgUl.offset().left,
								top:option.flgUl.offset().top-(option.flgUl.innerHeight()+option.manipulate.innerHeight()),
								opacity:0
							})
							
							.html(
								'<form action="' + G_BASE_URL + '/account/ajax/edit_work/' +　result.rsm.id +'" method="post" onsubmit="return false" class="workForm_Edit">'+
								'<ul class="'+option.uClass+'">'+
									'<li class="s1" data-txt="'+option.company.val()+'">'+option.company.val()+'</li>'+
									'<li class="s1" data-txt="'+option.jobs[0].selectedIndex+'">'+option.jobs[0].options[option.jobs[0].selectedIndex].text+'</li>'+
									'<li class="s3" data-start-value="'+option.start.val()+'" data-end-value="'+(option.end[option.zero].options[option.end[option.zero].selectedIndex].value ==-1 ? '-1':option.end[option.zero].options[option.end[option.zero].selectedIndex].text)+'">'+option.start.val()+' 年 '+(option.end[option.zero].options[option.end[option.zero].selectedIndex].value ==-1 ? '至今':'至 '+option.end[option.zero].options[option.end[option.zero].selectedIndex].text+' 年')+'</li>'+
								'</ul>'+
								'<p class="v_group_operate '+($('div#v_jobs p[class^="v_group_operate"]').length == 1 ? "i_bottomRadius5": "")+'">操作：'+
								'<a href="javascript:;" onclick="$.set.removeJobs(this, '+result.rsm.id+' );">删除</a>'+
								'<a href="javascript:;" onclick="$.set.amendJobs(this,'+result.rsm.id+' );">修改</a></p>'+
								'</form>'
							)
							
							.insertAfter(
							  option.manipulate.hasClass('i_bottomRadius5') ?
							  option.manipulate.removeClass('i_bottomRadius5') : 
							  option.manipulate
						  )
						  
						  //清除动画
						  .stop()
						  
						  //向下漂移
						  .animate({
							  top:option.flgUl.offset().top+(option.flgUl.innerHeight()+option.manipulate.innerHeight()),
							  opacity:1
						  },'slow','easeInOutBounce',function(){
							  
							  //清除浮动
							  $(this)
							  .removeClass('i_pas')
							  .removeAttr('style');
							  $('#v_job_formReset')[option.zero].reset();
							  option.company.focus();
						  });
						 
						}
						
						
					}, 'json');	
					
				}

		}else{
			
			if(option.changer){
				
							
				elemntArr=[],elems = $('#user_Jobs_Ul');option.array.reverse();
			   $(option.array).each(function(index, element) {
				   var elem = $(option.array)[index];
				   		var p ='<p class="v_group_operate">操作：<a onclick="$.set.removeJobs(this,'+elem.work_id+');" href="javascript:;">删除</a><a onclick="$.set.amendJobs(this,'+elem.work_id+');" href="javascript:;">修改</a></p>';
						elemntArr.push('<li class="s1" data-txt="'+elem.company_name+'">'+elem.company_name+'</li>');
						elemntArr.push('<li class="s1" data-txt="'+elem.job_id+'">'+elem.job_name+'</li>');	
						elemntArr.push('<li data-start-value="'+elem.start_year+'" data-end-value="'+(elem.end_year == -1 ? '-1':elem.end_year)+'" class="s3">'+elem.start_year+' 年 至 '+(elem.end_year == -1 ? '至今':elem.end_year+' 年')+'</li>');

						$('<form/>')
						.addClass('workForm_Edit')
						.attr('action',G_BASE_URL + '/account/ajax/edit_work/' + elem.work_id)
						.attr('method','post')
						.html('<ul class="v_group_bd i_clear">'+elemntArr.join('')+'</ul>'+p)
						.insertAfter(option.manipulate)
						.wrap(function(){
							return '<div class="i_white_bg"></div>';
						});
						
						elemntArr=[];
			    });
			  
			  elems.hasClass('i_bottomRadius5') ? elems.removeClass('i_bottomRadius5') : '';
			  $('#v_jobs').find('p:last').addClass('i_bottomRadius5')
			}
		
		}//end if for changer
			
	}, // end addjobs
	
	//删除
	removeJobs: function( elem , id){
		$.post(G_BASE_URL + '/account/ajax/remove_work/', 'id=' + id, function (result)
		{
			if (result.err)
			{
				$.alert(result.err);
			}
			else
			{
				$(elem).parents('div[class^="i_white_bg"]')
				.stop().slideUp('slow','easeOutBounce',function(){
						
						$(this).remove();
						
						//底部圆角添加
						$('#v_jobs p[class^="v_group_operate"]:last').hasClass('i_bottomRadius5') ? '' :
						$('#v_jobs p[class^="v_group_operate"]:last').addClass('i_bottomRadius5');	
					});
			}
		}, 'json');
	},
	
	//修改
	amendJobs:function( elem ,id){
		var elemUl = $(elem).parents('div[class^="i_white_bg"]').find('ul:first')
				 
			 //公司名称
			 elemUl.children(':first')
			 .html('<input type="text" name="company_name" value="'+elemUl.children(':first').attr('data-txt')+'">')
			 
			 //职位
			 .siblings(':eq(0)')
			 .html('<select class="v_group_selects_jobs" name="job_id">'+
					$('#work_jobs_list').html() +
				   '</select>');
			 
			 //工作时间
			elemUl.find('li:last').html($('#work_timeline').html());
			 
			 elemUl.find('li:eq(1) select:first')[0].selectedIndex =  elemUl.find('li:eq(1)').attr('data-txt');
			 elemUl.find('li:last select:first').val(elemUl.find('li:last').attr('data-start-value'));
			 elemUl.find('li:last select:last').val(elemUl.find('li:last').attr('data-end-value'));
			 
		$(elem).html('确定').attr('onclick','$.set.sureamendJob(this,'+id+')');
	},
	
	//确定修改
	sureamendJob:function( flg,id ){
		$.post($(flg).parents('form.workForm_Edit').attr('action'), $(flg).parents('form.workForm_Edit').formToArray(), function (result) {
			if (result.err){
				$.alert(result.err);
			}else{
				var elems = $(flg).parents('div.i_white_bg').find('ul:first');
				
				var elLi = elems.find('li:eq(0)'),elLival = elLi.find(':text').val(),
					 elLi_v2 = elems.find('li:eq(1)'), elLi_v2Val = elLi_v2.find('select')[0].options[elLi_v2.find('select')[0].selectedIndex].text,
					 elLast = elems.find('li:last'),elLastSatrtVal = elLast.find('select:first').val(),elLastEndVal = elLast.find('select:last').val()
				
				elLi.html(elLival)
				.attr('data-txt',elLival);
				
				elLi_v2.html(elLi_v2Val)
				.attr('data-txt',elLi_v2Val);
				
				elLast.html(elLastSatrtVal+' 年 '+(elLastEndVal == -1 ? '至今': '至  '+elLastEndVal+' 年'))
				.attr('data-start-value',elLastSatrtVal)
				.attr('data-end-value',elLastEndVal);
				
				$(flg).html('修改').attr('onclick','$.set.amendJobs(this,'+id+')');
			}
		}, 'json');
	} //end 

}