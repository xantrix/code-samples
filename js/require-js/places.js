define([
	"jquery",
	"xan/helpers/util",
	"xan/helpers/places/markerwithlabel"
	//"http://maps.googleapis.com/maps/api/js?libraries=places&sensor=true&key=AIzaSyDA_6ZZn5BxBWfFGcLylrkSxXGpgVw9ahg"
], function($,util,mlabel){

	var places = {

		//position
		position: null, /*Geoposition Object*/
		positionLatLng: null, /*google.maps.LatLng Object*/
		lat: null,
		lon: null,

		//options
		options: null,
		xanData: null,
		xanAggregateData: null,
		xanOptions: null,

		lastResult: null,
		lastProvider: null,

		_map: null,
		_autocomplete: null,
		lastAutocomplete: null,
		_timeout: 20000,

	    _xanRadius: 20, /* km */
	    _xanLimit: 20,

		//_markers: null,

		/**
		 * Init position (browser object navigator.geolocation.getCurrentPosition)
		 */
		getPosition: function(okCallback,errCallback){
			if(this.position != null)
				return okCallback(this.position);

			if (navigator.geolocation)
			{
				var self = this;
				navigator.geolocation.getCurrentPosition(
						function (position) {
							self.position = position;//cache
							self.lat = self.position.coords.latitude;
							self.lon = self.position.coords.longitude;
							self.positionLatLng = new google.maps.LatLng(self.lat, self.lon);
							console.log("SET NEW POSITION lat="+self.lat+"lon="+self.lon);

							return okCallback(position);
						},
						// next function is the error callback
						function (error)
						{
							switch(error.code)
							{
								case error.TIMEOUT:
									alert ('Timeout');
									break;
								case error.POSITION_UNAVAILABLE:
									alert ('Position unavailable');
									break;
								case error.PERMISSION_DENIED:
									alert ('Permission denied');
									break;
								case error.UNKNOWN_ERROR:
									alert ('Unknown error');
									break;
							}
							return errCallback(error);
						}
					);
			}
			else{
				alert ('client is not compliant with geolocation');
			}

		},

		setPosition: function(latLng){
			this.positionLatLng = latLng;
			this.lat = latLng.lat();
			this.lon = latLng.lng();
			this.position = { coords: {latitude:this.lat, longitude:this.lon} }; //fake Geoposition Object
			console.log("SET NEW POSITION lat="+this.lat+"lon="+this.lon);

		},

		clearPosition: function(){
			this.position = null;
			this.lat = null;
			this.lon = null;
			this.positionLatLng = null;
		},

	    refreshPosition: function(okCallback,errCallback){
			this.clearPosition();
			this.clearData();
			this.getPosition(okCallback,errCallback);
	    },

		setFoursquareOptions: function(options){
			this.options = options;
		},

		loadFoursquareData: function(okCallback,errCallback){
		    var url = 'https://api.foursquare.com/v2/venues/search?oauth_token=3TM5ZFWDHOSXXDIG4KLCF4S4AY0OTZZZEE503CGX3L4HIV&v=20120205';
		    //https://developer.foursquare.com/docs/venues/search
		    //https://developer.foursquare.com/docs/explore#req=venues/categories
		    //4d4b7105d754a06377d81259=spazi aperti 4bf58dd8d48988d162941735=strade
			if(this.lastResult != null && this.lastProvider=='foursquare')
				return okCallback(this.lastResult);//from cache

		    var self = this;

		    this.getPosition(function(pos){
			    $.ajax({
			     url: url,
			     dataType: 'jsonp',
			     type: 'GET',
			     //crossDomain: true,
			     data:  {
			       ll: self.lat+','+self.lon,
			       llAcc: 10,
			       query: self.options.search || undefined,
			       intent: 'checkin',
			       radius: self.options.radius || 200,
			       categoryId: self.options.largespace ? '4d4b7105d754a06377d81259,4bf58dd8d48988d162941735':undefined,
			       limit: self.options.limit || 20
			       },


			     // on success
			     success: function(data, textStatus, jqXHR){
			    	self.lastResult = data.response.venues;//cache
			    	self.lastProvider = "foursquare";
					return okCallback(data.response.venues);
			     },

			     // on failure
			     error: function (jqXHR, textStatus, errorThrown){
			      console.log(jqXHR);
			      console.log(textStatus);
			      console.log(errorThrown);
			      if(errCallback)
			    	  return errCallback(textStatus,errorThrown);
			     }
			     });

		    });
		},

		setxanOptions: function(options){
			this.xanOptions = options;
		},

		loadxanData: function(okCallback,errCallback){

			//always refresh data
//			if(this.xanData != null)
//				return okCallback(this.xanData);

		    var self = this;
		    //this.setxanOptions({url: url });

		    this.getPosition(function(pos){
			    $.ajax({
			     url: self.xanOptions.url,
			     dataType: 'json',
			     type: 'GET',
			     //crossDomain: true,
			     data:  {
			       lat: self.lat,
			       lon: self.lon,
			       sex: self.xanOptions.sex || 'female',
			       keyword: self.xanOptions.keyword || undefined,
			       placeId: self.xanOptions.placeId || undefined,
			       radius: self.xanOptions.radius || self._xanRadius,
			       limit: self.xanOptions.limit || self._xanLimit
			       },

			     // on success
			     success: function(data, textStatus, jqXHR){
			    	//https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array
			    	//http://andrewdupont.net/2006/05/18/javascript-associative-arrays-considered-harmful/
			    	//http://stackoverflow.com/questions/4246980/how-to-create-a-simple-map-using-javascript-jquery
			    	self.xanData = data.length > 0 ? new Object() : null;
			    	self.xanAggregateData = data.length > 0 ? new Object() : null;

			    	$.each(data,function(index,value){
			    		if(index==0){
			    			self.xanAggregateData = value;
			    		}else{
				    		var uniqueName = $("<div/>").html(value.uniqueKey).text();
				    		self.xanData[uniqueName] = value;
			    		}
			    	});
			    	console.log("xan DATA NEW lat="+self.lat+"lon="+self.lon);

					return okCallback(self.xanData);
			     },

			     // on failure
			     error: function (jqXHR, textStatus, errorThrown){
			      console.log(jqXHR);
			      console.log(textStatus);
			      console.log(errorThrown);
			      if(errCallback)
			    	  return errCallback(textStatus,errorThrown);
			     }

			    });

		    });
		},
		/**
		 * @param place googleObject places
		 * @return xanobject || null
		 */
		existxanObject: function(place){
			var uniqueName = place.name+'#'+place.lat.toFixed(2)+'#'+place.lon.toFixed(2);//name#44.12#45.12
			return places.xanData ? places.xanData[uniqueName] : null;
		},

		clearxanData: function(){
			this.xanData = null;
		},

		/**
		 * init google request options
		 */
		setGoogleOptions: function(options){
			this.options = options;
		},


		rebuildGoogleMap: function(options){

			var self = this;
			//recreate map
			var map = new google.maps.Map(self.options.divMap, {
		          mapTypeId: google.maps.MapTypeId.ROADMAP,
		          draggable: true,
		          //panControl: true,
		          //panControlOptions: {position: google.maps.ControlPosition.BOTTOM_LEFT },
		          //scaleControl: true,
		          zoom: options.zoom || 14,
		          minZoom: 8, //avoid overview zoom
		          //maxZoom: 8,
		          zoomControl: true,
		          zoomControlOptions: {
		        		  position: google.maps.ControlPosition.TOP_LEFT,
		        		  style: google.maps.ZoomControlStyle.DEFAULT
		          },
		          streetViewControl: false,
		          mapTypeControl: false,
		          styles:[{
		              featureType:"poi",
		              elementType:"labels",
		              stylers:[{
		                  visibility:"off"
		              }]
		          }]
		        });

			self.addMapListener(map, null);

			self._map = map;
			self.getPosition(function(pos){
				var currentPos = new google.maps.LatLng(self.lat, self.lon);
				map.setCenter(currentPos);
			});
		},

		getMap: function(){
			return this._map;
		},

		addMapListener: function(map,cb){
	        var self = this;
			google.maps.event.addListener(map, 'idle', function() {
	        	console.log('bounds_changed');
	        	self.setPosition(map.getCenter());
	        	//cb('bounds_changed');
	        	//options.onBoundsChanged();
	        	$.Topic( "placesMapBoundChanged" ).publish();
	          });
//	        google.maps.event.addListener(places.getMap(), 'zoom_changed', function() {
//	        	console.log('zoom_changed');
//	        	cb('zoom_changed');
//	          });

		},


		/**
		 * get google places data (from cache if possible)
		 * lastResult $$ _map
		 *
		 */
		loadGoogleData: function(okCallback,errCallback){
			//http://code.google.com/intl/it-IT/apis/maps/documentation/javascript/examples/index.html
			//http://code.google.com/intl/it-IT/apis/maps/documentation/javascript/reference.html
			//http://code.google.com/intl/it-IT/apis/maps/documentation/places/supported_types.html
			//http://code.google.com/intl/it-IT/apis/maps/documentation/javascript/examples/places-autocomplete.html

			var self = this;
			//recreate map
			var map = new google.maps.Map(self.options.divMap, {
		          mapTypeId: google.maps.MapTypeId.ROADMAP,
		          disableDefaultUI: self.options.disableMapUI,
		          draggable: self.options.draggable,
		          zoom: 14,
		          maxZoom: 8,
		          zoomControl: true,
		          zoomControlOptions: {
	        		  position: google.maps.ControlPosition.TOP_LEFT,
	        		  style: google.maps.ZoomControlStyle.DEFAULT
	          	  },
		          streetViewControl: false,
		          mapTypeControl: false,
		          styles:[{
		              featureType:"poi",
		              elementType:"labels",
		              stylers:[{
		                  visibility:"off"
		              }]
		          }]
		        });
			self._map = map;

			//cache ?
			if(this.lastResult != null && this.lastProvider=='google'){
				console.log("GOOGLE DATA from cache lat="+self.lat+"lon="+self.lon);
				return okCallback(this.lastResult);
			}


			this.getPosition(function(pos){
				var currentPos = new google.maps.LatLng(self.lat, self.lon);

				map.setCenter(currentPos);

		        var request = {
		          location: currentPos,
		          radius: self.options.radius || 2000,
		          types: self.options.types || undefined, //['route|political'],
		          keyword: self.options.keyword || undefined
		        };
		        //infowindow = new google.maps.InfoWindow();


		        var timeoutFired = false;
		        var timeout = function(){
		        	timeoutFired = true;
		        	console.log("TIMEOUT GOOGLE DATA");
					if(errCallback)
						return errCallback("timeout");
		        };
		        var timeoutTimerId = setTimeout ( timeout, self.options.timeout || self._timeout );

				var callback = function(results, status){
					if(timeoutFired)
						return okCallback(null);

					if (status == google.maps.places.PlacesServiceStatus.OK) {
						clearTimeout ( timeoutTimerId );
						self.lastResult = results;//cache
				    	self.lastProvider = "google";
				    	console.log("GOOGLE DATA NEW lat="+self.lat+"lon="+self.lon);

				    	return okCallback(results);
					}
					if(errCallback)
						return errCallback(status);

				};

				var service = new google.maps.places.PlacesService(map);
				service.search(request, callback);

			});
		},

		/**
		 * crea Marker from place in _map
		 * @param place googleObject || xanObject
		 *
		 * utility  http://code.google.com/p/google-maps-utility-library-v3/wiki/Libraries
		 * label
		 * http://google-maps-utility-library-v3.googlecode.com/svn/tags/markerwithlabel/
		 *
		 * cluster
		 * http://google-maps-utility-library-v3.googlecode.com/svn/tags/markerclustererplus/
		 *
		 */
		createMarker: function(place,options) {

			var options = options || {};
			var self = this;
			var placePos = place.geometry ? place.geometry.location : new google.maps.LatLng(place.lat, place.lon);

			var markerImg = null;
			if(options.iconUrl){
				markerImg = self._createMarkerImage( options.iconUrl );
			}

			//clickable shape
			var shape = {
				      coord: [1, 1, 1, 32, 32, 32, 32 , 1],
				      type: 'poly'
				  };

			var realRate = place.real_rating ? " "+place.real_rating : " ";
			var title = options.title || place.name+realRate;

            //create marker
			//var marker = new google.maps.Marker({
			var marker = new MarkerWithLabel({
		        map: self._map,
		        position: placePos,
		        icon: markerImg,
		        /*shadow: shadow,*/
		        //shape: shape,
		        title: options.title || title,
		        //zIndex: options.zIndex || null,
		        //markerWithLabel
		        draggable: false,
		        raiseOnDrag: false,
		        labelContent: "<span class='"+options.sex+"'>"+parseFloat(realRate).toFixed(1)+"</span>",
		        //labelAnchor: new google.maps.Point(8, 10),
		        labelAnchor: new google.maps.Point(8, -10),
		        labelClass: "labels", // the CSS class for the label
		        labelStyle: { opacity: 1.0},
		        labelInBackground: false
		    });

		      //marker click event ?
			if(options.clickCallback && options.infoWindowCnt){
				  var infowindow = new google.maps.InfoWindow();

				  google.maps.event.addListener(marker, 'click', function() {
			    	infowindow.setContent(options.infoWindowCnt);

			        infowindow.open(self._map, this);
			        options.clickCallback(this);//callback with this
			      });
			}
			return marker;

		 },

		 _createMarkerImage: function(urlImage,urlShadow){
			  // Marker sizes are expressed as a Size of X,Y
			  // where the origin of the image (0,0) is located
			  // in the top left of the image.

			  // Origins, anchor positions and coordinates of the marker
			  // increase in the X direction to the right and in
			  // the Y direction down.
			  var image = new google.maps.MarkerImage(urlImage,
			      // This marker is 20 pixels wide by 32 pixels tall.
			      new google.maps.Size(32, 32),
			      // The origin for this image is 0,0.
			      new google.maps.Point(0,0),
			      // The anchor for this image is the base of the flagpole at 0,32.
			      new google.maps.Point(16, 16)
			  );

			  /*var shadow = new google.maps.MarkerImage(urlShadow,
			      // The shadow image is larger in the horizontal dimension
			      // while the position and offset are the same as for the main image.
			      new google.maps.Size(37, 32),
			      new google.maps.Point(0,0),
			      new google.maps.Point(0, 32));*/

			      // Shapes define the clickable region of the icon.
			      // The type defines an HTML &lt;area&gt; element 'poly' which
			      // traces out a polygon as a series of X,Y points. The final
			      // coordinate closes the poly by connecting to the first
			      // coordinate.

			  return image;
		 },

		 /**
		  * SetCenter place in _map
		  * @param place googleObject || xanObject
		  * @param zoom int 1 (high)  17 (close)
		  */
		centeringMap: function(place,zoom){
			 var self = this;
			 //center place in map
            if (place.geometry && place.geometry.viewport) {
            	self._map.fitBounds(place.geometry.viewport);
            }
            var placePos = place.geometry ? place.geometry.location : new google.maps.LatLng(place.lat, place.lon);

            self._map.setCenter(placePos);

            self._map.setZoom(zoom || 15);

		 },


		 /**
		  * Place Detail Request Results Different From Autocomplete Text
		  * http://groups.google.com/group/google-places-api/browse_thread/thread/187375f42b558785
		  *
		  * places.setGoogleOptions({searchField:inputDomNode,  divMap: mapDomNode, types:['geocode'] });
		  *
		  * places.setGoogleOptions({searchField:inputDomNode,  divMap: mapDomNode, types:['geocode'] });
		  */
		 initGoogleAutocomplete: function(params){

 		 var self = this;
         var currentPos = new google.maps.LatLng(self.lat, self.lon);//init with currentPos

         var map = new google.maps.Map(params.divMap, {
		          mapTypeId: google.maps.MapTypeId.ROADMAP,
		          center: currentPos,
		          zoom: 15,
		          zoomControl: false,
		          streetViewControl: false,
		          mapTypeControl: false
		        });

		 // separate autocomplete _map ?
		 self._map = map;

          var input = params.searchField;
          var autocomplete = new google.maps.places.Autocomplete(input);

          //automatic bindTo ?
          params.autoBind = params.autoBind ? params.autoBind : true;
          if(params.autoBind)
        	  autocomplete.bindTo('bounds', map);

          autocomplete.setTypes(params.types);
          self._autocomplete = autocomplete;

//          var infowindow = new google.maps.InfoWindow();
//          var marker = new google.maps.Marker({
//            map: map
//          });

          google.maps.event.addListener(autocomplete, 'place_changed', function() {
            //infowindow.close();
            var place = autocomplete.getPlace();
            self.lastAutocomplete = place;

            /* set currentPos to autocomplete place result ?*/
            self.setPosition(place.geometry.location);

            //set Place as lastResult ?
//			self.lastResult = [place];//cache
//	    	self.lastProvider = "google";

            if (place.geometry.viewport) {
              self._map.fitBounds(place.geometry.viewport);
            } else {
              self._map.setCenter(place.geometry.location);
              self._map.setZoom(17);  // Why 17? Because it looks good.
            }

//            var image = new google.maps.MarkerImage(
//                place.icon,
//                new google.maps.Size(71, 71),
//                new google.maps.Point(0, 0),
//                new google.maps.Point(17, 34),
//                new google.maps.Size(35, 35));
//            marker.setIcon(image);
//            marker.setPosition(place.geometry.location);
//
//            var address = '';
//            if (place.address_components) {
//              address = [(place.address_components[0] &&
//                          place.address_components[0].short_name || ''),
//                         (place.address_components[1] &&
//                          place.address_components[1].short_name || ''),
//                         (place.address_components[2] &&
//                          place.address_components[2].short_name || '')
//                        ].join(' ');
//            }
//
//            infowindow.setContent('<div><strong>' + place.name + '</strong><br>' + address);
//            infowindow.open(map, marker);
          });

		 },

		getGooglePlaceDetails: function(okCallback,errCallback){
		},

		/**
		 * get lastResult (any provider) only from cache
		 */
		getData: function(okCallback,errCallback){
			if(this.lastResult != null)
				return okCallback(this.lastResult);//from cache
		},
		clearData: function(){
			this.lastResult = null;
			this.lastProvider = null;
		},

		/**
		 * parse foursquare place object in generic place object
		 * @param foursquare Object
		 * @return Object
		 */
		_parseFoursquarePlace: function(place){
			var obj = {};
			var icon = place.categories[0] ? place.categories[0].icon : null;
			var iconName = icon ? icon.prefix+icon.sizes[0]+icon.name : 'https://foursquare.com/img/categories/travel/highway_32.png';

			obj.icon = iconName;
			obj.id = place.id;
			obj.reference = "";
			obj.name = place.name;
			obj.address = place.location.address;
			obj.city = place.location.city;
			obj.country = place.location.country;
			obj.state = place.location.state;
			obj.distance = place.location.distance;
			obj.lat = place.location.lat;
			obj.lon = place.location.lng;
			obj.provider = "foursquare";
			obj.source = place;

			return obj;
		},

		geo: new google.maps.Geocoder(),

		/**
		 * parse google place object in generic place object
		 * @param googlePlace Object
		 * @return Object
		 */
		_parseGooglePlace: function(place,options,cb){
			//http://stackoverflow.com/questions/5346392/trying-to-find-the-distance-between-two-latlng-objects-using-googlemaps-api-and
			//google.maps.geometry.spherical.computeDistanceBetween(from:LatLng, to:LatLng, radius?:number)

			var dist = google.maps.geometry.spherical.computeDistanceBetween(this.positionLatLng, place.geometry.location);


			var country = "";
			var city = "";
			var state = ""; //province

			var createObj = function(){
				var obj = {};
				obj.icon = place.icon;
				obj.id = place.id;
				obj.reference = place.reference;
				obj.name = place.name;
				obj.address = place.vicinity || "" ;
				obj.city = city;
				obj.country = country;
				obj.state = state; //province
				obj.distance = dist.toFixed(2) || "";
				obj.lat = place.geometry.location.lat();
				obj.lon = place.geometry.location.lng();
				obj.provider = "google";
				obj.source = place;

				return cb(obj);

			};

			if(options.geocode == true){

				this.geo.geocode({location: place.geometry.location},function(results,status){
					if (status == google.maps.GeocoderStatus.OK) {
						for (i=0; i<results[0].address_components.length; i++){

							for (j=0;j<results[0].address_components[i].types.length; j++){

								if(results[0].address_components[i].types[j]=="country")
						          country = results[0].address_components[i].short_name;

								if(results[0].address_components[i].types[j]=="locality")
									city = results[0].address_components[i].short_name;

								if(results[0].address_components[i].types[j]=="administrative_area_level_2")
									state = results[0].address_components[i].short_name;
						    }
						}
					}
					createObj();

				});
			}else{
				createObj();
			}


		},

		/**
		 * get place object
		 * @param index
		 * @return Object place (generic)
		 */
		getItem: function(index,options,cb){
			var place =  this.lastResult ? this.lastResult[index] : null;
			if(!place)
				return null;

			switch(this.lastProvider)
			{
				case "foursquare":
					return this._parseFoursquarePlace(place,options,cb);

				case "google":
					return this._parseGooglePlace(place,options,cb);

			}
		}


	};

	//singleton pattern dojox/mobile/ProgressIndicator
//	AjaxService._instance = null;
//	AjaxService.getInstance = function(){
//		if(!AjaxService._instance){
//			AjaxService._instance = new AjaxService();
//		}
//		return AjaxService._instance;
//	};

	return places;

});




