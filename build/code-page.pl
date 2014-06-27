#!/usr/bin/env perl

use strict;
use warnings;
use lib qw( /opt/perl/lib/lib/perl5/ );

use JSON::PP;
use Path::Tiny;
use LWP::UserAgent;
use HTTP::Link::Parser;
use Data::Dumper;
use version;

{
	package Utils;
	
	sub yell {
		my $fmt = shift;
		printf STDERR "$fmt\n", @_;
	}
	
	sub mirror {
		my ($ua, $cache, $url, $name) = @_;
		my $i = 0;
		
		my $firstfile = $cache->child(sprintf $name, $i);
		return
			if  $firstfile->exists
			and $firstfile->stat->mtime > time() - 3600;
		
		my $next = $url;
		GET: {
			my $file = $cache->child(sprintf $name, $i);
			yell "$next -> $file";
			my @hdrs = ($next =~ /travis/)
				? (Accept => 'application/vnd.travis-ci.2+json')
				: (Accept => 'application/json');
			my $res = $ua->get($next, @hdrs);
			
			die $res->status_line
				unless $res->is_success;
			
			$file->spew_raw($res->content);
			
			my ($link) = grep {
				my $rel = $_->{rel};
				grep /^next$/i, @$rel;
			} @{ HTTP::Link::Parser::parse_links_to_list($res) };
			
			if ($link) {
				$i++;
				$next = $link->{URI};
				redo GET;
			}
		}
	}
	
	sub readjson {
		my ($json, $cache, $file, $path) = @_;
		my $i = 0;
		
		my @all;
		while ($cache->child(sprintf $file, $i)->exists) {
			
			yell "Reading %s", $cache->child(sprintf $file, $i);
			
			my $in = $json->decode(
				scalar $cache->child(sprintf $file, $i)->slurp_raw
			);
			
			my @path = @{ $path or [] };
			while (@path) {
				my $key = shift @path;
				ref($in) eq 'ARRAY'
					? ($in = $in->[$key])
					: ($in = $in->{$key});
			}
			
			push @all, @$in;
			$i++;
		}
		
		return @all;
	}
}

