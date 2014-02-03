dojo.provide("xan.items.user");


xan.items.user.deleteMultiUser = function(paneId,urlTarget){
	var confirm = xan.defaultmodule.confirm();
	confirm.setTitle('Delete Users');
	confirm.setContent('Delete selected users ? ');
	confirm.setExecuteFn(function(){
		var pane = dijit.byId(paneId);
		var form = dojo.byId('userForm');
		dojo.style(form,'display','none');
		dojo.create('div',{innerHTML:pane.onDownloadStart()},form,'after');

		//override first
		xan.defaultmodule.ajaxSrv().onResponse = function(data){
        	pane.refresh();
        	pane.onDownloadEnd();
		};
		xan.defaultmodule.ajaxSrv().onData = function(data){
            if (data == "empty")
            {
            	xan.helpers.form.errorAlertArea('No users selected');
            }else{
            	xan.helpers.form.successAlertArea('Users successfully deleted');
            }
		};
		xan.defaultmodule.ajaxSrv().onError = function(data){
			xan.helpers.form.errorAlertArea('Error');
		};

		var cfg = {url:urlTarget,
					form:form};
		xan.defaultmodule.ajaxSrv().call(cfg);


	});
	confirm.show();
};