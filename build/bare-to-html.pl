#!/usr/bin/env perl

use strict;
use warnings;

use Path::Tiny 'path';
use Path::Iterator::Rule;
use XML::Atom::Entry;
use HTML::HTML5::Sanity;
use HTML::HTML5::Writer;
use HTML::Inject;
use XML::LibXML::PrettyPrint;
use List::Util 'max';

my $writer = HTML::HTML5::Writer->new(markup => "xhtml", polyglot => 1);
my $pp     = XML::LibXML::PrettyPrint->new_for_html;

my $__orig = pop(@{$pp->{element}{preserves_whitespace}}); 
push @{$pp->{element}{preserves_whitespace}}, sub {
	no warnings;
	$_[0]->getAttribute('role') ne 'main' and $__orig->(@_);
};

my $dir = path('public_html')->absolute;
my $rule = Path::Iterator::Rule->new->file->name('*.bare');
my $iter = $rule->iter($dir);

my $template     = path('template.html');
my $template_dom = HTML::Inject->new(target => $template->openr);

FILE: while (my $file = $iter->())
{
	my $orig = path($file);
	my $new  = do { my $tmp = $file; $tmp =~ s/\.bare\z/.html/; path($tmp) };

	if ($new->exists)
	{
		next FILE
			if $new->stat->mtime >= $template->stat->mtime
			&& $new->stat->mtime >= $orig->stat->mtime
			&& $new->stat->mtime >= path(__FILE__)->stat->mtime;
	}
	
	print "Processing $orig into $new...\n";
	$new->parent->mkpath;
	$new->spew_utf8( Process($orig) );
	utime($orig->stat->atime, max($orig->stat->mtime, $template->stat->mtime, path(__FILE__)->stat->mtime), $new);
}

sub Process
{
	my $orig = shift;
	my $html = $orig->slurp;
	my $dom  = HTML::HTML5::Sanity::fix_document($template_dom->inject($html));
	my @NS   = ($html =~ m{\b(xmlns:\w+=".+?")}gms);
	my $out  = $writer->document($pp->pretty_print($dom));
	$out =~ s{<html }{<html @NS }ms;
	$out;
}
