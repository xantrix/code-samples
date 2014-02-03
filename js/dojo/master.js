dojo.provide("xan.items.master");

xan.items.master.deleteMultiMaster = function(paneId,urlTarget){
	var confirm = xan.defaultmodule.confirm();
	confirm.setTitle('Delete records');
	confirm.setContent('Delete selected records ? ');
	confirm.setExecuteFn(function(){
		var pane = dijit.byId(paneId);
		var form = dojo.byId('mastersForm');
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
            	xan.helpers.form.errorAlertArea('No records selected');
            }else{
            	xan.helpers.form.successAlertArea('Records successfully deleted ');
            }
		};
		xan.defaultmodule.ajaxSrv().onError = function(data){
			xan.helpers.form.errorAlertArea('Error!');
		};

		var cfg = {url:urlTarget,
					form:form};
		xan.defaultmodule.ajaxSrv().call(cfg);


	});
	confirm.show();
};

xan.items.master.assocMultiMaster = function(paneId,urlTarget,targetName){
	var confirm = xan.defaultmodule.confirm();
	confirm.setTitle('Bind records');
	confirm.setContent('Bind record to " '+targetName+' " ?');
	confirm.setExecuteFn(function(){
		var pane = dijit.byId(paneId);
		var form = dojo.byId('mastersForm');
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
            	xan.helpers.form.errorAlertArea('No records selected');
            }else{
            	xan.helpers.form.successAlertArea('Records successfully bound');
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