{
	package Project;
	
	use constant {
		CPAN_IDENT       => 'TOBYINK',
		BITBUCKET_IDENT  => 'tobyink',
		GITHUB_IDENT     => 'tobyink',
		TRAVIS_IDENT     => 'tobyink',
		COVERALLS_IDENT  => 'tobyink',  # spotted the pattern yet?
	};
	
	{ use Moo; }
	has [qw/
		name abstract licence bugtracker homepage
		latest_release latest_date latest_download
		developer_release developer_date developer_download
		cpan github bitbucket lang link
		travis last_build_state last_build_id
		coveralls coverage coverage_branch
	/] => (
		is        => 'rw',
		clearer   => 1,
		predicate => 1,
	);
	sub BUILD { push our @ALL, shift }
	
	sub handle_cpan_data {
		my $self = shift;
		my ($data) = @_;
		
		my $v = version->parse( $data->{version} );
		
		my ($newest) = reverse sort grep defined, (
			$self->latest_release,
			$self->developer_release,
		);
		
		if (!defined($newest) or $v gt $newest) {
			$self->name($data->{distribution});
			$self->abstract($data->{abstract} or ());
			$self->licence($data->{resources}{license}[0] or ());
			$self->bugtracker($data->{resources}{bugtracker}{web} or ());
		}
		
		$self->cpan('https://metacpan.org/release/'.$data->{distribution});
		$self->link($self->cpan);
		
		unless ($self->has_lang) {
			$self->lang(
				$self->name =~ /\bXS\b/i ? "C, Perl 5" :
				$self->name =~ /Alien.Wenity/i ? "VB.NET, Perl 5" :
				"Perl 5"
			);
		}
		
		my $repo = $data->{resources}{repository}{url} || '';
		$self->github($repo) if $repo =~ m{\Ahttps?://github\.com/};
		$self->bitbucket($repo) if $repo =~ m{\Ahttps?://bitbucket\.org/};
		
		if ($self->name =~ /\AAlt-(.+)-But(\w+)\z/) {
			$self->github('https://githib.com/tobyink/p5-alt-misc');
			$self->bitbucket('https://bitbucket.org/tobyink/p5-alt-misc');
		}
		
		my ($has, $rel, $date, $dl) = ($data->{version} =~ /_/)
			? qw( has_developer_release developer_release developer_date developer_download )
			: qw( has_latest_release latest_release latest_date latest_download );
		if ( !$self->$has
		or   ($self->$has and $self->$rel lt $v) ) {
			$self->$rel($v);
			$self->$date($data->{date});
			$self->$dl($data->{download_url});
		}
	}
	
	my %canon = (
		'ada'      => 'Ada',
		'p5'       => 'Perl 5',
		'Perl'     => 'Perl 5',
		'perl'     => 'Perl 5',
		'php'      => 'PHP',
		'html/css' => 'HTML, CSS, Javascript',
		'misc'     => 'Misc',
		'web'      => 'HTML, CSS, Javascript',
		'vb'       => 'VB.NET',
	);
	
	sub handle_bitbucket_data {
		my $self = shift;
		my ($data) = @_;
		
		$self->name($data->{name}) unless $self->has_name;
		
		no warnings qw(uninitialized);
		my ($hint) = ($self->name =~ /^(\w+)-/);
		$self->lang( $canon{$data->{lang}} or $canon{$hint} or $data->{lang} or () )
			unless $self->has_lang;
		$self->bitbucket('https://bitbucket.org/'.$data->{owner}.'/'.$data->{slug});
		$self->link($data->{website} or ()) unless $self->has_link;
	}
	
	sub handle_github_data {
		my $self = shift;
		my ($data) = @_;
		
		$self->name($data->{name}) unless $self->has_name;
		
		no warnings qw(uninitialized);
		my ($hint) = ($self->name =~ /^(\w+)-/);
		$self->lang( $canon{$data->{language}} or $canon{$hint} or $data->{language} or () )
			unless $self->has_lang;
		
		$self->link($data->{homepage} or $data->{html_url} or ()) unless $self->has_link;
		
		$self->github('https://github.com/'.$data->{owner}{login}.'/'.$data->{name});
	}

	sub handle_travis_data {
		my $self = shift;
		my ($data) = @_;
		
		$self->travis('https://travis-ci.org/'.$data->{slug});
		$self->last_build_state($data->{last_build_state} or ());
		$self->last_build_id($data->{last_build_id} or ());
		
		my $ua  = LWP::UserAgent->new;
		my $uri = 'https://coveralls.io/r/'.$data->{slug};
		my $res = $ua->get($uri, Accept => 'application/json');
		if ($res->is_success) {
			my $data2 = 'JSON::PP'->new->utf8(1)->decode($res->content);
			$self->coveralls($uri);
			my ($b, $c) = exists($data2->{model}{coverage_cache}{master})
				? (master => $data2->{model}{coverage_cache}{master})
				: each(%{ $data2->{model}{coverage_cache} });
			if ($b) {
				$self->coverage_branch($b);
				$self->coverage($c);
			}
		}
	}
	
	sub _get_all {
		my $class = shift;
		my ($cache) = @_;
		
		our @ALL;
		local @ALL;
		
		my $ua = 'LWP::UserAgent'->new;
		my $json = 'JSON::PP'->new->utf8(1)->pretty(1)->canonical(1);

		Utils::mirror($ua, $cache, @$_) for (
			[ "http://api.metacpan.org/v0/release/_search?q=author:${\CPAN_IDENT}&size=5000&fields=name,resources,date,download_url,version,distribution,abstract" => 'cpan_%d.json' ],
			[ "https://api.github.com/users/${\GITHUB_IDENT}/repos?per_page=100" => 'github_%d.json' ],
			[ "https://api.bitbucket.org/1.0/users/${\BITBUCKET_IDENT}/" => 'bitbucket_%d.json' ],
			[ "https://api.travis-ci.org/repos/${\TRAVIS_IDENT}" => 'travis_%d.json' ],
		);

		my (%projects_by_cpan, %projects_by_bitbucket, %projects_by_github, %projects_by_cpan_lc);

		{
			my @cpan = Utils::readjson($json, $cache, 'cpan_%d.json', [qw/ hits hits /]);
			
			for (@cpan) {
				my $c = $_->{fields};
				my $p = ($projects_by_cpan{ $c->{distribution} } ||= $class->new(name => $c->{distribution}));
				$p->handle_cpan_data($c);
				
				$projects_by_cpan_lc{ lc($c->{distribution}) } = $p;
				$projects_by_github{ $p->github } = $p if $p->has_github;
				$projects_by_bitbucket{ $p->bitbucket } = $p if $p->has_bitbucket;
			}
		}

		{
			my @repos = Utils::readjson($json, $cache, 'bitbucket_%d.json', [qw/repositories/]);
			for (@repos) {
				my $c = $_;
				next if $c->{is_private};
				next if $c->{is_fork};
				next if $c->{name} eq 'p5-alt-misc';
				
				my $p;
				if ($c->{name} =~ /\Ap5-(.+)\z/) {
					$p = $projects_by_cpan_lc{lc($1)};
				}
				$p ||= $class->new;
				$p->handle_bitbucket_data($c);
				
				$projects_by_bitbucket{ $p->bitbucket } = $p;
			}
		}

		{
			my @repos = Utils::readjson($json, $cache, 'github_%d.json');
			for (@repos) {
				my $c = $_;
				next if $c->{private};
				next if $c->{fork};
				next if $c->{name} eq 'p5-alt-misc';
				
				my $p;
				if ($c->{name} =~ /\Ap5-(.+)\z/) {
					$p = $projects_by_cpan_lc{lc($1)};
				}
				$p ||= $projects_by_bitbucket{'https://bitbucket.org/tobyink/'.$c->{name}};
				$p ||= $class->new;
				$p->handle_github_data($c);
				
				$projects_by_github{ $p->github } = $p;
			}
		}

		{
			my @repos = Utils::readjson($json, $cache, 'travis_%d.json', [qw/repos/]);
			for (@repos) {
				my $c = $_;
				my $p = $projects_by_github{'https://github.com/'.$c->{slug}};
				$p->handle_travis_data($c) if $p;
			}
		}
		
		return @ALL;
	}
	
	sub get_all {
		my $class = shift;
		my ($cache) = @_;
		
		$cache->mkpath;		
		my $cachefile = $cache->child('code.dd');
		
		my @all;
		if ($cachefile->exists and $cachefile->stat->mtime > time() - 3600) {
			Utils::yell "Reading cached data from $cachefile";
			my $got = eval("no strict;".$cachefile->slurp_raw)
				or die("Could not read cache: $@");
			@all = @$got;
		}
		else {
			@all = $class->_get_all(@_);
			Utils::yell "Caching data to $cachefile";
			$cachefile->spew_raw( Data::Dumper::Dumper(\@all) );
		}
		
		return @all;
	}
	
	sub to_html {
		my $self = shift;
		my $h;
		$h .= '<tr typeof="doap:Project"';
		$h .= sprintf(
			' about="http://purl.org/NET/cpan-uri/dist/%s/project"',
			$self->name,
		) if $self->cpan;
		$h .= ">\n";
		$h .= '<th>';
		if ($self->link) {
			$h .= sprintf(
				'<a href="%s" rel="doap:homepage" property="doap:name">%s</a>',
				$self->link,
				$self->name,
			);
		}
		else {
			$h .= sprintf(
				'<span property="doap:name">%s</a>',
				$self->name,
			);
		}
		if ($self->has_abstract) {
			$h .= sprintf("<br /><small property=\"doap:shortdesc\">%s</small>\n", $self->abstract);
		}
		$h .= "</th>\n";
		$h .= "<td>";
		if ($self->has_latest_release) {
			$h .= "<span rel=\"doap:release\">";
			if ($self->has_latest_download) {
				$h .= sprintf(
					"<a rel=\"doap:file-release\" property=\"doap:revision\" href=\"%s\">%s</a>",
					$self->latest_download,
					$self->latest_release,
				);
			}
			else {
				$h .= sprintf(
					"<span property=\"doap:revision\">%s</span>",
					$self->latest_release,
				);
			}
			if ($self->has_latest_date) {
				$h .= sprintf(
					" <small datatype=\"xsd:dateTime\" property=\"dc:issued\" content=\"%s\">%s</small>",
					$self->latest_date,
					substr($self->latest_date, 0, 10),
				);
			}
			$h .= "</span>";
		}
#		latest_release latest_date latest_download
#		developer_release developer_date developer_download
		if ($self->has_developer_release) {
			my $do_it = 0;
			if (not $self->has_latest_release) {
				++$do_it;
			}
			elsif ($self->has_latest_release and $self->latest_release lt $self->developer_release) {
				++$do_it;
				$h .= "<br/>";
			}
			
			if ($do_it) {
				$h .= "<span rel=\"doap:release\">";
				if ($self->has_developer_download) {
					$h .= sprintf(
						"<a rel=\"doap:file-release\" property=\"doap:revision\" href=\"%s\">%s</a>",
						$self->developer_download,
						$self->developer_release,
					);
				}
				else {
					$h .= sprintf(
						"<span property=\"doap:revision\">%s</span>",
						$self->developer_release,
					);
				}
				if ($self->has_developer_date) {
					$h .= sprintf(
						" <small datatype=\"xsd:dateTime\" property=\"dc:issued\" content=\"%s\">%s</small>",
						$self->developer_date,
						substr($self->developer_date, 0, 10),
					);
				}
				$h .= "</span>";
			}
		}
		
		$h .= "</td>\n";
		if ($self->has_lang) {
			$h .= sprintf("<td property=\"doap:programming-language\">%s</td>\n", $self->lang);
		}
		else {
			$h .= "<td></td>\n";
		}
		if ($self->has_github or $self->has_bitbucket) {
			$h .= "<td property=\"doap:repository\">";
			if ($self->has_github) {
				$h .= sprintf(
					"<a typeof=\"doap:GitRepository\" rel=\"doap:browse\" href=\"%s\"><img src=\"/assets/gh\" alt=\"GitHub\"/></a>",
					$self->github,
				);
			}
			if ($self->has_bitbucket) {
				$h .= sprintf(
					"<a typeof=\"doap:HgRepository\" rel=\"doap:browse\" href=\"%s\"><img src=\"/assets/bb\" alt=\"Bitbucket\" /></a>",
					$self->bitbucket,
				);
			}
			$h .= "</td>\n";
		}
		else {
			$h .= "<td></td>\n";
		}
		
		# travis last_build_state last_build_id
		# coveralls coverage coverage_branch		
		$h .= "<td>";
		$h .= sprintf(
			"<a href=\"%s/builds/%s\"><img src=\"%s.svg\" alt=\"Status: %s\" /></a>",
			$self->travis,
			$self->last_build_id,
			$self->travis,
			$self->last_build_state,
		) if $self->has_travis;
		if ($self->has_coveralls) {
			(my $img = $self->coveralls)
				=~ s{https://coveralls.io/r/}{https://img.shields.io/coveralls/};
			$h .= sprintf(
				"<br><a href=\"%s?branch=%s\"><img src=\"%s.svg\" alt=\"Coverage: %s%%\" /></a>",
				$self->coveralls,
				$self->coverage_branch,
				$img,
				$self->coverage,
			);			
		}
		$h .= "</td>\n";
		
		$h .= "</tr>\n\n";
		return $h;
	}
}

my $cache    = path('tmp/cache/');
my @projects = Project->get_all($cache);

@projects =
	map $_->[1],
	sort { $a->[0] cmp $b->[0] }
	map [ $_->name, $_ ],
	@projects;
	

select( path('public_html/code.bare')->openw_utf8 );

print "<title>Toby Inkster's Coding Projects</title>\n";
print <<STYLE;
<style type="text/css">
td small { white-space: nowrap }
</style>
STYLE
print "<article id=\"document_main\"";
print " xmlns:doap=\"http://usefulinc.com/ns/doap#\"";
print " xmlns:xsd=\"http://www.w3.org/2001/XMLSchema#\"";
print " xmlns:dc=\"http://purl.org/dc/elements/1.1/\"";
print ">\n";
print "<p>There follows a fairly comprehensive list of my open source projects.";
print " These range from stable, useful libraries, to barely thought out ideas.</p>\n";
print "<p>Note that test results and coverage information from continuous integration testing";
print " is only available for a few of these projects as I've only recently started to get";
print " myself organized with that sort of thing.</p>\n";
print "<table about=\"http://tobyinkster.co.uk/#i\"";
print " class=\"table table-striped table-condensed table-sortable\"";
print " data-sortlist=\"[[0,0]]\" style=\"width:auto\"";
print ">\n";
print "<thead>\n";
print "<tr>\n";
print "<th>Project</th>\n";
print "<th>Latest Release</th>\n";
print "<th>Language</th>\n";
print "<th>Repository</th>\n";
print "<th>Test Results</th>\n";
print "</tr>\n";
print "</thead>\n";
print "<tbody rev=\"doap:developer\">\n\n";
for my $p (@projects) {
	print $p->to_html;
}
print "</tbody>\n\n";
print "</table>\n";
print "</article>\n";
