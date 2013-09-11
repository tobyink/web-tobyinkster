#!/usr/bin/env perl

use strict;
use warnings;
use lib qw( /home/tai/perl5/cpan_khampton/magpie/lib /home/tai/perl5/hg/p5-demiblog4/lib );

use DBI;
use Encode qw( decode_utf8 encode_utf8 );
use RDF::Trine;
use RDF::Trine::Iterator 'sgrep';
use RDF::NS::Trine;
use Path::Tiny 'path';
use Demiblog4::Formatter::Demiblog3Textile;
use XML::Atom::Entry;

(my $trinedb = __FILE__) =~ s/\.pl\z/.trine/;

my $dbh    = DBI->connect('dbi:SQLite:dbname='.$trinedb);
my $store  = RDF::Trine::Store::DBI->new(Demiblog4 => $dbh);
my $model  = RDF::Trine::Model->new($store);
my $ns     = RDF::NS::Trine->new('20130402');

my $author = XML::Atom::Person->new(Version => '1.0');
$author->name('Toby Inkster');
$author->email('mail@tobyinkster.co.uk');
$author->uri('http://tobyinkster.co.uk/');

my $textile = Demiblog4::Formatter::Demiblog3Textile->new;

for my $s ($model->subjects($ns->rdf_type, $ns->rss_item))
{
	next unless $s->is_resource;
	next unless $s->uri =~ m{\Ahttp://tobyinkster.co.uk/blog/([0-9]{4})/([0-9]{2})/([0-9]{2})/([\w-]+)/\z};
	
	my ($yyyy, $mm, $dd, $slug) = ($1, $2, $3, $4);
	
	my $path = path(qw( sources legacy_content blog ), $yyyy, $mm, $dd, $slug, 'index.atom');
	$path->parent->mkpath;
	
	next if $path->exists;
	
	print "Writing $path...\n";
	my $data = $model->get_statements($s, undef, undef)->as_hashref->{$s->uri};
	
	my $entry = XML::Atom::Entry->new(Version => '1.0');
	$entry->id($s->uri);
	$entry->add_link(do {
		my $link = XML::Atom::Link->new(Version => '1.0');
		$link->rel('self');
		$link->href( $entry->id );
		$link;
	});
	$entry->title( $data->{ $ns->rss_title->uri }[0]{value} );
	$entry->author( $author );
	
	if (exists $data->{'http://ontologi.es/demiblog#textile_body'}) {
		my $txt = $data->{'http://ontologi.es/demiblog#textile_body'}[0]{value};
		my $content = XML::Atom::Content->new;
		$content->type('text/html');
		$content->body($textile->to_html(encode_utf8 $txt));
		$entry->content($content);
	}
	else {
		warn "NO BODY!";
	}
	
	$entry->published( $data->{ $ns->dcterms_created->uri }[0]{value} );
	$entry->updated( $data->{ $ns->dcterms_modified->uri }[0]{value} );
	$entry->add_link(do {
		my $link = XML::Atom::Link->new(Version => '1.0');
		$link->rel('license');
		$link->href($data->{ $ns->dcterms_license->uri }[0]{value});
		$link;
	});
	
	for my $tag (@{$data->{'http://www.holygoat.co.uk/owl/redwood/0.1/tags/taggedWithTag'}}) {
		next unless $tag->{value} =~ m{\Ahttp://tobyinkster.co.uk/tag/(.+)/#concept\z};
		my $cat = XML::Atom::Category->new(Version => '1.0');
		$cat->term($1);
		$entry->add_category($cat);
	}
	
	print {$path->openw_utf8} $entry->as_xml;
}
