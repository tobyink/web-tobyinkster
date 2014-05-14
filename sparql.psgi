#!/usr/bin/env plackup

use 5.010;
use strictures;
use lib qw(/opt/perl/lib/perl5/);  # path to recent Trine on live server

use Encode ();
use HTML::HTML5::Sanity ();
use HTML::HTML5::Writer ();
use HTML::Inject ();
use Path::Tiny ();
use Plack::Request ();
use Plack::Response ();
use RDF::NS ();
use RDF::QueryX::Lazy ();
use RDF::Trine ();
use XML::LibXML::PrettyPrint ();

use File::Basename qw(dirname);
chdir dirname(__FILE__);

{
	package Endpoint::RDFa;
	use parent qw(RDF::RDFa::Generator::Pretty::HTML);
	
	my $html = HTML::HTML5::Writer->new(xhtml => 1, polyglot => 1);
	
	sub create_document {
		my $self = shift;
		my $html = $self->{app_object}->template->inject('<title>SPARQL Results Graph</title>');
		$self->inject_document($html->toString, @_);
	}
	
	sub injection_site {
		return '//xhtml:article';
	}
	
	sub serialize_model_to_string {
		my ($proto, $model) = @_;
		my $doc = $proto->create_document($model);
		$html->document($doc);
	}
	
	sub serialize_model_to_file {
		my ($proto, $fh, $model) = @_;
		my $doc = $proto->create_document($model);
		print {$fh} $html->document($doc);
	}
}

{
	package Endpoint::RDFa::XHTML;
	use parent -norequire, qw(Endoint::RDFa);
}

{
	package Endpoint::RDFa::HTML;
	use parent -norequire, qw(Endoint::RDFa);
}

