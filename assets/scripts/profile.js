$.Profile = {
	init: function() { 	
		this.aTabs = $('.userProfile li')
		for (var i=0; i<this.aTabs.length; i++) {
			var tab = this.aTabs[i];

			$(tab).find('a').click($.proxy(
				function(event) {
					var $link 		= $(event.target);
					var $content 	= $($link.attr('href'));
					this.loadTab($content.data('controller'), $content);
				}
			, this));
		}
		
		$(this.aTabs[0]).find('a').click();
	},
	
	loadTab: function(controller, $content) {
		if ($content.children().length > 0) {
			$('.content > .pageTitle h2').text( $content.find('.panel-heading').text() );
			return;
		}
		
		$.ajax( {
			'type': 		'get', 
			'url':			controller,
			'success':		
				$.proxy(
					function (result) {
						if ($.hasAjaxErrorAndShowAlert(result) == true) { return; }
						
						result = $(result['result']);
						$content.children().remove();
						$content.html(result);
						
						$('.content > .pageTitle h2').text( $content.find('.panel-heading').text() );
					}
				, this),
		});
	}
};


$(document).ready(function() {
	$.Profile.init();
});
