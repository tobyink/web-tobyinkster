#!/usr/bin/env perl

use Path::Tiny 'path';
use Path::Iterator::Rule;

my $sources = path('sources')->absolute;
my $output  = path('public_html')->absolute;

my $rule = Path::Iterator::Rule->new->file;

SOURCE: for my $source ($sources->children)
{
	next unless $source->is_dir;
	my $iter = $rule->iter($source);
	
	FILE: while (my $file = $iter->())
	{
		my $orig = path($file);
		my $new  = $output->child( path($file)->relative($source) );
		
		if ($new->exists)
		{
			if ($new->stat->mtime > $orig->stat->mtime)
			{
				die "$new is newer than $orig!";
			}
			elsif ($new->stat->mtime == $orig->stat->mtime
			and $new->stat->size == $orig->stat->size)
			{
				next FILE;
			}
		}
		
		print "Copying $orig to $new...\n";
		$new->parent->mkpath;
		$orig->copy($new);
		utime($orig->stat->atime, $orig->stat->mtime, $new);
	}
}
