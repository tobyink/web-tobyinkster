#!/usr/bin/perl

%x = (
	
	'CoverBG' => 
	[
		'bg1', 
		'bg2', 
		'bg3'
	],

	'HTMLDiv' =>
	[
		"<div>\n".
		"<h1>[[Title,]]<br><small>by Agatha Christie</small></h1>\n".
		"<div id=\"cover\" class=\"[[CoverBG]]\"><span>[[Title,]]</span><span><small>Agatha Christie</small></span></div>\n".
		"<p><b>\"[[NewspaperReview]]\"</b><br><i>[[Paper]]</i></p>\n".
		"<p>[[Summary]]</p>\n".
		"<p><b>\"[[NewspaperReview]]\"</b><br><i>[[Paper]]</i></p>\n".
		"</div>\n"
	],

	'NewspaperReview' =>
	[
		'[[Review1]] [[Review2]]![[Stars]]',
		'[[Title,]] is [[Review1lc]] [[Review2]]![[Stars]]'
	],
	
	'Review1' =>
	[
		'A fascinating',
		'An exciting',
		'A shocking',
		'An adroit',
		'A thought-provoking',
		'A revealing',
		'A fast-paced',
		'A highly original',
		'A twisy, turny maze of a'
	],
	
	'Review1lc' =>
	[
		'a fascinating',
		'an exciting',
		'a shocking',
		'an adroit',
		'a thought-provoking',
		'a revealing',
		'a fast-paced',
		'a highly original',
		'a twisy, turny maze of a'
	],
	
	'Review2' =>
	[
		'masterpiece',
		'best-seller',
		'tale of intrigue',
		'best-selling masterpiece',
		'murder mystery',
		'mystery',
		'novel',
		'crime novel',
		'page-turner'
	],
	
	'Stars' =>
	[
		' ****',
		' *****',
		''
	],
	
	'Paper' =>
	[
		'The Times',
		'The Guardian',
		'The Scotsman',
		'The Observer',
		'The Independent',
		'The Washington Post',
		'The New York Times',
		'The Daily Mail',
		'Le Monde'
	],
	
	'Title' =>
	[
		'Murder for Tea',
		'Murder on the Menu',
		"Murder at Six O'Clock",
		'Murder at a [[Venue,]]',
		'The Tea-Time Murder'
	],
	
	'Summary' =>
	[
		'[[Intro]][[SuspectList]][[Teaser]]'
	],
	
	'Intro' =>
	[
		'At a [[Venue,]] in [[Place]] a [[PersonalAttribute]] [[Occupation]], [[Fullname]] is found [[DeathType]]. [[Detective,]], who happened to be in the room next door, is asked to investigate while the guests wait for the police, who were [[PoliceExcuse]]. ',
		'[[Detective,]] is called to a [[Venue,]] in [[Place]] where a [[PersonalAttribute]] [[Occupation]], [[Fullname]] has been found [[DeathType]]. '
	],
	
	'SuspectList' =>
	[
		'The suspects are [[Suspect]]; [[Suspect]]; [[Suspect]]; and [[Suspect]]. ',
		'Amongst the suspects that [[Detective,]] uncovers are [[Suspect]], who [[Motive]]; [[Suspect]]; [[Suspect]]; and [[Suspect]]. ',
		'Although [[Suspect]] is initially suspected, [[Detective,]] quickly realises that all is not as it seems: [[Suspect]] and [[Suspect]] also have a lot to gain from this untimely death. ',
		'While [[Suspect]] is under suspision, [[Detective,]] also uncovers a web of other suspects: [[Suspect]], who [[Motive]]; [[Suspect]], who [[Motive]]; and [[Suspect]], who was also acting rather suspiciously. '
	],
	
	'Teaser' =>
	[
		'As the mystery unfolds [[Clue]], [[Clue]] and [[Clue]] all become clues for [[Detective,]].',
		'As the mystery unfolds [[Clue]] and [[Clue]] become clues for [[Detective,]].',
		'Can [[Detective,]] solve the crime with only [[Clue]] for a clue?'
	],
	
	'Clue' =>
	[
		'a torn page',
		'a missing diary',
		'a train ticket to Paddington',
		'a ripped shirt',
		'a missing letter-opener',
		'a chipped plate',
		'an over-ripe tomato',
		'a cup of tea',
		'a smashed bottle',
		'a false moustache',
		'a broken twig',
		'some white powder',
		'a coded message'
	],
	
	'Motive' =>
	[
		'is in line to inherit a lot of money',
		'was seen arguing with the victim the night before',
		'was being blackmailed about an affair with [[Suspect]]',
		'was being blackmailed about an affair with [[Suspect]]',
		'has a criminal past',
		'was being blackmailed by the victim',
		'had recently broken off an affair with the victim',
		'was reputedly not very nice',
		'had sent threatening letters to the victim',
		'had been humiliated by the victim',
		"wasn't too fond of the victim"
	],
	
	'DeathType' =>
	[
		'murdered',
		'drowned',
		'hanged',
		'stabbed',
		'shot through the head',
		'shot through the heart',
		'pushed off a cliff',
		'poisoned',
		'clubbed to death with a rolling pin',
		'overdosed on sleeping tablets',
		'killed in some unspecified but violent manner',
		'beaten to death with a marrow'
	],
	
	'PoliceExcuse' =>
	[
		'caught in a snow drift',
		"too busy implementing the government's latest crakdown on [[Crackdown]]",
		"too busy implementing the government's latest crakdown on [[Crackdown]]",
		'also murdered',
		'really quite inept',
		'having a lovely cup of tea',
		'doing paperwork',
		'passed out drunk',
		'sadly unable to attend, due to a prior engagement',
		'on a training day in Staines',
		'delayed for some reason'
	],
	
	'Crackdown' =>
	[
		'teenagers',
		'drugs',
		'terrorists',
		'Brazillians in tube stations',
		'fluffy bunnies',
		'drunkenness',
		'pubs that close at eleven'
	],
	
	'Detective' => ['Hercule Poirot','Miss Marple'],

	'Fullname' => ['[[Firstname]] [[Surname]]'],

	'Firstname' => ['[[FirstnameF]]','[[FirstnameM]]'],
	
	'Suspect' => 
	[
		'a [[Occupation]] called [[Fullname]]',
		'[[Fullname]], a [[Occupation]] from [[Place]]',
		'[[Fullname]], a [[PersonalAttribute]] [[Occupation]]',
		'the [[PersonalAttribute]] [[Fullname]]',
		'a [[Occupation]] called [[Fullname]]',
		'[[Fullname]], a [[Occupation]] from [[Place]]',
		'[[Fullname]], a [[PersonalAttribute]] [[Occupation]]',
		'the [[PersonalAttribute]] [[Fullname]]',
		'[[Fullname]], the obligatory red-herring',
		'a mysterious stranger called [[Firstname]]',
		'a vagrant, seen wondering around the grounds the night before',
		'Captain Rufus [[Surname]] of the First Royal Batallion',
		'Second Leiutenant [[FirstnameM]] [[Surname]]'
	],
	
	'FirstnameM' =>
	[
		'Daniel',
		'Brian',
		'Johnathan',
		'David',
		'Mark',
		'Joseph',
		'Steven',
		'Adam',
		'Andrew',
		'Thomas',
		'Richard',
		'Harold',
		'Luke',
		'Edmund',
		'Ernest',
		'Sir Hilary',
		'Sir Beverley',
		'Sir Roger',
		'Sir Richard',
		'Lord'
	],
	
	'FirstnameF' =>
	[
		'Sofia',
		'Amy',
		'Katie',
		'Stephanie',
		'Mary',
		'Margaret',
		'Patricia',
		'Elizabeth',
		'Madeline',
		'Emma',
		'Janet',
		'Jane',
		'Danielle',
		'Lady',
		'Lady'
	],
	
	'Surname' =>
	[
		'Brown',
		'Black',
		'Jones',
		'James',
		'Nelson',
		'Jackson',
		'Presley',
		"O'Conner",
		'St John',
		'Cholmondsley',
		'Featherstonehaugh',
		'Colquhoun',
		'Marjoribanks',
		'Beauchamp',
		'Belvoir',
		'Woolfhardisworthy',
		'Cockburn',
		'Holmes-Warner',
		'Alton-Smith',
		'Belvior-Jones',
		'Holmes-Smith',
		'MacDonald',
		'MacAngus',
		'Chissinger',
		'Blair',
		'Donaldson',
		'Leavy',
		'Throatwarbler-Mangrove',
		'Mainwaring',
		'Launceston'
	],
		
	'PersonalAttribute' =>
	[
		'young',
		'beautiful',
		'retired',
		'troubled',
		'deaf',
		'elderly',
		'rich',
		'destitute'
	],
	
	'Occupation' =>
	[
		'pastry chef',
		'novelist',
		'doctor',
		'violinist',
		'archaeologist',
		'lawyer',
		'vicar',
		'busy-body',
		'post-office clerk',
		'shop assistant',
		'businessman',
		'sailor',
		'soldier',
		'dentist'
	],
	
	'Venue' =>
	[
		'mansion',
		'golf club',
		'hotel',
		'manor',
		'vicarage',
		'large house'
	],
	
	'Place' =>
	[
		'London',
		'Paris',
		'Marseille',
		'Reading',
		'Buckinghamshire',
		'Kent',
		'Berkshire',
		'Northamptonshire',
		'Surrey',
		'Sussex',
		'Hertfordshire',
		'Edinburgh',
		'Devonshire',
		'Wimbledon',
		'Richmond',
		'The Red-Light District',
		'Upper Walsinghamtonville',
		'Little Diddlington',
		'Little Piddlington',
		'Tringfordham',
		'Aldridge-upon-Tweedle'
	]
	
);	

