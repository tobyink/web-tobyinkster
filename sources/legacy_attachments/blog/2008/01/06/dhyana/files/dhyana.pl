#!/usr/bin/perl

our $VERSION = '0.3';

# dhyana.pl/0.3 (20080106)
#
# 	* Added differently coloured title
# 	* Use Getopt to parse command line
# 	* Improved handling of certain dodgy WMV files
# 	* Improved use of FFMPEG
# 	* Code straightened out to use functions
# 	* [Matt Pinkham] Matt's capture mode
#	* Added lots of help.
#
# dhyana.pl/0.2 (20071118)
#
# 	* Workarounds for odd PNG files
#	* Added 'auto' geometry.
#
# dhyana.pl/0.1 (20070819)
#
# 	* Initial release


# Silly tests.
die "This program cannot run if 00000001.png exists.\n" if (-e '00000001.png');
die "This program cannot run if 00000002.png exists.\n" if (-e '00000002.png');
die "This program cannot run if 00000001.gif exists.\n" if (-e '00000001.gif');

use Getopt::ArgvFile qw(argvFile);
use Getopt::Long 2.33 qw(GetOptions); 
use Pod::Usage;
use POSIX;
use strict;

# Paths to stuff.
our %path;
$path{convert}  = '/usr/bin/convert';
$path{ffmpeg}   = '/usr/bin/ffmpeg';
$path{midentify}= '/usr/bin/midentify';
$path{montage}  = '/usr/bin/montage';
$path{mplayer}  = '/usr/bin/mplayer';

# Style settings.
our $text_font      = '/usr/share/fonts/TTF/Vera.ttf';
our $text_colour    = 'black';
our $text_bg        = '#f8f8ff';
our $text_size      = '12';
our $text2_font     = '/usr/share/fonts/TTF/VeraSeBd.ttf';
our $text2_colour   = '#009900';
our $text2_size     = '24';

my  $file;
our $rows = 6;
our $cols = 4;
our $geom = 'auto';
our $Title;
our $MM   = 0;
our $CM   = 'auto';
our $i    = 0; # counter
our $e    = 0; # error frame counter

our $verbosity = 1;

# Read from an options file
&argvFile
(
	'home'=>1,
	'current'=>1,
	'resolveEnvVars'=>1,
	'resolveRelativePathes'=>1,
	'startupFilename'=>'.dhyanarc',
	'fileOption'=>'--options'
);

my $man = 0;
my $help = 0;

&GetOptions
(
	"path=s%"		=> \%path,
	"font-family=s"   	=> \$text_font,
	"colour|color=s"  	=> \$text_colour,
	"background=s"    	=> \$text_bg,
	"font-size=i"     	=> \$text_size,
	"heading-font-family=s"	=> \$text2_font,
	"heading-colour|heading-color=s"
				=> \$text2_colour,
	"heading-font-size=i"	=> \$text2_size,
	"multi!"		=> \$MM,
	"title|t=s"		=> \$Title,
	"rows|r=i"		=> \$rows,
	"columns|cols|c=i"	=> \$cols,
	"geometry|geom|geo|g=s"	=> \$geom,
	"capture-mode|C=s"	=> \$CM,
	"verbose|v+"		=> \$verbosity,
	"quiet"			=> sub { $verbosity=0; },
	'help|usage|h' 		=> \$help,
	'man'			=> \$man,
	'version'		=> sub { die $VERSION; }
);
pod2usage(2) if $help;
pod2usage(0) if $man;
exit if ($help || $man);

if ($MM)
{
	while ($file = shift @ARGV)
	{
		if (-s $file)
		{
			&process($file);
		}
		else
		{
			&debug("$file not found.", 1);
		}
	}
}
else
{
	# Read command line options.
	$file = shift @ARGV || pod2usage(2);
	$rows = shift @ARGV || $rows;
	$cols = shift @ARGV || $cols;
	$geom = shift @ARGV || $geom || 'auto';
	$Title= shift @ARGV || $Title || $file;
	
	&process($file);
}

sub create_tmp_dir
{
	my $tmp_dir = $ENV{'TMPDIR'} || $ENV{'TMP'} || '/tmp';
	$tmp_dir .= '/dhyana-' . int(rand(100_000)) . '/';
	mkdir ($tmp_dir);
	
	&debug("Created working directory $tmp_dir.", 2);
	return $tmp_dir;
}