{
	package Endpoint;
	use Moose;
	use match::simple qw(match);
	use HTML::HTML5::Entities qw(encode_entities);
	use namespace::autoclean;
	
	has default_query    => (is => 'ro', default => '');
	has store            => (is => 'ro', required => 1);
	has model            => (is => 'ro', lazy_build => 1);
	has app              => (is => 'ro', lazy_build => 1);
	has _form_html       => (is => 'ro', lazy_build => 1);
	has template         => (is => 'ro', lazy_build => 1);
	has prefixes         => (is => 'ro', lazy_build => 1);
	has graph_formats    => (is => 'ro', lazy_build => 1);
	has bindings_formats => (is => 'ro', lazy_build => 1);

	sub _build_model {
		my $self = shift;
		RDF::Trine::Model->new($self->store);
	}
	
	sub _build_app {
		my $self = shift;
		return sub {
			my $req = Plack::Request->new(@_);
			my $res = $self->handle_request($req);
			$res->finalize;
		};
	}
	
	sub _build_template {
		my $self = shift;
		HTML::Inject->new(target => Path::Tiny->new('template.html')->openr);
	}
	
	sub _build__form_html {
		my $self = shift;
		
		my $graph_formats = do {
			my %tmp = %{ $self->graph_formats };
			join "", map "<option>$_</option>", sort keys %tmp;
		};

		my $bindings_formats = do {
			my %tmp = %{ $self->bindings_formats };
			join "", map "<option>$_</option>", sort keys %tmp;
		};
		
		my $default_query = encode_entities($self->default_query);

		my $form = HTML::HTML5::Sanity::fix_document(
			$self->template->inject(qq{
				<!DOCTYPE html>
				<title>SPARQL Endpoint</title>
				<article id="document_main">
					<h1>SPARQL Endpoint</h1>
					<p>See <a href="http://www.w3.org/TR/sparql11-query/">the SPARQL 1.1 specification</a> for query syntax.</p>
					<form action="" method="post" role="form">
						<div class="form-group">
							<label for="query">SPARQL 1.1 Query</label>
							<textarea cols="60" rows="6" name="query" class="form-control" id="query">$default_query</textarea>
						</div>
						<div class="form-group">
							<label for="format">Result Format</label>
							<select name="format" class="form-control" id="format">
								<option value="">(automatic)</option>
								<optgroup label="Formats for bindings">$bindings_formats</optgroup>
								<optgroup label="Formats for graphs">$graph_formats</optgroup>
							</select>
						</div>
						<button type="submit" class="btn btn-default">Submit Query</button>
					</form>
				</article>
			})
		);
		my $writer = HTML::HTML5::Writer->new(markup => "xhtml", polyglot => 1);
		my $pp = XML::LibXML::PrettyPrint->new_for_html;
		Encode::encode( utf8 => $writer->document($pp->pretty_print($form)) );
	}
	
	sub _build_prefixes {
		+{
			google => 'http://rdf.data-vocabulary.org/#',
			RDF::NS->new->SELECT(qw/
				rdf rdfs owl xsd
				foaf schema awol geo dct sioc vcard rel doac ogp
			/)
		};
	}
	
	sub _build_graph_formats {
		+{
			XML      => ['RDF::Trine::Serializer::RDFXML'    => 'application/rdf+xml' ],
			JSON     => ['RDF::Trine::Serializer::RDFJSON'   => 'application/rdf+json' ],
			Turtle   => ['RDF::Trine::Serializer::Turtle'    => 'text/turtle'],
			NTriples => ['RDF::Trine::Serializer::NTriples'  => 'text/plain'],
			XHTML    => ['Endpoint::RDFa::XHTML'             => 'application/xhtml+xml' ],
			HTML     => ['Endpoint::RDFa::HTML'              => 'text/html' ],
		}
	}
	
	sub _build_bindings_formats {
		my $self = shift;
		Scalar::Util::weaken($self);
		
		my $csv = sub {
			require RDF::Trine::Exporter::CSV;
			RDF::Trine::Exporter::CSV->new->serialize_iterator_to_string(@_);
		};
		
		my $xhtml = sub {
			my $iter = shift;
			$self->serialize_iterator_to_xhtml($iter);
		};
		
		+{
			XML      => [ as_xml    => 'application/sparql-results+xml' ],
			JSON     => [ as_json   => 'application/sparql-results+json' ],
			Text     => [ as_string => 'text/plain' ],
			CSV      => [ $csv      => 'text/comma-separated-values' ],
			HTML     => [ $xhtml    => 'text/html' ],
			XHTML    => [ $xhtml    => 'application/xhtml+xml' ],
		}
	}
	
	sub handle_request {
		my $self = shift;
		my ($req) = @_;
		
		if (match($req->method, [qw/ GET POST /]) and $req->param('query')) {
			my $sparql = $req->param('query');
			my $query  = RDF::QueryX::Lazy->new($sparql, { lang => 'sparql11', update => !!0 });
			return $self->handle_query($req, $query);
		}
		
		return $self->handle_form($req);
	}
	
	sub handle_form {
		my $self = shift;
		my ($req) = @_;
		
		my $r = Plack::Response->new(200);
		$r->content_type('text/html');
		$r->body($self->_form_html);
		return $r;
	}
	
	sub handle_query {
		my $self = shift;
		my ($req, $q) = @_;
		
		my $model   = $self->model;
		my @graphs  = $model->get_graphs->get_all;
		my $dataset = $model->dataset_model(default => \@graphs, named => \@graphs);
		my $a       = $q->execute($dataset);
		
		if ($a->is_graph) {
			return $self->handle_results_graph($req, $q, $a);
		}
		
		return $self->handle_results_bindings($req, $q, $a);
	}
	
	sub handle_results_graph {
		my $self = shift;
		my ($req, $q, $a) = @_;
		
		my $model = RDF::Trine::Model->new;
		$model->add_iterator($a);
		
		my %opts = (
			namespaces => $self->prefixes,
			app_object => $self,
		);
		my ($ct, $ser);
		if ($req->param('format')) {
			my $fmt = $self->graph_formats->{$req->param('format')}
				or die "bad format";
			$ser = $fmt->[0]->new(%opts);
			$ct  = $fmt->[1];
		}
		else {
			($ct, $ser) = RDF::Trine::Serializer->negotiate(
				request_headers => $req->headers,
				%opts,
			);
		}
		
		if ($ser->isa('RDF::Trine::Serializer::Turtle')
		and $model->size < 350
		and eval { require RDF::TrineX::Serializer::MockTurtleSoup }) {
			$ser = RDF::TrineX::Serializer::MockTurtleSoup->new(%opts);
		}
		
		my $r = Plack::Response->new(200);
		$r->content_type($ct);
		$r->body($ser->serialize_model_to_string($model));
		return $r;
	}
	
	sub handle_results_bindings {
		my $self = shift;
		my ($req, $q, $a) = @_;
		
		my $fmt;
		if ($req->param('format')) {
			$fmt = $self->bindings_formats->{$req->param('format')};
		}
		else {
			require HTTP::Negotiate;
			my $chosen = HTTP::Negotiate::choose([
				[ HTML  => 1.000, 'text/html' ],
				[ XHTML => 1.000, 'application/xhtml+xml' ],
				[ XML   => 1.000, 'application/sparql-results+xml' ],
				[ JSON  => 0.900, 'application/sparql-results+json' ],
				[ CSV   => 0.800, 'text/comma-separated-values' ],
				[ Text  => 0.500, 'text/plain' ],
				[ XML   => 0.100, 'application/xml' ],
				[ XML   => 0.100, 'text/xml' ],
				[ JSON  => 0.100, 'application/json' ],
				[ JSON  => 0.100, 'application/x-sparql-results+json' ],
			], $req->headers) || 'XML';
			
			$fmt = $self->bindings_formats->{$chosen};
		}
		
		defined $fmt or die "bad format";
		
		my $ser = $fmt->[0];
		my $ct  = $fmt->[1];
		
		my $r = Plack::Response->new(200);
		$r->content_type($ct);
		$r->body($a->$ser);
		return $r;
	}
	
	sub serialize_iterator_to_xhtml {
		my $self = shift;
		my $iter = $_[0];
		
		my $count = 0;
		my @names = $iter->binding_names;
		
		my $table = "<table class=\"sparql-bindings table table-striped table-responsive\">\n";
		$table .= "<thead>\n";
		$table .= "<tr>\n";
		$table .= join("", map sprintf('<th>%s</th>', encode_entities($_)), @names) . "\n";
		$table .= "</tr>\n";
		$table .= "</thead>\n";
		$table .= "<tbody>\n";
		while (my $row = $iter->next)
		{
			++$count;
			$table .= "<tr>\n";
			$table .= join("", map {
				!defined($_) ?
					"<td><i>(nil)</i></td>"
				: $_->is_nil ?
					"<td><i>(nil)</i></td>"
				: $_->is_resource ?
					sprintf("<td><a href=\"%s\">%s</a></td>", (encode_entities($_->uri)) x 2)
				: $_->is_blank ?
					sprintf("<td><i>%s</i></td>", encode_entities($_->blank_identifier))
				: $_->has_language ?
					sprintf("<td>&quot;%s&quot; <i>\@%s</i></td>", encode_entities($_->literal_value), encode_entities($_->literal_value_language))
				: $_->has_datatype ?
					sprintf("<td>&quot;%s&quot; <i>^^&lt;%s&gt;</i></td>", encode_entities($_->literal_value), encode_entities($_->literal_datatype))
				: sprintf("<td>&quot;%s&quot;</td>", encode_entities($_->literal_value));
			} @{$row}{@names}) . "\n";
			$table .= "</tr>\n";
		}
		$table .= "</tbody>\n";
		$table .= "</table>\n";
		
		my $html = HTML::HTML5::Sanity::fix_document(
			$self->template->inject(qq{
				<!DOCTYPE html>
				<title>SPARQL Query Results</title>
				<article id="document_main">
					<h1>SPARQL Query Results</h1>
					$table
				</article>
			})
		);
		my $writer = HTML::HTML5::Writer->new(markup => "xhtml", polyglot => 1);
		my $pp = ($count < 60) ? XML::LibXML::PrettyPrint->new_for_html : undef;
		Encode::encode( utf8 => $writer->document($pp ? $pp->pretty_print($html) : $html) );
	}
}

my $store = RDF::Trine::Store->new_with_config({
	storetype => 'DBI',
	name      => '',
	dsn       => 'dbi:SQLite:dbname=store.sqlite',
	username  => '',
	password  => '',
});

my $endpoint = Endpoint->new(
	store         => $store,
	default_query => "SELECT ?name WHERE { <http://tobyinkster.co.uk/#i> foaf:name ?name . }\n",
);
$endpoint->app;
