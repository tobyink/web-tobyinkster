#!/usr/bin/env plackup

use strict;
use warnings;
use lib qw( /opt/perl/lib/lib/perl5/ );

{
	package Local::HandleMail;
	
	use Module::Runtime qw(use_module);
	use Email::Sender::Simple qw(sendmail);
	
	use Moo;
	with qw( MooseX::ConstructInstance );
	has destination      => (is => 'ro', required => 1);
	has subject_template => (is => 'ro', default => '%s');
	has email_class      => (is => 'ro', required => 1);
	has transport        => (is => 'ro', required => 1);
	has response_class   => (is => 'ro', default => sub { use_module 'Plack::Response' });
	has success_redirect => (is => 'ro', required => 1);
	
	sub handle_request {
		my $self  = shift;
		my ($req) = @_;
		
		$req->param('to') =~ /toby/i or die("error");
		
		my $msg = $self->construct_instance(
			$self->email_class,
			header => [
				To       => $self->destination,
				From     => scalar $req->param('from'),
				Subject  => sprintf($self->subject_template, scalar $req->param('subject')),
			],
			body => scalar $req->param('body'),
		);	
		
		sendmail($msg, $self->transport);
		
		my $res = $self->construct_instance($self->response_class);
		$res->redirect($self->success_redirect);
		return $res;
	}
}

require Email::Sender::Transport::Sendmail;

my $obj = Local::HandleMail->new(
	destination       => "mail\@tobyinkster.co.uk",
	subject_template  => "[toby.ink] %s",
	success_redirect  => "http://toby.ink/email-thanks",
	email_class       => sub {
		require Email::Simple;
		Email::Simple->create(@_);
	},
	transport         => Email::Sender::Transport::Sendmail->new,
);

my $app = sub {
	require Plack::Request;
	$obj->handle_request( Plack::Request->new(shift) )->finalize;
};
