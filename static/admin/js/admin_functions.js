function _form_process(result)
{
	if (result.errno == "-1")
	{
		if ($('#notification_box').length > 0)
		{
			$("html,body").animate({scrollTop:0},'slow');
			
			$("#notification_box").removeClass("success").addClass("error").fadeIn().find('[name=notification_content]').html(result.err);
		}
		else
		{
			alert(result.err);
		}
	}
	else if (result.errno == "1")
	{
		if (result.rsm && result.rsm.url)
		{
			window.location.href = decodeURIComponent(result.rsm.url);
		}
		else
		{
			if ($('#notification_box').length > 0)
			{
				$("html,body").animate({scrollTop:0},'slow');
				
				$("#notification_box").removeClass("error").addClass("success").fadeIn().find('[name=notification_content]').html(result.err);
				
				setTimeout(function () {
					$('#notification_box').fadeOut();
				}, 3000);
			}
			else
			{
				window.location.reload();
			}
		}
	}
}

function topic_lock(topic_id, status)
{
	var url = G_BASE_URL + '/admin/topic/topic_lock/topic_id-' + topic_id + '__status-' + status;
	
	$.getJSON(url, function (response)
	{
		if (response)
		{
			if (response.err)
			{
				alert(response.err);
			}
			
			if (response.errno == 1)
			{
				if (response.rsm)
				{
					if (response.rsm.url != undefined)
					{
						window.location.href = response.rsm.url;
					}
					else
					{
						window.location.reload();
					}
				}
				window.location.reload();
			}
		}
		else
		{
			alert("系统错误");
		}
	},'json').error(function (error) { alert('发生错误, 返回的信息: ' + error.responseText); });
}

function category_remove(category_id)
{
	if (!confirm("确定删除分类及其子分类？"))
	{
		return false;
	}
	
	var url = G_BASE_URL + '/admin/category/category_remove/category_id-' + category_id;
	
	$.getJSON(url, function (response){
		if (response)
		{
			if (response.err)
			{
				alert(response.err);
			}
			
			if (response.errno == 1)
			{
				window.location.reload();
			}
		}
		else
		{
			alert("系统错误");
		}
	},'json').error(function (error) { alert('发生错误, 返回的信息: ' + error.responseText); });
}

function change_send_email(el)
{
	if (el.val() == '2')
	{
		el.parent().parent().find("[groupid=6]").hide();
	}
	else if (el.val() == '1')
	{
		el.parent().parent().find("[groupid=6]").show();
	}
}

function test_email_setting(fm, el)
{
	el.val("  正在发送...  ");
	
	$.post(G_BASE_URL + '/admin/setting/test_email_setting/', fm.formToArray(), function (result)
	{
		el.val("  发送测试邮件  ");
		
		alert(result.err);
	}, 'json').error(function (error) { el.val("  发送测试邮件  "); alert('发生错误, 返回的信息: ' + error.responseText); });
}

function runCode(obj)
{
	var winname = window.open('', "_blank", '');
	winname.document.open('text/html', 'replace');
	winname.opener = null;
	winname.document.write(obj.val());
	winname.document.close();
}