$(document).ready(function ()
{
	if($(":input[name=cache_open]:checked").val() == 'N')
	{
		$("p[groupid=10]").hide().slice(0,2).show();
	}
	
	$(":input[name=cache_open]").change(function()
	{
		if($(":input[name=cache_open]:checked").val() == 'Y')
		{
			$("p[groupid=10]").show();
		}
		else
		{
			$("p[groupid=10]").hide().slice(0,2).show();
		}
	});
	
	
	if($(":input[name=email_type]").length > 0)
	{
		if($(":input[name=email_type]").val() == '2')
		{
			$("p[groupid=7]").hide();
			$("p[name=email_type]").show();
		}
	}
	
	$(":input[name=email_type]").change(function()
	{
		if($(":input[name=email_type]").val() == '2')
		{
			$("p[groupid=7]").hide();
			$("p[name=email_type]").show();
		}
		else if($(":input[name=email_type]").val() == '1')
		{
			$("p[groupid=7]").show();
		}
	});

	if($(":input[name=url_rewrite_enable]:checked").val() == 'N')
	{
		$("[name=request_route]").hide();
		$("[name=request_route_custom]").hide();
	}
	
	$(":input[name=url_rewrite_enable]").change(function()
	{
		if($(":input[name=url_rewrite_enable]:checked").val() == 'Y')
		{
			$("[name=request_route]").show();
			$(':input[name=request_route]').change();
		}
		else
		{
			$("[name=request_route]").hide();
			$("[name=request_route_custom]").hide();
		}
	});
	
	$(":input[name=request_route]").change(function()
	{
		if($(":input[name=request_route]:checked").val() == '99')
		{
			$("[name=request_route_custom]").show();
		}
		else
		{	
			$("[name=request_route_custom]").hide();
		}
	});

	if($(":input[name=site_close]:checked").val() == 'N')
	{
		$("p[name=close_notice]").hide();
	}
	
	$(":input[name=site_close]").change(function()
	{
		if($(":input[name=site_close]:checked").val() == 'Y')
		{
			$("p[name=close_notice]").show();
		}
		else
		{
			$("p[name=close_notice]").hide();
		}
	});

	if($(':input[name=integral_system_enabled]:checked').val() == 'N')
	{
		$("p[groupid=14]").hide().slice(0,1).show();
	}

	$(":input[name=integral_system_enabled]").change(function ()
	{
		if($(':input[name=integral_system_enabled]:checked').val() == 'Y')
		{
			$("p[groupid=14]").show();
		}
		else
		{
			$("p[groupid=14]").hide().slice(0,1).show();
		}
	});

	if($(":input[name=qq_login_enabled]:checked").val() == 'N')
	{
		$("p[name=qq_login_app_id]").hide();
		$("p[name=qq_login_app_key]").hide();
	}
	
	$(":input[name=qq_login_enabled]").change(function()
	{
		if($(":input[name=qq_login_enabled]:checked").val() == 'Y')
		{
			$("p[name=qq_login_app_id]").show();
			$("p[name=qq_login_app_key]").show();
		}
		else
		{
			$("p[name=qq_login_app_id]").hide();
			$("p[name=qq_login_app_key]").hide();
		}
	});
});