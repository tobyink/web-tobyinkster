#!/usr/bin/env perl

use strict;
use warnings;

use Path::Tiny 'path';
use Path::Iterator::Rule;
use RDF::RDFa::Parser;
use List::Util qw(max);

my $me   = path(__FILE__)->stat->mtime;

my $dir  = path('public_html')->absolute;
my $rule = Path::Iterator::Rule->new->file->name('*.html');
my $iter = $rule->iter($dir);

my $options = RDF::RDFa::Parser::Config->new('xhtml', '1.1');
my $sink = RDF::Trine::Model->new(
	RDF::Trine::Store->new_with_config({
		storetype => 'DBI',
		name      => '',
		dsn       => 'dbi:SQLite:dbname=store.sqlite',
		username  => '',
		password  => '',
	}) or die("nuh?")
);
my $ser = RDF::Trine::Serializer::NTriples->new;

FILE: while (my $file = $iter->())
{
	my $orig = path($file);
	my $new  = do { my $tmp = $file; $tmp =~ s/\.html\z/.nt/; path($tmp) };
	
	# this file changes frequently, and contains mostly redundant info
	next if $new =~ m{public_html/blog/index.nt\z};
	
	if ($new->exists)
	{
		next FILE
			if $new->stat->mtime >= $orig->stat->mtime
			&& $new->stat->mtime >= $me;
	}
	
	print "Processing $orig into $new...\n";
	$new->parent->mkpath;
	my $nt;
	eval { $nt = Process($orig); 1 } or do {
		print "Error processing $orig!\n";
		next;
	};
	$new->spew_utf8( Process($orig) );
	utime($orig->stat->atime, max($orig->stat->mtime, $me), $new);
}

sub Process
{
	my $orig = shift;
	
	my $base = "http://tobyinkster.co.uk/" . $orig->relative($dir);
	$base =~ s/index\.html\z//;
	my $graph = RDF::Trine::Node::Resource->new($base);
	
	$sink->remove_statements(undef, undef, undef, $graph);
	
	$sink->begin_bulk_ops;
	my $rdfa = RDF::RDFa::Parser->new($orig->slurp_utf8, $base, $options);
	$rdfa->set_callbacks({
		ontriple => sub { $sink->add_statement($_[2], $graph); 0 },
	});
	$sink->end_bulk_ops;
	$ser->serialize_model_to_string($rdfa->graph);
}