$div = &expand('[[HTMLDiv]]');

print <<EOF;
Content-Type: text/html

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<title>Create Your Own Agatha Christie Novel</title>

<style type="text/css">
BODY {
	margin: 0;
	padding: 1em 6em;
	font-family: "Arial", sans-serif;
}
H1 { text-align: center; margin: 0 0 1.5em; }
#cover {
	height: 350px;
	width: 250px;
	color: black;
	text-align: center;
	float: left;
	margin: 0 1em 1em 0;
	border: 2px solid black;
}
.bg1 { background: #d7cfaa url("bg1.png"); }
.bg2 { background: #d9cfd8 url("bg2.png"); }
.bg3 { background: #eddfda url("bg3.png"); }
#cover span
{
	text-align: center;
	margin: 10px;
	display: block;
	font-size: 36px;
	font-weight: bold;
	font-family: "Garamond", "Times New Roman", serif;
}
#cover small
{
	text-align: center;
	margin: 10px;
	display: block;
	font-size: 18px;
	font-weight: bold;
	font-family: "Garamond", "Times New Roman", serif;
}
B {
	font-size: 120%; 
}
HR {
	clear: both;
}
</style>

$div

<hr>
<p>Automatically generated by Toby Inkster's <a 
href="source.html" title="Perl Source Code"><i>Create Your Own
Agatha Christie Novel</i></a>. Use your browser's "Reload" button to create
another novel, each one as original and well thought out as a real
Agatha Christie best-seller.</p>
<p><b>Disclaimer:</b> I do actually rather like Agatha Christie.
There is no disclaimer like this at the bottom of my <a
href="http://tobyinkster.co.uk/Software/dan_brown/">Dan Brown generator</a>
though.</p>
EOF

sub expand
{
	local $_ = shift;
	local $token;
	local @poss;
	local %replacement;
	local $repeat;
	
	while (/\[\[[A-Za-z0-9]*\,?\]\]/)
	{
	
		&debug("regexp match: $&");
	
		$token = $&; 		# last matched thing
		$token =~ s/[\[\]]//g; 	# remove brackets
		
		$repeat = 0;
		if ($token =~ /\,/)
		{
			$repeat = 1;
			$token =~ s/\,//;
		}
		
		&debug("token: $token");
		
		if (!defined($replacement{$token}))
		{
			warn "PercentX doesn't exist!\n" 
				unless (%x);
			warn "PercentX doesn't list $token!\n" 
				unless ($x{$token});
				
			# find possible replacements
			@poss  = @{$x{$token}};
						
			# choose one
			$replacement{$token} = $poss[rand @poss];
			
			&debug("defined $token as '".$replacement{$token}."'");
		}
		else
			{ &debug("Already defined $token. Using again."); }
		
		s/\[\[$token\,?\]\]/$replacement{$token}/;
		
		undef($replacement{$token}) unless ($repeat==1);
	}
	
	return $_;
}

sub debug
{
	local $d = shift;
	#print STDERR "DEBUG-- $d\n";
}
