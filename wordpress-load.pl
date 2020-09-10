use v5.12;
use strict;
use warnings;
use utf8::all;
use XML::Atom::Entry;
use Data::Dumper;
use Path::Tiny;
use Path::Iterator::Rule;
use DBI;
use Encode;

sub dt {
	my $str = shift;
	$str =~ s/T/ /;
	$str =~ s/Z//;
	$str =~ s/[+].*//;
	$str;
}

my $dbh    = DBI->connect('DBI:mysql:database=tobyinkwp', 'tobyinkwp', 'tobyinkwp');
my $prefix = 'wp_';

my $sth_post_select = $dbh->prepare("SELECT * FROM ${prefix}posts WHERE guid=?");

my $sth_post_update = $dbh->prepare(<<"SQL");
UPDATE ${prefix}posts
SET
	post_author=1,
	post_date=?,
	post_date_gmt=?,
	post_content=?,
	post_title=?,
	post_excerpt=?,
	post_status='publish',
	post_name=?,
	post_modified=?,
	post_modified_gmt=?,
	post_type='post',
	comment_count=0,
	comment_status='closed',
	ping_status='closed'
WHERE guid=?
SQL

my $sth_post_insert = $dbh->prepare(<<"SQL");
INSERT INTO ${prefix}posts (
	post_author,
	post_date,
	post_date_gmt,
	post_content,
	post_title,
	post_excerpt,
	post_status,
	post_name,
	post_modified,
	post_modified_gmt,
	post_type,
	comment_count,
	comment_status,
	ping_status,
	guid,
	to_ping,
	pinged,
	post_content_filtered
)
VALUES ( 1, ?, ?, ?, ?, ?, 'publish', ?, ?, ?, 'post', 0, 'closed', 'closed', ?, '', '', '' )
SQL

my %tags;
my $sth = $dbh->prepare("SELECT t.term_id,t.name FROM ${prefix}term_taxonomy tt INNER JOIN ${prefix}terms t ON t.term_id=tt.term_id WHERE tt.taxonomy='post_tag'");
$sth->execute();
while (my $row = $sth->fetchrow_arrayref) {
	$tags{ $row->[1] } = $row->[0];
}
sub get_tag_id {
	my $name = shift;
	return $tags{$name} if exists $tags{$name};
	
	my $slug = lc($name);
	$slug =~ s/\W/-/g;
	$slug =~ s/--/-/g while ($slug =~ /--/);
	
	my $sth = $dbh->prepare("INSERT INTO ${prefix}terms (name, slug, term_group) VALUES (?, ?, 0)");
	$sth->execute($name, $slug);
	my $id = $dbh->{'mysql_insertid'};
	
	my $sth2 = $dbh->prepare("INSERT INTO ${prefix}term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (?, 'post_tag', '', 0, 0)");
	$sth2->execute( $id );
	
	$tags{ $name } = $dbh->{'mysql_insertid'};
}

my %slugs;
my $rule = Path::Iterator::Rule->new;
$rule->file->name( '*.atom' );
my $iter = $rule->iter( 'public_html/blog/' );
while ( defined( my $_file = $iter->() ) ) {
	my $file = path( $_file );
	next if $file->parent->parent->basename eq 'public_html';
	
	my $xml   = $file->slurp_utf8;
	my $entry = XML::Atom::Entry->new(\$xml);
	
	my $slug = $file->parent->basename;
	my $slug_usage = ++$slugs{$slug};

	if ( $slug_usage > 1 ) {
		$slug .= '-tobyink-' . $slug_usage;
	}
	
	my $num_id;
	$sth_post_select->execute( $entry->id );
	if ( my $row = $sth_post_select->fetchrow_arrayref ) {
		$num_id = $row->[0];
	}
	
	if ( defined $num_id ) {
		$sth_post_update->execute(
			dt($entry->published),
			dt($entry->published),
			$entry->content->body,
			$entry->title,
			$entry->summary // '',
			$slug,
			dt($entry->updated),
			dt($entry->updated),
			$entry->id
		);
		
		say "UPDATED POST $num_id ENTRY ", $entry->id;
	}
	else {
		$sth_post_insert->execute(
			dt($entry->published),
			dt($entry->published),
			$entry->content->body,
			$entry->title,
			$entry->summary // '',
			$slug,
			dt($entry->updated),
			dt($entry->updated),
			$entry->id
		);
		$sth_post_select->execute( $entry->id );
		if ( my $row = $sth_post_select->fetchrow_arrayref ) {
			$num_id = $row->[0];
		}
		
		say "INSERTED POST $num_id ENTRY ", $entry->id;
	}
	
	my @tags = map { $_->label or $_->term } $entry->categories;
	do {
		my $kind = 5;
		if ( $entry->id =~ /ilovecbeebies/ ) {
			$kind = 4;
		}
		elsif ( $entry->id =~ /blogs.perl.org/ ) {
			push @tags, 'perl';
			$kind = 3;
		}
		elsif ( "@tags" =~ /recipes/i ) {
			$kind = 313;
		}
		$dbh->do("DELETE FROM ${prefix}term_relationships WHERE object_id=${num_id} AND term_taxonomy_id IN (3,4,5,313)");
		$dbh->do("INSERT INTO ${prefix}term_relationships VALUES (${num_id}, ${kind}, 0)");
		say " - updated category";
	};
	
	my @links = $entry->links;
	if (@links) {
		my $sth = $dbh->prepare("SELECT * FROM ${prefix}postmeta WHERE post_id=? AND meta_key='source_link'");
		$sth->execute($num_id);
		my $is_update = 0;
		if ( $sth->fetchrow_arrayref ) {
			$sth = $dbh->prepare("UPDATE ${prefix}postmeta SET meta_value=? WHERE post_id=? AND meta_key='source_link'");
			++$is_update;
		}
		else {
			$sth = $dbh->prepare("INSERT INTO ${prefix}postmeta (meta_value, meta_key, post_id) VALUES (?, 'source_link', ?)");
		}
		if ( $links[0]->href !~ /tobyinkster.co.uk/ ) {
			$sth->execute( $links[0]->href, $num_id );
			say sprintf(" - %s link to %s", $is_update ? 'updated' : 'inserted', $links[0]->href );
		}
	}
	
	if (@tags) {
		my $sth_tag_select = $dbh->prepare("SELECT * FROM ${prefix}term_relationships WHERE object_id=? AND term_taxonomy_id=?");
		TAG: for my $tag ( @tags ) {
			my $tag_id = get_tag_id( $tag );
			$sth_tag_select->execute( $num_id, $tag_id );
			next TAG if $sth_tag_select->fetchrow_arrayref;
			$dbh->do("INSERT INTO ${prefix}term_relationships (object_id, term_taxonomy_id) VALUES ($num_id, $tag_id)");
		}
	}
}

$dbh->do(<<"SQL");
UPDATE ${prefix}term_taxonomy SET count = (
SELECT COUNT(*) FROM ${prefix}term_relationships rel 
    LEFT JOIN ${prefix}posts po ON (po.ID = rel.object_id) 
    WHERE 
        rel.term_taxonomy_id = ${prefix}term_taxonomy.term_taxonomy_id 
        AND 
        ${prefix}term_taxonomy.taxonomy NOT IN ('link_category')
        AND 
        po.post_status IN ('publish', 'future')
)
SQL

