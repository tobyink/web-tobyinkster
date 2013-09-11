#!/usr/bin/env perl

use strict;
use warnings;

use Path::Tiny;
use XML::Atom::Feed;

my $feed = XML::Atom::Feed->new('http://ilovecbeebies.tmpdir.eu/feed/atom/');

for my $entry ($feed->entries) {
	next unless $entry->link->href =~ m{\Ahttp://ilovecbeebies.tmpdir.eu/([0-9]{4})/([0-9]{2})/([0-9]{2})/(.+)/\z};
	my ($yyyy, $mm, $dd, $slug) = ($1, $2, $3, $4);
	
	my $path = path(qw( sources ilovecbeebies blog ), $yyyy, $mm, $dd, $slug, 'index.atom');
	$path->parent->mkpath;
	
	next if $path->exists;
	
	print "Writing $path...\n";
	print {$path->openw} $entry->as_xml;
}


