$(
	function () {
		
		var url = window.location;
		$('nav ul.nav li a').filter(function() { return this.href == url; }).parent().addClass('active');

		$('article').removeClass('col-sm-12');
		$('article').addClass('col-sm-9');
		
		var side = $('<div role="contentinfo" id="sidebar" class="col-sm-3 hidden-xs">').insertAfter('article');
		
		side.append('<aside id="sidebar-top"><img class="img-responsive img-thumbnail" src="http://www.gravatar.com/avatar/c42f5f533d5e2032aae76abe2eb3584b?s=192" alt="(tobyink)"><h1 class="h4">Toby Inkster</h1><p>Lewes, East Sussex, UK</p></aside>');
		side.append('<aside id="brain-activity"><h1 class="h4">Thoughts <a href="http://tobyink.soup.io/rss/original"><img src="/assets/feed-icon-14x14.png" alt="(feed)"></a></h1><ul class="list-group"></ul></aside>');
		side.append('<aside id="gh-activity"><h1 class="h4">GitHub Activity <a href="http://github.com/tobyink.atom"><img src="/assets/feed-icon-14x14.png" alt="(feed)"></a></h1><ul class="list-group"></ul></aside>');
		
		$.getScript(
			'/assets/jquery.timeago.js',
			function () {
				$.getScript(
					'/assets/jquery.github-activity.js',
					function () {
						$("#gh-activity ul").githubActivityFor("tobyink", { limit: 6 });
					}
				);
			}
		);
			
		$.get(
			'http://www.corsproxy.com/tobyink.soup.io/rss/original',
			function (data) {
				var items = data.getElementsByTagName('item');
				var thoughts = $('#brain-activity ul');
				var count = 0;
				$(items).each(function (i, e) {
					count++;
					if (count > 6) return;
					thoughts.append('<li class="list-group-item">'
						+ e.getElementsByTagName('description')[0].textContent
						+ '<small class="text-muted">' 
						+ $.timeago( new Date(e.getElementsByTagName('pubDate')[0].textContent) )
						+ '</small></li>');
				});
			}, 'xml'
		);
	}
);
