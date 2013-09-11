#!/usr/bin/env perl

use strict;
use warnings;

use Path::Tiny;
use XML::Atom::Feed;

my $feed = XML::Atom::Feed->new('http://blogs.perl.org/users/toby_inkster/atom.xml');

for my $entry ($feed->entries) {
	next unless $entry->published =~ m{\A([0-9]{4})-([0-9]{2})-([0-9]{2})T};
	my ($yyyy, $mm, $dd) = ($1, $2, $3);
	
	next unless $entry->link->href =~ m{\Ahttp://blogs.perl.org/users/\w+/[0-9]{4}/[0-9]{2}/(.+).html\z};
	my $slug = $1;
	
	my $path = path(qw( sources bpo blog ), $yyyy, $mm, $dd, $slug, 'index.atom');
	$path->parent->mkpath;
	
	next if $path->exists;
	
	print "Writing $path...\n";
	print {$path->openw} $entry->as_xml;
}


