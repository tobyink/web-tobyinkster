#!/usr/bin/env perl

use v5.14;

package Local::App {
	use Zydeco version => '1.0';
	
	use HTML::Entities qw();
	sub E ($) { goto \&HTML::Entities::encode_entities_numeric }
	
	use Path::Tiny qw(path);
	
	class DT_Parser extends ::DateTime::Format::Natural {
		factory get_dt_parser () {
			state $instance = $class->new;
		}
	}
	
	class HTML_Parser extends ::HTML::HTML5::Parser {
		require XML::LibXML::QuerySelector;
		factory get_html_parser () {
			state $instance = $class->new;
		}
	}
	
	class UA extends ::HTTP::Tiny {
		factory get_ua () {
			state $instance = $class->new;
		}
		around request ( @args ) {
			my $response = $self->$next( @args );
			confess( 'HTTP %d Error: %s', $response->{status}, $response->{reason} ) unless $response->{success};
			return $response;
		}
	}

	class WebPage {
		has url!;
		has ua (
			handles => { 'get' => 'get' }
		)                = $self->FACTORY->get_ua;
		has html_parser  = $self->FACTORY->get_html_parser;
		has dt_parser    = $self->FACTORY->get_dt_parser;
		has content      = $self->get( $self->url )->{content};
		has dom (
			handles => { 'q' => 'querySelector' }
		)                = $self->html_parser->parse_string( $self->content );
		
		has title        = $self->q('.entry-title')->textContent;
		has datetime     = $self->dt_parser->parse_datetime( $self->q('.dateline .date')->textContent );
		has body         = join '', map $_->toString, $self->q('.entry-body')->childNodes;
		has summary      = do {
			$self->content =~ m{<dc:description>(.*)</dc:description>};
			$1;
		};
		has blog_slug    = do {
			$self->url =~ m{http://blogs.perl.org/users/([^/]+)/(\d+)/(\d+)/([^/]+).html};
			$1;
		};
		has entry_slug    = do {
			$self->url =~ m{http://blogs.perl.org/users/([^/]+)/(\d+)/(\d+)/([^/]+).html};
			$4;
		};
		has blog_id     = do {
			$self->content =~ m{<input type="hidden" name="IncludeBlogs" value="(\d+)" />};
			$1;
		};
		has entry_id     = do {
			$self->content =~ m{<div class="entry" id="entry-(\d+)">};
			$1;
		};
		has id           = sprintf('tag:blogs.perl.org,%s:/users/%s//%s.%s', $self->datetime->year, $self->blog_slug, $self->blog_id, $self->entry_id);
		has tags         = [ map $_->textContent, $self->dom->querySelectorAll('a[rel=tag]') ];
		
		has atom_path    = path sprintf(
			'sources/bpo/blog/%04d/%02d/%02d/%s/index.atom',
			$self->datetime->year,
			$self->datetime->month,
			$self->datetime->day,
			$self->entry_slug,
		);
		
		method to_atom () {
			my $return = qq{<?xml version="1.0" encoding="UTF-8"?>\n};
			$return   .= qq{<entry xmlns="http://www.w3.org/2005/Atom">\n};
			$return   .= sprintf(qq{<title>%s</title>\n}, E $self->title);
			$return   .= sprintf(qq{<link rel="alternate" type="text/html" href="%s" />\n}, E $self->url);
			$return   .= sprintf(qq{<id>%s</id>\n}, E $self->id);
			$return   .= sprintf(qq{<published>%s</published>\n}, $self->datetime);
			$return   .= sprintf(qq{<updated>%s</updated>\n}, $self->datetime);
			$return   .= sprintf(qq{<summary>%s</summary>\n}, E $self->summary);
			$return   .= sprintf(qq{<category term="%s" scheme="http://www.sixapart.com/ns/types#category" />\n}, E $_) for @{ $self->tags };
			$return   .= sprintf(qq{<content type="html" xml:lang="en" xml:base="http://blogs.perl.org/users/%s/"><![CDATA[%s]]></content>\n}, E $self->blog_slug, $self->body);
			$return   .= qq{</entry>\n};
			return $return;
		}
		
		method process () {
			say "Processing ", $self->url;
			$self->atom_path->is_file and return $self;
			say "... making ", $self->atom_path;
			$self->atom_path->parent->mkpath;
			$self->atom_path->spew_utf8( $self->to_atom );
			say "... done";
			return $self;
		}
		
		factory new_page ( Str $url ) {
			$class->new( url => $url );
		}
	}
}

open my $fh, '<', 'blog-get.html';

while ( defined ( my $line = <$fh> ) ) {
	if ( $line =~ m{(http://blogs.perl.org/[^"]+\.html)} ) {
		my $page = 'Local::App'->new_page( $1 );
		$page->process();
	}
}

