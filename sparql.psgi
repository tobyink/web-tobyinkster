#!/usr/bin/env plackup

use 5.010;
use strictures;

use Encode ();
use HTML::HTML5::Sanity ();
use HTML::HTML5::Writer ();
use HTML::Inject ();
use Path::Tiny ();
use Plack::Request ();
use Plack::Response ();
use RDF::Query ();
use RDF::Trine ();
use XML::LibXML::PrettyPrint ();

{
	package Endpoint;
	use Moose;
	use match::simple qw(match);
	
	has store    => (is => 'ro', required => 1);
	has model    => (is => 'ro', lazy_build => 1);
	has app      => (is => 'ro', lazy_build => 1);
	has template => (is => 'ro', lazy_build => 1);

	sub _build_model {
		my $self = shift;
		RDF::Trine::Model->new($self->store);
	}
	
	sub _build_app {
		my $self = shift;
		return sub {
			my $req = Plack::Request->new(@_);
			my $res = $self->handle_request($req);
#			$res->content_type('text/plain')
#				if $res->content_type =~ /xml/;
			$res->finalize;
		};
	}
	
	sub _build_template {
		my $self = shift;
		HTML::Inject->new(target => Path::Tiny->new('template.html')->openr);
	}
	
	sub handle_request {
		my $self = shift;
		my ($req) = @_;
		
		if (match($req->method, [qw/ GET POST /]) and $req->param('query')) {
			my $sparql = $req->param('query');
			my $query  = RDF::Query->new($sparql, { lang => 'sparql11', update => !!0 });
			return $self->handle_query($req, $query);
		}
		
		return $self->handle_form($req);
	}
	
	sub handle_form {
		my $self = shift;
		my ($req) = @_;
		
		state $form_html = do {
			my $form = HTML::HTML5::Sanity::fix_document(
				$self->template->inject(q{
					<!DOCTYPE html>
					<title>SPARQL Endpoint</title>
					<h1>SPARQL Endpoint</h1>
					<article id="document_main">
						<form action="" method="post">
							<div>
								<textarea cols="60" rows="12" name="query"></textarea><br>
								<input type="submit">
							</div>
						</form>
						<p>See <a href="http://www.w3.org/TR/sparql11-query/">SPARQL 1.1</a> for query syntax.</p>
					</article>
				})
			);
			my $writer = HTML::HTML5::Writer->new(markup => "xhtml", polyglot => 1);
			my $pp = XML::LibXML::PrettyPrint->new_for_html;
			Encode::encode( utf8 => $writer->document($pp->pretty_print($form)) );
		};
		
		my $r = Plack::Response->new(200);
		$r->content_type('text/html');
		$r->body($form_html);
		return $r;
	}
	
	sub handle_query {
		my $self = shift;
		my ($req, $q) = @_;
		
		my $a = $q->execute($self->model);
		
		if ($a->is_graph) {
			return $self->handle_results_graph($req, $q, $a);
		}
		
		return $self->handle_results_bindings($req, $q, $a);
	}
	
	sub handle_results_graph {
		my $self = shift;
		my ($req, $q, $a) = @_;
		
		my $r = Plack::Response->new(200);
		$r->content_type('application/rdf+xml');
		$r->body($a->as_xml);
		return $r;
	}
	
	sub handle_results_bindings {
		my $self = shift;
		my ($req, $q, $a) = @_;
		
		my $r = Plack::Response->new(200);
		$r->content_type('application/sparql-results+xml');
		$r->body($a->as_xml);
		return $r;
	}
}

my $store = RDF::Trine::Store->new_with_config({
	storetype => 'DBI',
	name      => '',
	dsn       => 'dbi:SQLite:dbname=store.sqlite',
	username  => '',
	password  => '',
});

my $endpoint = Endpoint->new(store => $store);
$endpoint->app;