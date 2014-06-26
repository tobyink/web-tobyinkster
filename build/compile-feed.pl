#!/usr/bin/env perl

use strict;
use warnings;

use Path::Tiny 'path';
use Path::Iterator::Rule;
use DateTime;
use XML::Atom::Entry;
use XML::Atom::Feed;
use JSON qw(encode_json);
use List::Util qw(min);
use List::MoreUtils qw(part);

my $blogroot = 'http://toby.ink/blog/';

my $dir = path('public_html/blog')->absolute;
my $rule = Path::Iterator::Rule->new->file->name('*.atom');
my $iter = $rule->iter($dir);

my @entries;

FILE: while (my $file = $iter->())
{
	next if $file eq $dir->child('index.atom');  # skip the file we're trying to compile!
	
	$file = path($file);
	my $entry = XML::Atom::Entry->new($file->openr);
	$entry->elem->removeChild($_) for $entry->elem->getChildrenByTagName('content');
	
	(my $mirror = $blogroot . $file->relative($dir)) =~ s{/index.atom$}{/};
	my $mirror_link = XML::Atom::Link->new(Version => '1.0');
	$mirror_link->href($mirror);
	$mirror_link->rel('self');
	$entry->add_link($mirror_link);
	
	push @entries, $entry;
}

@entries =
	map  $_->[0],
	sort { $b->[1] cmp $a->[1] }
	map  [ $_, $_->published ],
	@entries;

my $feed = XML::Atom::Feed->new(Version => '1.0');
$feed->id($blogroot);
$feed->title("Toby Inkster's Blog");
$feed->author(do {
	my $me = XML::Atom::Person->new(Version => '1.0');
	$me->name('Toby Inkster');
	$me->uri('http://tobyinkster.co.uk/');
	$me->email('mail@tobyinkster.co.uk');
	$me;
});
$feed->updated( $entries[0]->published );
$feed->add_entry($_) for @entries;

$dir->child('index.atom')->spew_utf8( $feed->as_xml );

my @summaries = map {
	;my $e = $_
	;my @link =
		map  { ;sort(@{ $_ or [] }) }
		part { ;m{^http://toby\.?ink} }
		map  { ;$_->href }
		grep { ;no warnings 'uninitialized' ;$_->rel =~ /^(self|alternate|)$/ }
		$e->link
	;+{
		title     => $e->title,
		published => $e->published,
		link      => @link ? $link[0] : $e->id,
	}
} @entries;

my $count = min(20, scalar(@summaries));
$dir->child('index.json')->spew_raw( encode_json [ @summaries[0 .. $count-1] ] );
