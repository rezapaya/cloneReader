$.extend({
	namespace: function() {
		var a = arguments,
			o = null,
			i, j, d;
		
		for (i = 0; i < a.length; i = i + 1) {
			d = a[i].split(".");
			o = window;
			
			for (j=0; j<d.length; j=j+1) {
				o[d[j]] = o[d[j]] || {};
				o = o[d[j]];
			}
		}
		
		return o;
	},
	
	getUrlVars: function(){
		var vars = {}, hash;
		if (window.location.href.indexOf('?') == -1) {
			return vars;
		}
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		for(var i = 0; i < hashes.length; i++) {
			var hash = hashes[i].split('=');
			if (hash.length == 2) {
				vars[hash[0]] = hash[1];
			}
		}
		return vars;
	},

	getAppType: function() {
		// TODO: mejorar esta parte, hacer constantes o algo asi
		if ($.inArray(this.appType, ['appAjax', 'appMobile', 'webSite']) != -1) {
			return this.appType;
		}

		return 'webSite';
	},

	isMobile: function() {
		return $(window).width() < 768;		
	},	
	
	validateEmail: function(value) {
		if (value == '') {
			return true;
		}
		var filter = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
		return !!filter.test(value);
	},

	validateUrl: function(value) {
		if (value.length == 0) { return true; }
 
		if(!/^(https?|ftp):\/\//i.test(value)) {
			value = 'http://' + value;
		}
		
		var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
		return regexp.test(value);
	},
	
	strPad: function(i,l,s) {
		var o = i.toString();
		if (!s) { s = '0'; }
		while (o.length < l) {
			o = s + o;
		}
		return o;
	},	
	
	base64Decode: function( data ) {
		var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
		var o1, o2, o3, h1, h2, h3, h4, bits, i = 0, ac = 0, dec = "", tmp_arr = [];
	
		if (!data) {
			return data;
		}
	
		data += '';
	
		do {
			h1 = b64.indexOf(data.charAt(i++));
			h2 = b64.indexOf(data.charAt(i++));
			h3 = b64.indexOf(data.charAt(i++));
			h4 = b64.indexOf(data.charAt(i++));
	
			bits = h1<<18 | h2<<12 | h3<<6 | h4;
	
			o1 = bits>>16 & 0xff;
			o2 = bits>>8 & 0xff;
			o3 = bits & 0xff;
	
			if (h3 == 64) {
				tmp_arr[ac++] = String.fromCharCode(o1);
			} else if (h4 == 64) {
				tmp_arr[ac++] = String.fromCharCode(o1, o2);
			} else {
				tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
			}
		} while (i < data.length);
	
		dec = tmp_arr.join('');
		dec = $.utf8Decode(dec);
	
		return dec;
	},
	
	base64Encode: function(data) {
		var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
		var o1, o2, o3, h1, h2, h3, h4, bits, i = 0, ac = 0, enc="", tmp_arr = [];
	
		if (!data) {
			return data;
		}
	
		data = $.utf8Encode(data+'');
	
		do { // pack three octets into four hexets
			o1 = data.charCodeAt(i++);
			o2 = data.charCodeAt(i++);
			o3 = data.charCodeAt(i++);
	
			bits = o1<<16 | o2<<8 | o3; h1 = bits>>18 & 0x3f;
			h2 = bits>>12 & 0x3f;
			h3 = bits>>6 & 0x3f;
			h4 = bits & 0x3f;
	
			// use hexets to index into b64, and append result to encoded string
			tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
		} while (i < data.length);
	
		enc = tmp_arr.join('');
	
		switch (data.length % 3) {
			case 1:
				enc = enc.slice(0, -2) + '==';
				break;
			case 2:
				enc = enc.slice(0, -1) + '=';
				break;
		}
	
		return enc;
	},
	
	utf8Decode: function( str_data ) {
		var tmp_arr = [], i = 0, ac = 0, c1 = 0, c2 = 0, c3 = 0;
	
		str_data += '';
	
		while ( i < str_data.length ) {
			c1 = str_data.charCodeAt(i);
			if (c1 < 128) {
				tmp_arr[ac++] = String.fromCharCode(c1);
				i++;
			} else if ((c1 > 191) && (c1 < 224)) {
				c2 = str_data.charCodeAt(i+1);
				tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
				i += 2;
			} else {
				c2 = str_data.charCodeAt(i+1);
				c3 = str_data.charCodeAt(i+2);
				tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
		}
	
		return tmp_arr.join('');
	},
	
	utf8Encode: function( argString ) {
		var string = (argString+''); // .replace(/\r\n/g, "\n").replace(/\r/g, "\n");
	
		var utftext = "", start, end, stringl = 0;
	
		start = end = 0;
		stringl = string.length;
		for (var n = 0; n < stringl; n++) {
			var c1 = string.charCodeAt(n);
			var enc = null;
	
			if (c1 < 128) {
				end++;
			}
			else if (c1 > 127 && c1 < 2048) {
				enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
			}
			else {
				enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
			}
			if (enc !== null) {
				if (end > start) {
					utftext += string.slice(start, end);
				}
				utftext += enc;
				start = end = n+1;
			}
		}
	
		if (end > start) {
			utftext += string.slice(start, stringl);
		}
	
		return utftext;
	},
	
	stripTags: function(str, allowed_tags) {
		var key = '', allowed = false;
		var matches = [];
		var allowed_array = [];
		var allowed_tag = '';
		var i = 0;
		var k = '';
		var html = '';
		var replacer = function (search, replace, str) {
			return str.split(search).join(replace);
		};
		// Build allowes tags associative array
		if (allowed_tags) {
			allowed_array = allowed_tags.match(/([a-zA-Z0-9]+)/gi);
		}
		str += '';
		// Match tags
		matches = str.match(/(<\/?[\S][^>]*>)/gi);
		// Go through all HTML tags
		for (key in matches) {
			if (isNaN(key)) {
				// IE7 Hack
				continue;
			}
			// Save HTML tag
			html = matches[key].toString();
			// Is tag not in allowed list? Remove from str!
			allowed = false;
			// Go through all allowed tags
			for (k in allowed_array) {
				// Init
				allowed_tag = allowed_array[k];
				i = -1;
				if (i != 0) { i = html.toLowerCase().indexOf('<'+allowed_tag+'>');}
				if (i != 0) { i = html.toLowerCase().indexOf('<'+allowed_tag+' ');}
				if (i != 0) { i = html.toLowerCase().indexOf('</'+allowed_tag)   ;}
	
				// Determine
				if (i == 0) {
					allowed = true;
					break;
				}
			}
			if (!allowed) {
				str = replacer(html, "", str); // Custom replace. No regexing
			}
		}
		return str;
	},
	
	showNotification: function(msg, className){
		if (className == null) {
			className = 'alert-success';
		}
		$div = $('<div class="alert ' + className +' fade in navbar-fixed-top"><strong>' + msg + '</strong></div>')
			.appendTo('body')
			.fadeTo('slow', 0.95).delay(2000).slideUp('slow');
	},
	
	showWaiting: function(forceWaiting) {
		/*
		 * TODO:
		 * Para forzar que muestre o oculte el div, sumo o resto a la variable countProcess; pensar si hay una forma mas elegante de resolver esto. 
		 */
		if ($.countProcess < 0) { $.countProcess = 0; }
		if (forceWaiting == true) {$.countProcess++;}
		if (forceWaiting == false) {$.countProcess--;}
				
		var isLoading = ($.countProcess > 0);

	
		$('#divWaiting').css( { 'display':	isLoading == true ? 'block' : 'none' } );
		
		$('#divWaiting').appendTo('body');
		
		$('body').removeClass('isLoading');
		if (isLoading == true) {
			$('body').addClass('isLoading');
		}
	},	
	
	urlToHashUrl: function(url) {
		if (url.indexOf('#') != -1) {
			return url.substr(url.indexOf('#'));
		}

		return '#' + url.replace(base_url, '');
	},
	
	goToUrl: function(url) {
		if ($.getAppType()  == 'webSite') {
			$.showWaiting(true);
			location.href = url;
			return;
		}
		
		url = $.urlToHashUrl(url);
		$.goToHashUrl(url);
	},
	
	goToHashUrl: function(url) {
		location.hash = url;
	},	
	
	goToUrlList: function() {
		var urlList = $.getParamUrl('urlList');
		if (urlList != null) {
			$.goToUrl($.base64Decode(decodeURIComponent(urlList)));
		}
	},
	
	getHashUrl: function() {
		return location.hash.slice(1);
	},
	
	getParamUrl: function(paramName) {
		if ($.getAppType()  == 'webSite') {
			return $.url().param(paramName);
		}
		
		var params = $.getUrlVars();
		return params[paramName];		
	},
	
	ISODateString: function(d){
		function pad(n) {return n<10 ? '0'+n : n}
		return d.getUTCFullYear()+'-'
		+ pad(d.getUTCMonth()+1)+'-'
		+ pad(d.getUTCDate()) +' '
		+ pad(d.getUTCHours())+':'
		+ pad(d.getUTCMinutes())+':'
		+ pad(d.getUTCSeconds())
	},
	
	formatDate: function($element) {
		var value = $element.text();
		if (value == '') {
			return;
		}
		if (moment($element.text(), 'YYYY-MM-DDTHH:mm:ss').isValid() == false) {
			return;
		}
		
		var format = _msg['MOMENT_DATE_FORMAT'];
		if ($element.hasClass('datetime')) {
			format += ' HH:mm:ss';
		}
		$element.text( moment($element.text(), 'YYYY-MM-DDTHH:mm:ss' ).format( format) );		
	},
	
	showModal: function($modal, keyboard, onCloseRemove) {
		$('body').addClass('modal-open');
		
		$modal.data('onCloseRemove', onCloseRemove == null ? true : onCloseRemove);
		
		$modal.modal( { 'backdrop': 'static', 'keyboard': keyboard });


		$('.modal').css('z-index', 1029);;

		
		$(document).unbind('hidden.bs.modal');
		$(document).bind('hidden.bs.modal', function (event) {
			if ($(event.target).data('onCloseRemove') == true) {
				$(event.target).remove();
				$(this).removeData('bs.modal');
			}
			
			$(document.body).removeClass('modal-open');
			if ($('.modal-backdrop').length > 0) {
				$('.modal-backdrop').last().show();
				$('body').addClass('modal-open');
				$('.modal:last').css('z-index', 1040);;
			}
		}); 
		
		$(document).off('focusin.modal');
		
		$('.modal-backdrop').hide();

		$('.modal-backdrop:last')
			.css( {'opacity': 0.3  } )
			.show();
		$('.modal:last').css('z-index', 1040);;
	},
	
	hasAjaxErrorAndShowAlert: function(result) {
		if (result == null) {
			$(document).crAlert('error');
			return true;
		}
		if (result['code'] != true) {
			$(document).crAlert(result['result']);
			return true;
		}
		return false;
	}
});


$(document).ready(function() {
	crMenu.initMenu();
	resizeWindow();
	
	$.showWaiting(true);
	$('a').click(function(event) {
		if (event.button != 0) {
			return;
		}
		
		var url = $(event.target).attr('href');
		if (url == null || url.substr(0, 1) == '#') {
			return;
		}
		event.preventDefault();
		return $.goToUrl(url);
	});	
	
	$.countProcess = 0;
	
	$.ajaxSetup({dataType: "json"});
	
	$(document).ajaxSend(
		function(event, jqXHR, ajaxOptions) {
			if (ajaxOptions.skipwWaiting === true) {
				return;
			}
			$.countProcess ++;
			$.showWaiting();	
		}
	);
	 
	$(document).ajaxComplete(
		function(event, jqXHR, ajaxOptions) {
			if (ajaxOptions.skipwWaiting === true) {
				return;
			}			
			$.countProcess --;
			$.showWaiting();	
		}
	);
	
	$(document).ajaxError(
		function(event, jqXHR, ajaxOptions) {
			if (jqXHR.status === 0 && jqXHR.statusText === 'abort') {
				return;
			}
			if (jqXHR.status === 0 && jqXHR.statusText === 'error') {
				$(document).crAlert( {
					'msg': 			_msg['Not connected. Please verify your network connection'],
					'isConfirm': 	true,
					'confirmText': 	_msg['Retry'],
					'callback': 	$.proxy(
						function() { $.ajax(ajaxOptions); }
					, this)
				});
				return;
			}
			
			var result = $.parseJSON(jqXHR.responseText);
			$.hasAjaxErrorAndShowAlert(result);
		}
	);
});
	
$(window).resize(function() {
	resizeWindow();
});

function resizeWindow() {
	return;
	$('.content')
		.css('min-height', 1)
		.css('min-height', $(document).outerHeight(true) - $('.menu').offset().top - $('.menu').outerHeight(true) - $('footer').outerHeight(true) ); 
}

function cn(value) {
	console.log(value);
}
