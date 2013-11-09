#!/usr/bin/env perl

use strict;
use warnings;

use Path::Tiny 'path';
use Path::Iterator::Rule;
use XML::Atom::Entry;
use HTML::HTML5::Builder 'RAW_CHUNK';
use HTML::HTML5::Writer;
use DateTime::Format::ISO8601;

my $datetime = DateTime::Format::ISO8601->new;
my $writer   = HTML::HTML5::Writer->new(quote_attributes => 1, start_tags => 1, end_tags => 1);

my $dir = path('public_html')->absolute;
my $rule = Path::Iterator::Rule->new->file->name('*.atom');
my $iter = $rule->iter($dir);

FILE: while (my $file = $iter->())
{
	my $orig = path($file);
	my $new  = do { my $tmp = $file; $tmp =~ s/\.atom\z/.bare/; path($tmp) };

	if ($new->exists)
	{
		if ($new->stat->mtime > $orig->stat->mtime)
		{
			die "$new is newer than $orig!";
		}
		elsif ($new->stat->mtime == $orig->stat->mtime)
		{
			next FILE;
		}
	}
	
	print "Processing $orig into $new...\n";
	$new->parent->mkpath;
	$new->spew_utf8(
		$orig =~ m{public_html/blog/index.atom}
			? ProcessFeed($orig)
			: ProcessEntry($orig)
	);
	utime($orig->stat->atime, $orig->stat->mtime, $new);
}

sub ljoin
{
	my $join = shift;
	my @r = map +($join, $_), @_;
	shift @r;
	return @r;
}

