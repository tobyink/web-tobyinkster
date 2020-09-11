#!/usr/bin/env perl

use v5.14;

package Local::App {
  use Zydeco;
  
  # Utility function.
  #
  require HTML::Entities;
  sub E ($) { goto \&HTML::Entities::encode_entities_numeric }
  
  use Path::Tiny qw(path);
  
  # A role for creating singleton classes.
  # Classes composing the role need to provide a method
  # name for the singleton factory.
  #
  role SingletonFactory ( $methodname ) {
    my $instance;
    
    # After the role has been applied to a class...
    #
    after_apply {
      if ( $kind eq 'class' ) {
        
        # Create that factory method.
        #
        factory {$methodname} () {
          $instance ||= $class->new;
        }
      }
    }
  }
  
  # Create a subclass of HTTP::Tiny
  # which is a singleton.
  #
  class UA 1.99 {
    extends ::HTTP::Tiny;
    with SingletonFactory('get_ua');
    
    # Only difference is that it dies if
    # the HTTP request fails.
    #
    around request ( @args ) {
      my $response = $self->$next( @args );
      confess( 'HTTP %d Error: %s', $response->{status}, $response->{reason} )
        unless $response->{success};
      return $response;
    }
  }
  
  # Create a subclass of HTML::HTML5::Parser
  # which is a singleton.
  #
  class HTML_Parser {
    extends ::HTML::HTML5::Parser;
    with SingletonFactory('get_html_parser');
    
    # Could load this anywhere, but convenient
    # to do it here.
    #
    require XML::LibXML::QuerySelector;
  }
  
  # Create a subclass of DateTime::Format::Natural
  # which is a singleton.
  #
  class DT_Parser {
    extends ::DateTime::Format::Natural;
    with SingletonFactory('get_dt_parser');
  }
  
  # This is the web scraping class.
  #
  class WebPage {
    # Required attribute.
    #
    has url!;
    
    # A bunch of lazy attributes.
    #
    has ua (
      handles => { 'get' => 'get' }
    ) = $self->FACTORY->get_ua;
    
    has html_parser = $self->FACTORY->get_html_parser;
    
    has dt_parser = $self->FACTORY->get_dt_parser;
    
    has content = $self->get( $self->url )->{content};
    
    has dom (
      handles => { 'q' => 'querySelector' }
    ) = $self->html_parser->parse_string( $self->content );
    
    has title = $self->q('.entry-title')->textContent;
    
    has datetime = do {
      my $dt = $self->q('.dateline .date')->textContent;
      $self->dt_parser->parse_datetime( $dt );
    };
    
    has body = do {
      my @nodes = $self->q('.entry-body')->childNodes;
      join '', map $_->toString, @nodes;
    };
    
    has summary = do {
      $self->content =~ m{<dc:description>(.*)</dc:description>};
      $1;
    };
    
    has blog_slug = do {
      $self->url =~ m{http://blogs.perl.org/users/([^/]+)/(\d+)/(\d+)/([^/]+).html};
      $1;
    };
    
    has entry_slug = do {
      $self->url =~ m{http://blogs.perl.org/users/([^/]+)/(\d+)/(\d+)/([^/]+).html};
      $4;
    };
    
    has blog_id = do {
      $self->content =~ m{<input type="hidden" name="IncludeBlogs" value="(\d+)" />};
      $1;
    };
    
    has entry_id = do {
      $self->content =~ m{<div class="entry" id="entry-(\d+)">};
      $1;
    };
    
    has id = sprintf(
      'tag:blogs.perl.org,%s:/users/%s//%s.%s',
      $self->datetime->year,
      $self->blog_slug,
      $self->blog_id,
      $self->entry_id,
    );
    
    has tags = do {
      my @nodes = $self->dom->querySelectorAll('a[rel=tag]');
      [ map $_->textContent, @nodes ];
    };
    
    has atom_path = path sprintf(
      'sources/bpo/blog/%04d/%02d/%02d/%s/index.atom',
      $self->datetime->year,
      $self->datetime->month,
      $self->datetime->day,
      $self->entry_slug,
    );
    
    # Method to output the blog post as Atom.
    #
    method to_atom () {
      my $return = qq{<?xml version="1.0" encoding="UTF-8"?>\n};
      $return   .= qq{<entry xml:lang="en" xmlns="http://www.w3.org/2005/Atom">\n};
      $return   .= sprintf(qq{<title>%s</title>\n}, E $self->title);
      $return   .= sprintf(qq{<link rel="alternate" type="text/html" href="%s" />\n}, E $self->url);
      $return   .= sprintf(qq{<id>%s</id>\n}, E $self->id);
      $return   .= sprintf(qq{<published>%s</published>\n}, $self->datetime);
      $return   .= sprintf(qq{<updated>%s</updated>\n}, $self->datetime);
      $return   .= sprintf(qq{<summary>%s</summary>\n}, E $self->summary);
      $return   .= sprintf(qq{<category term="%s" scheme="http://www.sixapart.com/ns/types#category" />\n}, E $_) for @{ $self->tags };
      $return   .= sprintf(qq{<content type="html" xml:base="http://blogs.perl.org/users/%s/"><![CDATA[%s]]></content>\n}, E $self->blog_slug, $self->body);
      $return   .= qq{</entry>\n};
      return $return;
    }
    
    method process () {
      say "Processing ", $self->url;
      return if $self->atom_path->is_file;
      say "... making ", $self->atom_path;
      $self->atom_path->parent->mkpath;
      $self->atom_path->spew_utf8( $self->to_atom );
      say "... done";
      return;
    }
    
    factory new_page ( Str $url ) {
      $class->new( url => $url );
    }
  }
}

open my $fh, '<', 'blog-get.html';

while ( defined ( my $line = <$fh> ) ) {
  if ( $line =~ m{(http://blogs.perl.org/[^"]+\.html)} ) {
    'Local::App'->new_page( $1 )->process;
  }
}
