dojo.provide("xan.defaultmodule.Confirm");

dojo.declare("xan.defaultmodule.Confirm", [dijit.Dialog], {

	id: "confirmDialog",
	idOk: "confirmBtnOk",
	idCancel: "confirmBtnCancel",
	okButton:null,
	cancelButton:null,

	executeFn:null,
	//cancelFn:null,
	
	okLabel: "Ok",
	cancelLabel: "Annulla",
	
	onlyOk: false,
	
//	 postMixInProperties:function(){
//		  //this.inherited(arguments);
//		     /*If you provide a postMixInProperties method for
//		      * your widget, it will be invoked before rendering
//		      * occurs, and before any dom nodes are created.
//		      * If you need to add or change the instance's properties
//		      * before the widget is rendered - this is the place to do it.*/
//		  console.log("Post properties mixed in . . .");
//	},
	
	postCreate:function(){
		  this.inherited(arguments);//necessaria
		     /* This is typically the workhorse of a custom widget.
		      * The widget has been rendered (but note that
		      * sub-widgets in the containerNode have not!) */
		  if(!this.onlyOk){
				this.cancelButton = new dijit.form.Button({
					id: this.idCancel,
					label: this.cancelLabel, 
					onClick: dojo.hitch(this,function(){ 
															this.onCancel();
															}) 
				});		
				this.domNode.appendChild(this.cancelButton.domNode);
			}
		  
		this.okButton = new dijit.form.Button({
			id: this.idOk,
			label: this.okLabel, 
			type: "submit", onClick: function(){}  
		});

		this.domNode.appendChild(this.okButton.domNode);
		
		
		//console.log("Created the widget . . .");
	},
	 
//	startup: function(){
//		 this.inherited(arguments);
//	     /* If you need to be sure parsing and creation
//	      *of any child widgets is complete, use startup.*/
//		 console.log("All children created, starting the widget . . .");
//	 },
	 
//	destroy: function(){
//		 //this.inherited(arguments);
//	     /* Implement destroy if you have special tear-down
//	      * work to do (the superclasses will take care of
//	      * most of it for you; be sure to call this.inherited
//	            * (arguments);) */
//	     console.log("Called for widgets destruction . . .");
//	},		 


	setExecuteFn: function(callback){
		this.executeFn = callback;
	},
	
	execute: function(){
		this.executeFn();
	},
	
	setTitle: function(title){
		this.attr('title',title);
	},
	
	setContent: function(content){
		this.attr('content',content);
	}
	
	/**
	 * _DialogMixin.js contiene execute onExecute onCancel
	 */
//	onCancel: function(){
//		this.inherited(arguments);
//	}

});


//the confirm singleton variable. it can be overwritten if needed.
xan.defaultmodule._confirm = null;
xan.defaultmodule.confirm = function(config){
	// returns the current confirm. creates one only if it is not created yet.
	if(!xan.defaultmodule._confirm){
		xan.defaultmodule._confirm = new xan.defaultmodule.Confirm(config);
		dojo.body().appendChild(xan.defaultmodule._confirm.domNode);
		xan.defaultmodule._confirm.startup();
	}
	return xan.defaultmodule._confirm;	// Object
};

//the confirmOk singleton variable. it can be overwritten if needed.
xan.defaultmodule._confirmOk = null;
xan.defaultmodule.confirmOk = function(){
	// returns the current confirmOk. creates one only if it is not created yet.
	if(!xan.defaultmodule._confirmOk){
		var config = { 
				onlyOk: true, 
				id: "confirmOkDialog",
				idOk: "confirmOkBtnOk"
		};
		
		xan.defaultmodule._confirmOk = new xan.defaultmodule.Confirm(config);
		xan.defaultmodule._confirmOk.setExecuteFn(function(){
			this.onCancel();
		});
		dojo.body().appendChild(xan.defaultmodule._confirmOk.domNode);
		xan.defaultmodule._confirmOk.startup();
	}
	return xan.defaultmodule._confirmOk;	// Object
};