sub ProcessFeed
{
	my $file = shift;
	my $atom = XML::Atom::Feed->new($file->openr);
	my $H = 'HTML::HTML5::Builder'->new;
	
	my @elements;

	push @elements, $H->title(
		{
			'xmlns:dc' => 'http://purl.org/dc/terms/',
			'property' => 'dc:title',
		},
		$atom->title,
	);

	push @elements, map {
		my $link = $_;
		$H->link(
			map {
				defined($link->$_) ? +{ $_ => $link->$_ } : ();
			} qw/ rel href hreflang title type /
		);
	} $atom->links;
	
	push @elements, $H->link({
		rel     => 'alternate',
		type    => 'application/atom+xml',
		href    => $file->basename,
	});

	push @elements, $H->article(
		{
			'xmlns:atom' => 'http://bblfish.net/work/atom-owl/2006-06-06/#',
			'xmlns:iana' => 'http://www.iana.org/assignments/relation/',
			'xmlns:rdf'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
			'xmlns:xsd'  => 'http://www.w3.org/2001/XMLSchema#',
			'typeof'     => 'atom:Feed',
			'id'         => 'document_main',
			'class'      => 'feed',
		},
		$H->span(
			{
				'property' => 'atom:id',
				'content'  => $atom->id,
				'datatype' => 'xsd:anyURI',
			}
		),
		$H->header(
			{
				'role'     => 'heading',
				'rel'      => 'atom:title',
			},
			$H->h1(
				{
					'typeof'   => 'atom:TextContent',
					'property' => 'atom:body',
				},
				$atom->title,
			),
		),
		$H->dl(
			{ 'role' => 'contentinfo' },
			(map {
				my $person = $atom->$_;
				
				$H->dt(ucfirst $_),
				$H->dd(
					{ rel => "atom:$_" },
					$H->a(
						{
							typeof   => "atom:Person",
							property => "atom:name",
							rel      => "atom:uri",
							href     => $person->uri,
							(about => 'http://tobyinkster.co.uk/#i')x!!( $person->uri eq 'http://tobyinkster.co.uk/' ),
						},
						$person->name,
					),
				),
			} qw/ author /),
			(map {
				my $dt = $datetime->parse_datetime($atom->$_);
				$dt->set_time_zone('UTC');
				
				$H->dt(ucfirst $_),
				$H->dd(
					$H->time(
						{ property => "atom:$_", datetime => $dt->iso8601 . "Z", content => $dt->iso8601 . "Z", datatype => 'xsd:dateTime' },
						$dt->ymd, " ", $dt->hms, " UTC",
					),
				),
			} qw/ updated /),
		),
		$H->ol(
			{ 'role' => 'main', 'rel' => 'atom:entry' },
			(map {
				my $e   = $_;
				my $dt  = $datetime->parse_datetime($e->published);
				my @L   = grep { ;no warnings 'uninitialized'; $_->rel =~ /^(self|alternate|)$/ } $e->link;
				my ($l) = grep { $_->href !~ m{^http://tobyinkster.co.uk/} } @L;  # non-local
				my ($L) = grep { $_->href =~ m{^http://tobyinkster.co.uk/} } @L;  # local
				$l ||= $L;
				
				$H->li(
					{ 'typeof' => 'atom:Entry', property => 'atom:id', datatype => 'xsd:anyURI', property => $e->id },
					$H->time(
						{ 'property' => 'atom:published', datatype => 'xsd:dateTime', datetime => $dt->iso8601 . "Z", content => $dt->iso8601 . "Z" },
						$dt->ymd,
					),
					q[ ],
					$H->cite($H->a({ 'href' => $l->href }, $e->title)),
					$L && ($L->href eq $l->href)
						? ()
						: (
							q[ ],
							$H->small('(', $H->a({ href => $L->href }, 'local copy'), ')'),
						),
				);
			} $atom->entries),
		),
	);

	join "\n", map $writer->element($_), @elements;
}

sub ProcessEntry
{
	my $file = shift;
	my $atom = XML::Atom::Entry->new($file->openr);
	my $H = 'HTML::HTML5::Builder'->new;
	
	my @elements;
	
	push @elements, $H->title(
		{
			'xmlns:dc' => 'http://purl.org/dc/terms/',
			'property' => 'dc:title',
		},
		$atom->title,
	);

	push @elements, map {
		my $link = $_;
		$H->link(
			map {
				defined($link->$_) ? +{ $_ => $link->$_ } : ();
			} qw/ rel href hreflang title type /
		);
	} $atom->links;
	
	push @elements, $H->link({
		rel     => 'alternate',
		type    => 'application/atom+xml',
		href    => $file->basename,
	});

	push @elements, $H->article(
		{
			'xmlns:atom' => 'http://bblfish.net/work/atom-owl/2006-06-06/#',
			'xmlns:iana' => 'http://www.iana.org/assignments/relation/',
			'xmlns:rdf'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
			'xmlns:xsd'  => 'http://www.w3.org/2001/XMLSchema#',
			'typeof'     => 'atom:Entry',
			'id'         => 'document_main',
		},
		$H->span(
			{
				'property' => 'atom:id',
				'content'  => $atom->id,
				'datatype' => 'xsd:anyURI',
			}
		),
		$H->header(
			{
				'role'     => 'heading',
				'rel'      => 'atom:title',
			},
			$H->h1(
				{
					'typeof'   => 'atom:TextContent',
					'property' => 'atom:body',
				},
				$atom->title,
			),
		),
		$H->dl(
			{ 'role' => 'contentinfo' },			
			(map {
				my $person = $atom->$_;
				
				$H->dt(ucfirst $_),
				$H->dd(
					{ rel => "atom:$_" },
					$H->a(
						{
							typeof   => "atom:Person",
							property => "atom:name",
							rel      => "atom:uri",
							href     => $person->uri,
							(about => 'http://tobyinkster.co.uk/#i')x!!( $person->uri eq 'http://tobyinkster.co.uk/' ),
						},
						$person->name,
					),
				),
			} qw/ author /),
			(map {
				my $dt = $datetime->parse_datetime($atom->$_);
				$dt->set_time_zone('UTC');
				
				$H->dt(ucfirst $_),
				$H->dd(
					$H->time(
						{ property => "atom:$_", datetime => $dt->iso8601 . "Z", datatype => 'xsd:dateTime' },
						$dt->ymd, " ", $dt->hms, " UTC",
					),
				),
			} qw/ published updated /),
			(do {
				$H->dt('Categories'),
				$H->dd(
					{ rel => 'atom:category'},
					ljoin "; ",
					map {
						my $category = $_;
						$H->span(
							{ typeof => 'atom:Category' },
							$category->label ? (
								$H->span({ property => 'atom:label' }, $category->label),
								($H->span({ property => 'atom:term', content => $category->term }))x!!($category->term),
							) : $H->span({ property => 'atom:term' }, $category->term),
							($H->span({ rel=>'atom:scheme', resource=>$category->scheme }))x!!($category->scheme),
						);
					} $atom->categories
				),
			})x!!($atom->categories),
		),
		$H->div(
			{
				'rel'      => 'atom:content',
			},
			$H->div(
				{
					'role'     => 'main',
					'typeof'   => 'atom:Content',
					'property' => 'atom:body',
					'datatype' => 'rdf:XMLLiteral',
				},
				do {
					my $cnt  = $atom->content;
					my $mode = $cnt->mode || 'xml';
					$mode = 'escaped' if $cnt->type eq 'html';
					
					$mode eq 'escaped' ? RAW_CHUNK($cnt->elem->textContent) :
					$mode eq 'xml'     ? $cnt->elem->childNodes  : die($mode);
				}
			),
		),
		$H->span(
			{ rel => 'atom:link' },
			map {
				my $link = $_;
				$H->span(
					{
						typeof   => 'atom:Link',
						(
							property => 'atom:title',
							resource => $link->title
						) x !!defined($link->title)
					},
					$H->span(
						{
							rel      => 'atom:rel',
							resource => ($link->rel =~ /:/ ? $link->rel : sprintf('[iana:%s]', $link->rel||'alternate'))
						}
					),
					$H->span(
						{ rel => 'atom:to' },
						$H->span(
							{
								rel      => 'atom:src',
								resource => $link->href,
								(
									property => 'atom:lang',
									resource => $link->hreflang,
								) x !!defined($link->hreflang)
							},
						),
					),
				);
			} $atom->links,
		),
	);
	
	join "\n", map $writer->element($_), @elements;
}
