dojo.provide("xan.defaultmodule.AjaxService");

dojo.declare("xan.defaultmodule.AjaxService", null, {
	
	url: null,
	content: null,
	form: null,
	handleAs: null,
	sync: null,
	
	constructor: function(config){
	},
	
	call: function(config){
	
		
		this.url = config.url;
		this.content = config.content || {};
		this.form = dojo.byId(config.form);
		this.handleAs = config.handleAs || "json";
		this.sync = config.sync || true;
		
	    var f = (config.type == undefined || config.type=="Post") ? dojo.xhrPost : dojo.xhrGet ;
		
	    //f({...})
		dojo.xhrPost({
	        url: this.url,
	        content: this.content,
	        form: this.form,
	        handleAs: this.handleAs,
	        sync: this.sync,
	        handle:  dojo.hitch(this,function(data,args){
				this.onResponse(data);
				if(typeof data == "error" || data == "error")
	            {
	            	this.onError(data);
	            }
	            else 
	            {
	            	this.onData(data);
	            }
	        }),
	        error: dojo.hitch(this,function(data,args){
	        	this.onResponse(data);
	        	this.onError(data);
	        	this.onNetError(data);
	        })
	    });	
		
		
	},
	
	onResponse: function(data){
	
	},
	
	onError: function(data){
		
		//xan.helpers.form.errorAlertArea('Error ajax Submit');
	},
	
	onNetError: function(data){
	},
	
	onData: function(data){
		
		//xan.helpers.form.successAlertArea('Utente Eliminato con successo');
	}	
	

	
	
	
});

//the AjaxService singleton variable. it can be overwritten if needed.
xan.defaultmodule._ajaxSrv = null;
xan.defaultmodule.ajaxSrv = function(){
	// returns the current AjaxService. creates one only if it is not created yet.
	if(!xan.defaultmodule._ajaxSrv){
		xan.defaultmodule._ajaxSrv = new xan.defaultmodule.AjaxService();
	}
	return xan.defaultmodule._ajaxSrv;	// Object
};