sub get_video_info
{
	$file = shift;
	&debug("Using $path{midentify} to get file information.", 3);

	# Info about the video.
	my @info = split /\n/, `$path{midentify} '$file'`;
	my %info;
	foreach (@info)
	{
		my $k;
		my $v;
		chomp;
		($k, $v) = split /\=/;
		$v =~ s/\\//g;
		$info{$k} = $v;
	}

	return %info;
}

sub get_annotation
{
	my $file = shift;
	my $info = shift;
	
	my $annotation  = sprintf("Size: %.02f MB (%d bytes)\n",
			(-s $file)/(1024*1024),
			(-s $file))
		. sprintf("Length: %d:%02d:%02d\n",
			POSIX::floor($info->{'ID_LENGTH'}/3600),
			POSIX::floor(($info->{'ID_LENGTH'}%3600)/60),
			POSIX::floor($info->{'ID_LENGTH'}%60) 
			)
		. 'Video: '.$info->{'ID_VIDEO_WIDTH'} . 'x' . $info->{'ID_VIDEO_HEIGHT'} 
		. ' (' . $info->{'ID_VIDEO_FORMAT'} . ', '
		. int($info->{'ID_VIDEO_FPS'}) . ' frames/sec, '
		. int($info->{'ID_VIDEO_BITRATE'}/1000) . " kb/sec)\n"
		. 'Audio: '.$info->{'ID_AUDIO_NCH'} . ' chan'
		. ' (' . $info->{'ID_AUDIO_CODEC'} . ', '
		. int($info->{'ID_AUDIO_RATE'}/1000) . ' kHz, '
		. int($info->{'ID_AUDIO_BITRATE'}/1000) . " kb/sec)\n"
		;
		
	# Error calculating stuff, it seems.
	if ( $info->{'ID_VIDEO_FPS'} == 1000)
	{	
		&debug("Potentially unreliable info, so using reduced information set.", 3);
		$annotation  = sprintf("Size: %.02f MB (%d bytes)\n",
				(-s $file)/(1024*1024),
				(-s $file))
			. sprintf("Length: %d:%02d:%02d\n",
				POSIX::floor($info->{'ID_LENGTH'}/3600),
				POSIX::floor(($info->{'ID_LENGTH'}%3600)/60),
				POSIX::floor($info->{'ID_LENGTH'}%60) 
				)
			. 'Video: '.$info->{'ID_VIDEO_WIDTH'} . 'x' . $info->{'ID_VIDEO_HEIGHT'} 
			. ' (' . $info->{'ID_VIDEO_FORMAT'} . ")\n"
			. 'Audio: '.$info->{'ID_AUDIO_NCH'} . ' chan'
			. ' (' . $info->{'ID_AUDIO_CODEC'} . ', '
			. int($info->{'ID_AUDIO_RATE'}/1000) . ' kHz, '
			. int($info->{'ID_AUDIO_BITRATE'}/1000) . " kb/sec)\n"
			;
	}
		
	return $annotation;
}

sub get_capture_timings
{
	&debug("Calculating timings for captures.", 3);
	
	my $file = shift;
	my $info = shift;
	
	my $n_frames = $rows * $cols;
	my $length = $info->{'ID_LENGTH'};
	
	if ( $info->{'ID_VIDEO_FPS'} == 1000)
	{
		debug("Potentially dodgy file. Adjusting timings.", 1);
		$length *= 0.6;
	}
	
	my $part_length = $length / ($n_frames - 1);
	my @times = (1);
	for (my $i=1; $i<$n_frames; $i++)
	{
		push @times, int($i * $part_length)-1;
	}
	
	
	
	return @times;
}

