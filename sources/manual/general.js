$(
	function () {
		
		$('article').addClass('with-sidebar');
		
		var side = $('<div role="contentinfo" id="sidebar">').insertBefore('footer');
		
		side.append('<aside id="sidebar-top"><img src="http://www.gravatar.com/avatar/c42f5f533d5e2032aae76abe2eb3584b?s=192" alt="(tobyink)"><h1>Toby Inkster</h1><p>Lewes, East Sussex, UK</p></aside>');
		side.append('<aside id="brain-activity"><h1>Thoughts</h1><ul></ul></aside>');
		side.append('<aside id="gh-activity"><h1>GitHub Activity</h1><ul></ul></aside>');
		
		$.getScript(
			'/jquery.timeago.js',
			function () {
				$.getScript(
					'/jquery.github-activity.js',
					function () { $("#gh-activity ul").githubActivityFor("tobyink", { limit: 10 }) }
				);
			}
		);
		
		$.get(
			'http://www.corsproxy.com/tobyink.soup.io/rss/original',
			function (data) {
				var items = data.getElementsByTagName('item');
				var thoughts = $('#brain-activity ul');
				$(items).each(function (i, e) {
					thoughts.append('<li>'
						+ e.getElementsByTagName('description')[0].textContent
						+ '<small>' 
						+ $.timeago( new Date(e.getElementsByTagName('pubDate')[0].textContent) )
						+ '</small></li>');
				});
			}, 'xml'
		);
			
		/*
		$.getJSON(
			'https://spreadsheets.google.com/feeds/cells/0ArSnKUfCBx-QdFQ3LW9EbUtqVDl4eWJ5NjJBUWwyOHc/od6/public/basic?alt=json',
			function (data) {
				var entries  = data.feed.entry;
				var thoughts = $('#brain-activity ul');
				for (var i in entries) {
					var e = entries[i];
					thoughts.append('<li>' + e.content['$t'] + '<small>' + $.timeago(e.updated['$t']) +'</small></li>');
				}
			}
		);
		*/
	}
);
