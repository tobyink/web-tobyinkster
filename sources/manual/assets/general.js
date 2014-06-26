$(
	function () {
		
		var url = window.location;
		$('nav ul.nav li a').filter(function() { return this.href == url; }).parent().addClass('active');

		$('article').removeClass('col-sm-12');
		$('article').addClass('col-sm-9');
		
		var side = $('<div role="contentinfo" id="sidebar" class="col-sm-3 hidden-xs">').insertAfter('article');
		
		side.append(
			'<aside id="sidebar-top"><img class="img-responsive img-thumbnail" src="http://www.gravatar.com/avatar/c42f5f533d5e2032aae76abe2eb3584b?s=192" alt="(tobyink)"><h1 class="h4">Toby Inkster</h1><p>Lewes, East Sussex, UK</p></aside>' +
			'<ul class="nav nav-tabs">' +
			'  <li class="active"><a href="#brain-activity" data-toggle="tab">Think</a></li>' +
			'  <li><a href="#gh-activity" data-toggle="tab">Code</a></li>' +
			'  <li><a href="#blog-activity" data-toggle="tab">Write</a></li>' +
			'</ul>' +
			'<div class="tab-content">' +
			'  <aside class="tab-pane active" id="brain-activity"><h1 class="h4">Thoughts <a href="http://tobyink.soup.io/rss/original"><img src="/assets/feed-icon-14x14.png" alt="(feed)"></a></h1><ul class="list-group"></ul></aside>' +
			'  <aside class="tab-pane" id="gh-activity"><h1 class="h4">GitHub Activity <a href="http://github.com/tobyink.atom"><img src="/assets/feed-icon-14x14.png" alt="(feed)"></a></h1><ul class="list-group"></ul></aside>' +
			'  <aside class="tab-pane" id="blog-activity"><h1 class="h4">Blog <a href="http://toby.ink/blog/index.atom"><img src="/assets/feed-icon-14x14.png" alt="(feed)"></a></h1><ul class="list-group"></ul></aside>' +
			'</div>'
		);
		
		$.getScript(
			'/assets/jquery.timeago.js',
			function () {
				$.get(
					'http://www.corsproxy.com/tobyink.soup.io/rss/original',
					function (data) {
						var items = data.getElementsByTagName('item');
						var thoughts = $('#brain-activity ul');
						var count = 0;
						$(items).each(function (i, e) {
							count++;
							if (count > 10) return;
							thoughts.append('<li class="list-group-item">'
								+ e.getElementsByTagName('description')[0].textContent
								+ '<small class="text-muted">' 
								+ $.timeago( new Date(e.getElementsByTagName('pubDate')[0].textContent) )
								+ '</small></li>');
						});
					}, 'xml'
				);				
				$.getScript(
					'/assets/jquery.github-activity.js',
					function () {
						$("#gh-activity ul").githubActivityFor("tobyink", { limit: 14 });
					}
				);
				$.get(
					'http://toby.ink/blog/index.json',
					function (data) {
						var blog = $('#blog-activity ul');
						for (var i in data) {
							if (i >= 16) return;
							blog.append(
								'<li class="list-group-item">'
								+ '<a href="' + data[i].link + '">' + data[i].title + '</a>'
								+ '<small class="text-muted">' 
								+ $.timeago( new Date(data[i].published) )
								+ '</small></li>'
							);
						}
					}, 'json'
				);
			}
		);
			
	}
);