sub process
{
	my $file       = shift;
	
	die "Horrible apostrophe-filled file name.\n" if ($file =~ /\'/);
	
	my $tmp_dir    = &create_tmp_dir;
	my %info       = &get_video_info($file);
	my $annotation = &get_annotation($file, \%info);
	my $real_geom  = $geom;
	
	if ($real_geom eq 'auto')
	{
		if ($info{ID_VIDEO_WIDTH} > 600)
		{
			$real_geom = int($info{ID_VIDEO_WIDTH} / 3) . 'x' .
				int($info{ID_VIDEO_HEIGHT} / 3) . '+4+4';
		}
		elsif ($info{ID_VIDEO_WIDTH} > 315)
		{
			$real_geom = int($info{ID_VIDEO_WIDTH} / 2) . 'x' .
				int($info{ID_VIDEO_HEIGHT} / 2) . '+4+4';
		}
		else
		{
			$real_geom = int($info{ID_VIDEO_WIDTH} / 1) . 'x' .
				int($info{ID_VIDEO_HEIGHT} / 1) . '+4+4';
		}
	}
	
	my @timings = &get_capture_timings($file, \%info);
	
	$i = 1;
	$e = 0;
	
	if ( ($CM =~ /^matt$/i)
	||   ($CM =~ /^auto$/i && $info{ID_VIDEO_FPS}==1000  ))
	{
		&capture_matt($file, $tmp_dir, @timings);
	}
	
	else
	{
		foreach my $t (@timings)
		{
			if ($CM =~ /^auto$/i)
			{
				if ($file =~ /\.(mpe?g|mp4)$/i && -e $path{ffmpeg})
					{ &capture_mpeg($file, $tmp_dir, $t); }
				else
					{ &capture_std($file, $tmp_dir, $t); }
			}
			
			elsif ($CM =~ /^(ff)?mpe?g$/i)
				{ &capture_mpeg($file, $tmp_dir, $t); }
			
			elsif ($CM =~ /^old.?(ff)?mpe?g$/i)
				{ &capture_old_mpeg($file, $tmp_dir, $t); }
			
			else
				{ &capture_std($file, $tmp_dir, $t); }
		}
	}
	
	my $montage   = &create_montage($tmp_dir, $real_geom);
	my $a_montage = &annotate_montage($file, $tmp_dir, $annotation, $montage, $real_geom);
	
	# Finalising
	my $file_out = $file;
	$file_out =~ s/\.[^\.]+$/.jpeg/;
	&debug("Saving final file as '$file_out'.", 1);
	system("$path{convert} -quality 90 '$a_montage' '$file_out'");
	
	&debug("Cleaning up temporary files.", 2);
	system("rm -fr '$tmp_dir'");
	
}

sub capture_mpeg
{
	my $file    = shift;
	my $tmp_dir = shift;
	
	while ($_ = shift)
	{
		&debug("MPEG Capture [slow!]: frame $i, at $_ seconds.", 2);
		my $cmd = $path{ffmpeg} . " -i '$file'"
				. ' -f image2'
				. " -ss $_ -vframes 1"
				. " -y '%08d.png'";
		my $cmd_out = `$cmd 2>&1`;
		system(sprintf("cp '00000001.png' '%s/frame-%06d.png'",
				$tmp_dir, $_));
		system("rm -f '00000001.png'");
		$i++;
	}
	
	return 1;
}

sub capture_old_mpeg
{
	my $file    = shift;
	my $tmp_dir = shift;
	
	while ($_ = shift)
	{
		&debug("MPEG Capture [slow!]: frame $i, at $_ seconds.", 2);
		my $cmd = $path{ffmpeg} . " -i '$file'"
			. ' -f image -img gif'
			. " -ss $_ -vframes 1"
			. " -y '%08d.gif'";
		my $cmd_out = `$cmd 2>&1`;
		system(sprintf("$path{convert} '00000001.gif' '%s/frame-%06d.png'",
			$tmp_dir, $_));
		system("rm -f '00000001.gif'");
		$i++;
	}
		
	return 1;
}

sub capture_std
{
	my $file    = shift;
	my $tmp_dir = shift;
	
	while ($_ = shift)
	{
		&debug("Capture: frame $i, at $_ seconds.", 2);

		my $cmd = $path{mplayer} . ' -really-quiet -nosound'
			. ' -vo png:z=3 -frames 2'
			. " -ss $_ '$file'";
		my $cmd_out = `$cmd 2>&1`;
		if (-e '00000002.png')
			{ system(sprintf("mv '00000002.png' '%s/frame-%06d.png'", $tmp_dir, $_)); }
		else
			{ &debug("Something wrong with capture at $_ seconds", 1); $e++; }
		
		for ( my $f=1 ; $f<=2 ; $f++ )
			{ system("rm -f '0000000$f.png'") if (-e "0000000$f.png"); }
			
		$i++;
	}
	
	return 1;
}

sub capture_matt
{
	use Cwd;
	use File::chdir;
	use File::Spec;

	my $file    = shift;
	my $tmp_dir = shift;
	my @timings = @_;
	my $abs_file;
	my $dir = getcwd;
	
	&debug("Matt's Capture: put your faith in mplayer.", 2);
	
	if (File::Spec->file_name_is_absolute( $file ))
	{
		$abs_file = $file;
	}
	else
	{
		die "Apostrophes \' are bad in dir names\n" if ($dir =~ s/\'/\\'/g);
		my $abs_path = File::Spec->rel2abs( $dir ) ;
		$abs_file = "$dir/$file";
	}

	my $part_length = $timings[2] - $timings[1];
	my $n_frames    = $#timings;
	
	$CWD = $tmp_dir;
	my $cmd = $path{mplayer} . ' -really-quiet -nosound'
		. " -vo png:z=3 -sstep $part_length -frames $n_frames"
		. " '$abs_file'";
	my $cmd_out = `$cmd 2>&1`;
	my $frame_count = `ls $tmp_dir/0*.png|wc -l`;
	$CWD = $dir;
	
	$e = ($rows * $cols) - $frame_count;
	$i = $frame_count + 1;
	
	return 1;
}
sub create_montage 
{
	my $tmp_dir = shift;
	my $real_geom = shift;
	my $frame_count = ($cols * $rows) - $e;
	my $real_rows = POSIX::ceil($frame_count/$cols);
	
	&debug("Creating montage.", 2);
	&debug("Only $frame_count captures, so $cols by $real_rows.", 1) if ($e);
	
	my $cmd = $path{montage}
		. " -geometry '$real_geom'"
		. " -background '$text_bg'"
		. " -fill '$text_colour'"
		. " -tile '".$cols."x".$real_rows."'"
		. " $tmp_dir/frame-*.png $tmp_dir/0*.png $tmp_dir/montage.png";
	my $cmd_out = `$cmd 2>&1`;
	die "Error creating montage!\n" unless (-e "$tmp_dir/montage.png");
	
	return "$tmp_dir/montage.png";
}

sub annotate_montage
{
	my $file  	= shift;
	my $tmp_dir 	= shift;
	my $annotation	= shift;
	my $montage	= shift;
	my $real_geom	= shift;

	&debug("Annotating montage '$montage'.", 2);
	
	my $bord = $real_geom;
	$bord =~ s/^\d+x\d+\+//;
	$bord =~ s/\+/x/;
	
	my $cmd = $path{convert}
		. " -fill '$text_colour'"
		. " -background '$text_bg'"
		. " -font '\@$text_font'"
		. " -pointsize '$text_size'"
	# 	. " -gravity North"
		. " -trim +repage"
		. " -bordercolor '$text_bg'"
		. " -border $bord"
		. " text:- '$tmp_dir/annotation.png'";
	open(CONVERT, "|$cmd");
	if ($file eq $Title)
	{
		print CONVERT "$annotation\n";
	}
	else
	{
		print CONVERT "$file\n$annotation\n";
	}
	close(CONVERT);
	my $cmd = $path{convert}
		. " -fill '$text2_colour'"
		. " -background '$text_bg'"
		. " -font '\@$text2_font'"
		. " -pointsize '$text2_size'"
	# 	. " -gravity North"
		. " -trim +repage"
		. " -bordercolor '$text_bg'"
		. " -border $bord"
		. " text:- '$tmp_dir/filename.png'";

	open(CONVERT, "|$cmd");
	print CONVERT "$Title\n";
	close(CONVERT);
	system("$path{convert} -bordercolor '$text_bg' -border $bord -background '$text_bg' '$tmp_dir/filename.png' '$tmp_dir/annotation.png' '$tmp_dir/montage.png' -append '$tmp_dir/final.png'");
	
	return "$tmp_dir/final.png";
}	


# Debugging function.
sub debug
{
	my $x		= shift;
	my $level	= shift;
	
	print "[$level] $x\n" unless ($level > $verbosity);
}


__END__

=head1 NAME

dhyana.pl - Sequential video screen captures on Linux

=head1 SYNOPSIS

  dhyana.pl [options] file [cols [rows [geometry [title]]]]
  dhyana.pl --multi [options] file [file ...]

  Options:
    --help                   brief help message
    --man                    full documentation
    --version                print version number
    --verbose, -v            increase verbosity
    --quiet                  no status output
    --path TOOL=PATH         set path for external tool
    
  Capture options:
    --cols=X, -c X           columns of images to capture (default 4)
    --rows=Y, -r Y           rows of images to capture (default 6)
    --geometry=G, -g G       geometry of thumbnails (default 'auto')
    --title=T, -t T          title for thumbnails (filename default)
    --capture-mode=M, -C M   capture technique (default 'auto')
    
  Style options:
    --background             background colour (e.g. 'green', '#00ff00')
    --font-family            path to TTF file for text
    --font-size              size of text in pixels
    --colour, --color        colour for text
    --heading-font-family    path to TTF file for heading
    --heading-font-size      size of heading in pixels
    --heading-colour         colour for heading
    
=head1 OPTIONS

Dhyana.pl processes video files and outputs an image file containing a
summary of the video in the form of thumbnails taken at regular intervals.

It includes annotation at the top of the image with video file details such
as file name, codecs used, bitrates, length, file size and so forth.

For more details, see http://tobyinkster.co.uk/tag/dhyana/.

=over 8

=item B<--options>

To save time typing frequently used options, you may specify a group of
options in a text file. The text file syntax is the same as the command
line parameters, including dashes, but may extend over multiple lines, and
can include comment lines starting with a hash.

    dhyana.pl --options=myopts.txt file.mpeg
    
A shorthand for this exists:

    dhyana.pl @myopts.txt file.mpeg

Dhyana.pl also looks for additional options in the files './.dhyanarc' and
'~/.dhyanarc' if they exist.

=item B<--verbose>, B<--quiet> 

By default, only warning/debugging messages with priority "1" are shown.
You may increase dhyana.pl's verbosity using the --verbose option. This
option may be used multiple times to increase it further. The --quiet
option mutes all output from dhyana.pl except fatal errors.

=item B<--path>

Dhyana.pl uses several external programs:

    * mplayer
    * midentify [part of mplayer]
    * ffmpeg
    * convert [part of ImageMagick]
    * montage [part of ImageMagick]
    
By default it looks for these programs in /usr/bin/. You may specify
alternative locations using the path option. e.g.

    dhyana.pl --path ffmpeg=/opt/media/bin/ffmpeg file.mpeg

You may specify multiple paths by using the --path option repeatedly:

    dhyana.pl --path convert=/opt/magick/bin/convert \
      --path mogrify=/opt/magick/bin/mogrify file.mpeg
      
It is suggested that such paths are specified in '.dhyanarc' files to
save repeatedly typing them.

=item B<--capture-mode>

Dhyana.pl calls external programs such as mplayer and ffmpeg (see also
the --path option) to take the screen captures. The capture mode allows
you to choose which external programs to use, and how. Available modes
are:

    * auto (choose best based on input file codecs)
    * standard (frame-by-frame using mplayer)
    * ffmpeg (PNG export using newer ffmpeg)
    * old-ffmpeg (GIF export using older ffmpeg)
    * matt (all-at-once using mplayer)
    
Unless you have a particular reason to choose otherwise, it's normally
fine to use the default capture mode ('auto').

=item B<--cols>, B<--rows>

Lays out the thumbnails in an X*Y table. By default, 4 columns and 6 rows
are used. 

Occasionally, certain frame captures fail, in which case the resulting
table may be smaller than requested. In this case, rows will be removed,
not columns.

=item B<--geometry>

Specified in the format XxY+W+H where X and Y are the width and height of
image thumbnails to take, and W and H are the width and height of the
margin to include around them. 

These dimensions are passed directly to ImageMagick, so dhyana.pl follows
ImageMagick's behaviour with regard to them. Namely, images are scaled
by default preserving aspect ratio. If you want to warp the images, you
may prefix the geometry with an exclamation mark (!) to force the images
to be scaled to your exact dimensions.

The default geometry is 'auto' which automatically chooses an appropriate
geometry based on the video resolution.

=item B<--title>

Sets the title to display at the top of the image. The title can be in a
different font and colour from the rest of the annotation. If no title is
specified, the file name is used. If a title other than the file name is
specified, the file name will be included in the body of the annotation.

=item B<Style Options>

These are not yet documented.

=back 
