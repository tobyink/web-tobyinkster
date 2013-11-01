#!/usr/bin/perl

use strict;
use POSIX;

# Paths to stuff.
my $path_convert   = '/usr/bin/convert';
my $path_ffmpeg    = '/usr/bin/ffmpeg';
my $path_midentify = '/usr/bin/midentify';
my $path_montage   = '/usr/bin/montage';
my $path_mplayer   = '/usr/bin/mplayer';

# Silly tests.
die "This program cannot run if 00000001.png exists.\n" if (-e '00000001.png');
die "This program cannot run if 00000002.png exists.\n" if (-e '00000002.png');
die "This program cannot run if 00000001.gif exists.\n" if (-e '00000001.gif');

# Read command line options.
my $file = shift @ARGV || die "Specify input file.\n";
my $rows = shift @ARGV || 6;
my $cols = shift @ARGV || 4;
my $geom = shift @ARGV || '240x180+10+10';
my $is_mpeg = 0;
$is_mpeg = 1 if ($file =~ /\.mpe?g$/i && -e $path_ffmpeg);

# Other settings.
my $text_font   = '@/usr/share/fonts/msttcorefonts/trebuc.ttf';
my $text_colour = '#0080';
my $text_bg     = 'AliceBlue';
my $text_size   = '18';

# Create temporary directory.
my $tmp_dir = $ENV{'TMPDIR'} || $ENV{'TMP'} || '/tmp';
$tmp_dir .= '/dhyana-' . int(rand(100_000)) . '/';
mkdir ($tmp_dir);

# Info about the video.
my @info = split /\n/, `$path_midentify '$file'`;
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
@info = undef;

# Create image annotation
my $annotation  = sprintf("Size: %.02f MB (%d bytes)\n",
			(-s $file)/(1024*1024),
			(-s $file))
		. sprintf("Length: %d:%02d:%02d\n",
			POSIX::floor($info{'ID_LENGTH'}/3600),
			POSIX::floor(($info{'ID_LENGTH'}%3600)/60),
			POSIX::floor($info{'ID_LENGTH'}%60) 
			)
		. 'Video: '.$info{'ID_VIDEO_WIDTH'} . 'x' . $info{'ID_VIDEO_HEIGHT'} 
		. ' (' . $info{'ID_VIDEO_FORMAT'} . ', '
		. int($info{'ID_VIDEO_FPS'}) . ' frames/sec, '
		. int($info{'ID_VIDEO_BITRATE'}/1000) . " kb/sec)\n"
		. 'Audio: '.$info{'ID_AUDIO_NCH'} . ' chan'
		. ' (' . $info{'ID_AUDIO_CODEC'} . ', '
		. int($info{'ID_AUDIO_RATE'}/1000) . ' kHz, '
		. int($info{'ID_AUDIO_BITRATE'}/1000) . " kb/sec)\n"
		;

# Find out how long the video is and thus when we need to capture frames.
my $n_frames = $rows * $cols;
my $length = $info{'ID_LENGTH'};
my $part_length = $length / ($n_frames - 1);
my @times = (1);
for (my $i=1; $i<$n_frames; $i++)
{
	push @times, int($i * $part_length)-1;
}

# For each frame, capture it and save it to the temp directory.
my $i = 1;
foreach (@times)
{
	&debug("Capturing frame $i, at $_ seconds.");
	if ($is_mpeg)
	{
		&debug("Using FFMPEG instead of MPLAYER. (Slow.)");
		my $cmd = $path_ffmpeg . " -i '$file'"
			. ' -f image -img gif'
			. " -ss $_ -vframes 1"
			. " -y '%08d.gif'";
		my $cmd_out = `$cmd 2>&1`;
		system(sprintf("$path_convert '00000001.gif' '%s/frame-%06d.png'",
			$tmp_dir, $_));
		system("rm -f '00000001.gif'");
	}
	else
	{
		my $cmd = $path_mplayer . ' -really-quiet -nosound'
			. ' -vo png:z=3 -frames 2'
			. " -ss $_ '$file'";
		my $cmd_out = `$cmd 2>&1`;
		system(sprintf("mv '00000002.png' '%s/frame-%06d.png'",
			$tmp_dir, $_));
		system("rm -f '00000001.png' '00000002.png'");
	}
	$i++;
}

# Creating montage.
&debug("Creating montage.");
my $cmd = $path_montage
	. " -geometry '$geom'"
	. " -background '$text_bg'"
	. " -fill '$text_colour'"
	. " -tile '".$cols."x".$rows."'"
	. " $tmp_dir/frame-*.png $tmp_dir/montage.png";
my $cmd_out = `$cmd 2>&1`;
die "Error creating montage!\n" unless (-e "$tmp_dir/montage.png");

# Annotating montage
&debug("Annotating montage.");
my $cmd = $path_convert
	. " -background '$text_bg'"
	. " -fill '$text_colour'"
	. " -font '$text_font'"
	. " -pointsize '$text_size'"
	. " -gravity North"
	. " -trim +repage"
	. " -bordercolor '$text_bg'"
	. " -border 20x20"
	. " text:- '$tmp_dir/annotation.png'";
open(CONVERT, "|$cmd");
print CONVERT "$file\n$annotation\n";
close(CONVERT);
system("$path_convert -background '$text_bg' '$tmp_dir/annotation.png' '$tmp_dir/montage.png' -append '$tmp_dir/final.png'");

# Finalising
&debug("Saving final file.");
my $file_out = $file;
$file_out =~ s/\.[^\.]+$/.jpeg/;
system("$path_convert -quality 90 '$tmp_dir/final.png' '$file_out'");
&debug("Cleaning up temporary files.");
system("rm -fr '$tmp_dir'");

# Debugging function.
sub debug
{
	my $x = shift @_;
	print "debug: $x\n";
}
