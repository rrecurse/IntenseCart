#!/usr/bin/suidperl
use Cyrus::IMAP::Admin;
$ENV{'PATH'} = "/usr/bin";

$SASL = "/usr/sbin/saslpasswd2";

chomp ($account = <STDIN>);
chomp ($oldpasswd = <STDIN>);
chomp ($newpasswd = <STDIN>);

close STDOUT;
close STDERR;
open STDOUT, ">>/tmp/pass.stdout";
open STDERR, ">>/tmp/pass.stderr";
print STDERR "----------------\n";
print STDERR  "Account: $account\n";
print STDERR "Old: $oldpasswd\n";
print STDERR "New: $newpasswd\n";

if ($account =~ /@/) {
  ($user, $domain) = split(/@/, $account);
} else {
  $user = $account;
  $domain = "";
}

# Untaint to make perl happy...
if ($user =~ /^([-A-Za-z0-9_.]+)$/) {
  $user = $1;
} else {
  print STDERR "User is tainted: $user!\n";
  exit 1;
}

if ($user =~ /^\-/) {
  print STDERR "User is tainted: $user!\n";
  exit 2;
}

if ($domain =~ /^([-A-Za-z0-9.]+)$/) {
  $domain = $1;
} else {
  print STDERR "Domain is tainted: $domain\n";
  exit 3;
}

if ($domain =~ /^\-/) {
  print STDERR "Domain is tainted: $domain!\n";
  exit 4;
}

# make sure we can connect to cyrus using the given username / password combination
$client = Cyrus::IMAP::Admin->new("localhost");

$err = $client->authenticate(
  -user => $account,
  -mechanism => "login",
  -password => $oldpasswd
);

$IMAPERROR = $client->error;
if ($IMAPERROR || !$err) {
  print STDERR "Error connecting to IMAP server: $IMAPERROR\n";
  exit 5;
}

# Ok, password is good, so change it
@args = ();
push @args, $SASL;
push @args, "-p";
if (length($domain) > 0) {
  push @args, "-u";
  push @args, $domain;
}
push @args, $user;

$pid = open(PIPE, "|-");
if ($pid) {
  print PIPE "$newpasswd\n";
  close PIPE;
} else {
  exec @args;
}
exit 100;
