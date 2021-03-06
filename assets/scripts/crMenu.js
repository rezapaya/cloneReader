crMenu = {
	renderMenu: function(aMenu, className, $parent){
		if (aMenu.length == 0) {
			return;
		}
		
		var $ul = $('<ul></ul>').appendTo($parent);
		if (className != null) {
			$ul.addClass(className);
		}
	
		
		for (var i=0; i<aMenu.length; i++) {
			var label 	= _msg[aMenu[i]['label']];
			if (label == null) {
				label = aMenu[i]['label'];
			}
			var $li 	= $('<li></li>').appendTo($ul);
			var $link 	= $('<a></a>')
				.appendTo($li)
				.attr('title', label)
				.text(label);
			
			if (aMenu[i]['url'] != null) {
				$link.attr('href', '#' + aMenu[i]['url']);
			}
			if (aMenu[i]['icon'] != null) {
				$link.prepend(' <i class="' + aMenu[i]['icon'] + '" ></i> ')
			}
			
			if (aMenu[i]['childs'].length > 0) {
				$link.addClass('dropdown-toggle').attr('data-toggle', 'dropdown');
				this.renderMenu(aMenu[i]['childs'], null, $li);
			}
		}
	},
	
	initMenu: function() {
		var $menuProfile = $('ul.menuProfile');
		
		var $iconGear 	= $menuProfile.find('.icon-gear');
		var $settings 	= $iconGear.parent();
		var label		= $settings.text();
		$settings
			.addClass('settings').css('cursor', 'pointer')
			.html('')
			.append($iconGear)
			.append('<span>' + label + '</span>');
		$settings.parent().find('> ul > li').addClass('dropdown-submenu dropdown-submenu-left');	

		
		
		$menuProfile.find('.lang-' + langId ).before('<i class="icon-ok icon-fixed-width"></i>');
		$menuProfile.find('.icon-flag-alt').parent()
			.append('<span class="badge pull-right">' + langId + '</span>')
			.addClass('lang').css('cursor', 'pointer');
	
		$menuProfile.find('li ul').addClass('dropdown-menu');

		$('ul.dropdown-menu [data-toggle=dropdown]').on('click', 
			function(event) {
				event.preventDefault(); 
				event.stopPropagation(); 
				
				var expand = $(this).parent().hasClass('open');
				
				$('ul.dropdown-menu [data-toggle=dropdown]').parent().removeClass('open');
				
				if (expand == false) {
					$(this).parent().addClass('open');
				}
			}
		);
	},		
};