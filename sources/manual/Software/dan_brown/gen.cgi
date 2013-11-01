#!/usr/bin/perl

%x = (
	'GIF' => 
	[
		'ball_gold.gif', 
		'ball_ocean.gif', 
		'ball_white.gif'
	],
	
	'CoverBG' => 
	[
		'purplebg', 
		'redbg', 
		'bluebg',
		'blackbg'
	],

	'HTMLDiv' =>
	[
		"<div>\n".
		"<h1>[[Title,]]<br><small>by Dan Brown</small></h1>\n".
		"<div id=\"cover\" class=\"[[CoverBG]]\"><span>[[Title,]]</span><img src=\"[[GIF]]\" alt=\"\"><span><small>Dan Brown</small></span></div>\n".
		"<p><b>\"[[NewspaperReview]]\"</b><br><i>[[Paper]]</i></p>\n".
		"<p>[[Summary]]</p>\n".
		"<p><b>\"[[NewspaperReview]]\"</b><br><i>[[Paper]]</i></p>\n".
		"</div>\n"
	],

	'Title' =>
	[
		'[[TitleObject,]]s and [[TitleObject2,]]s',
		'The [[TitleAdj,]] [[TitleObject,]]',
		'The [[TitleObject,]] of [[TitleAdj,]]'
	],
	
	'TitleAdj' => ['Gold', 'White', 'Black', 'Silver', 'Scarlet', 'Amber',
			'Michaelangelo', 'Da Vinci', 'Rafaelo', 'Leonardo',
			'Crowley', 'Comte de Saint Germain',
			# homage to Umberto Eco
			'Ardenti',
			# /homage
			'Magick', 'Soloman'
			],
	
	'TitleObject' => 
	[
		'Pyramid',
		'Band',
		'Book',
		'Wand',
		'Ring',
		'Circle',
		'Box',
		'Crucifix',
		'Code',
		'Temple',
		'Cross',
		'Secret',
		'Shroud',
		'Crucible',
		'Chapel',
		'Arch',
		'Piece',
		'Godhead',
		'Key',
		'Shrub',
		'Angel',
		'Demon'
	],

	'TitleObject2' => 
	[
		'Wand',
		'Ring',
		'Crucifix',
		'Code',
		'Temple',
		'Secret',
		'Shroud',
		'Crucible',
		'Chapel',
		'Arch',
		'Piece',
		'Godhead',
		'Key',
		'Angel',
		'Demon'
	],

	'NewspaperReview' =>
	[
		'[[Review1]] [[Review2]]![[Stars]]',
		'[[Title,]] is [[Review1lc]] [[Review2]]![[Stars]]'
	],
	
	'Review1' =>
	[
		'A fascinating',
		'An explosive',
		'An exciting',
		'A shocking',
		'A highly original'
	],
	
	'Review1lc' =>
	[
		'a fascinating',
		'an explosive',
		'an exciting',
		'a shocking',
		'a highly original'
	],
	
	'Review2' =>
	[
		'masterpiece',
		'best-seller',
		'tale of intrigue',
		'tale of suspense',
		'best-selling masterpiece',
		'best-selling masterpiece of suspense',
		'novel',
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
		'Le Monde',
		'Time Out', 'Time Out'
	],
	
	'Summary' =>
	[
		'For hundreds of years, people have speculated about the existance of [[ShadyGroup,]]. [[Intro]][[Middle]][[Outro]]',
		'[[Intro]][[Middle]][[Outro]]',
		'[[MurderStory]]',
		"It's nearly the millennium. [[Intro]][[Middle]][[Outro]]",
		'[[Intro]][[Middle]][[Outro]]'
	],
	
	'Intro' =>
	[
		'[[ShadyGroup,]] have kept the secret of [[Secret]] for [[LongPeriod]]. ',
		'The secret of [[Secret]] has been guarded by [[ShadyGroup,]] for [[LongPeriod]]. ',
		'Deep in the sewers of [[Place]], [[ShadyGroup,]] guards the dark secret of [[Secret]]. '
	],
	
	'Middle' =>
	[
		'A [[PersonalAttribute]] [[Occupation]] has stumbled upon their trail while [[InnocentActivity]]. ',
		'A [[PersonalAttribute]] [[Occupation]] has stumbled upon their trail. ',
		'A [[PersonalAttribute]] [[Occupation]], [[Firstname]] [[Surname]], has stumbled upon their trail. ',
		'But one of them has decided to use the secret for his own gain. '
	],
	
	'Outro' =>
	[
		"It's now a desparate race to [[Place]] with [[SomethingImportant]] at stake. ",
		"It's now a desparate race [[Expanse]] and [[SomethingImportant]] is at stake. ",
		"[[ShadyGroup]] will stop at nothing to keep their secret. ",
		"[[ShadyGroup]] will stop at nothing to keep their secret, but can [[ShadyGroup]] stop *them* first?! ",
	],

	'MurderStory' =>
	[
		"[[FirstnameM]] [[Surname]], a [[Occupation]], is found murdered in [[Place,]] and [[FirstnameM,]] [[Surname]], a [[Occupation]] is asked to supply expert information to the [[Place]] police. [[FirstnameM]] teams up with [[FirstnameF]] [[Surname]], a beautiful [[Occupation]], to solve the murder and unravel a mystery involving the secret of [[Secret]], a desparate chase across [[Expanse]] and a fateful showdown in [[Place]] with the [[ShadyGroup]] where [[SomethingImportant]] is at stake.",
		"[[FirstnameM]] [[Surname]], a [[Occupation]], is found murdered in [[Place,]] and [[FirstnameM,]] [[Surname]], a [[Occupation]] is asked to supply expert information to the [[Place]] police. Soon [[FirstnameM,]] finds himself involved with a beautiful [[Occupation]] called [[FirstnameF]], meetings in [[Place]] with the highly-secretive group [[ShadyGroup]] and a dramatic showdown with [[ShadyGroup,]] after a chase across [[Expanse]]. When [[SomethingImportant]] is at stake, [[ShadyGroup]] won't let anything stand in their way. Can [[FirstnameM]] stop them in time?"
	],
	
	'Firstname' => ['[[FirstnameF]]','[[FirstnameM]]'],
	
	'FirstnameM' =>
	[
		'Dan',
		'Brian',
		'John',
		'Dave',
		'Mark',
		'Joe',
		'Steven',
		'Adam',
		'Andrew'
	],
	
	'FirstnameF' =>
	[
		'Chloe',
		'Zoe',
		'Sofia',
		'Amy',
		'Katie',
		'Stephanie'
	],
	
	'Surname' =>
	[
		'Brown',
		'Black',
		'Jones',
		'Alberti',
		'James',
		'Nelson',
		'Jackson',
		'Presley',
		"O'Conner"
	],
	
	'ShadyGroup' =>
	[
		'The Knights Templar',
		'The Illuminati',
		'The Freemasons',
		'The Rosicrucians',
		'The Ordo Templi Orientis',
		'The Osirica',
		'The Cabalists',
		'The Followers of the Temple Of The Vampire',
		'The Secret Order of the Knights of the Round Table',
		'The Cardinals of the Catholic Church',
		'The Church of Satan',
		'The Gnostics',
		'The Elders of Zion',
		'The Assassins',
		'The Jesuits',
		'The Paulicians',
		'The Synarchists',
		'The Templi Resurgentes Equites Synarchici',
		'The Babylonian Brotherhood',
		'The Hermetic Order of the Golden Dawn',
		'Opus Dei',
		'The Priory of Sion',
		'Enron',
		'The British Royal Family'
	],
	
	'Secret' =>
	[
		'Ra',
		'the Holy Grail',
		'the Golden Fleece',
		'the Holy Chalice',
		'Excalibur',
		'the blood line of Jesus',
		'the Spear of Destiny',
		'the City of Atlantis',
		'the Ark of the Covenant',
		'the Pyramids of Egypt',
		'the Crystal skulls',
		'the Delphic Oracle',
		'Camelot',
		'the Garden of Eden',
		'King Arthur',
		'Mary Magdalene',
		"Noah's Ark",
		'Stonehenge',
		'the Ten Lost Tribes of Israel',
		"Lucifer's crypt",
		'the Winged Skull of Ur',
		'the first of the great Old &AElig;on',
		'the umbilicus mundi',
		'the telluric currents',
		"qabbalah nevu'it",
		'time travel',
		'the identity of the Anti-Christ',
		'the Anunnaki',
		'antimatter',
		"the Colonel Sanders' fourteen herbs and spices",
		'the lady Pope Joan',
		'Mary Magdalene'
	],
	
	'LongPeriod' =>
	[	
		'over a thousand years',
		'nearly a millennium',
		'twelve centiuries',
		'centuries'
	],
	
	'PersonalAttribute' =>
	[
		'intrepid',
		'young',
		'beautiful',
		'Italian',
		'psychic',
		'dying',
		'retired',
		'troubled'
	],
	
	'Occupation' =>
	[
		'reporter',
		'pastry chef',
		'novelist',
		'researcher',
		'doctor',
		'taxi-driver',
		'violinist',
		'p&aelig;diatric nurse',
		'archaeologist',
		'lawyer',
		'civil engineer',
		'priest',
		'scientist',
		'librarian'
	],
	
	'InnocentActivity' =>
	[
		'decyphering an encoded letter found in a library',
		'overhearing a conversation in [[Place]]',
		'solving a crossword puzzle'
	],
	
	'Place' =>
	[
		'London',
		'Paris',
		'Baghdad',
		'Archangel',
		'St Petersberg',
		'Rome',
		'Prague',
		'Athens',
		'Sparta',
		'Troy',
		'Istanbul',
		'Cairo',
		'Atlantis',
		'Jerusalem',
		'the North Pole',
		'Bethlehem',
		'Vatican City',
		'Seville',
		'Rio de Janeiro',
		'New York',
		'Edinburgh',
		'Dublin',
		'Cardiff',
		'Toulouse',
		'Zurich',
		'Marseille'
	],
	
	'Expanse' =>
	[
		'across the wastelands of Siberia',
		'through modern-day Europe',
		'around the Mediterranean',
		'through the caverns of old Constantinople',
		'across the Middle East'
	],
	
	'SomethingImportant' =>
	[
		'the very fabric of space-time itself',
		'the fate of the world',
		'western civilisation',
		'world peace',
		'a small MacDonalds Franchise',
		"the wealth of one of Europe's oldest families"
	]
);	

$div = &expand('[[HTMLDiv]]');

print <<EOF;
Content-Type: text/html

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<title>Create Your Own Dan Brown Novel</title>

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
	color: white;
	text-align: center;
	float: left;
	margin: 0 1em 1em 0;
	border: 2px solid black;
}
.purplebg { background: purple url("purplebg.png"); }
.redbg    { background: red url("redbg.png"); }
.bluebg   { background: blue url("bluebg.png"); }
.blackbg  { background: black url("blackbg.png"); }
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
Dan Brown Novel</i></a>. Use your browser's "Reload" button to create
another novel, each one as original and well thought out as a real
Dan Brown best-seller.</p>
<p>Update (Dec 2007): I'm told that I've been mentioned in <a 
href="http://timeout.com"><i>Time Out</i></a>.</p>
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
