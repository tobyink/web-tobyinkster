all: load build

merge-dirs:
	./build/merge-dirs.pl

build-feed: merge-dirs
	./build/compile-feed.pl

build-atom: build-feed
	./build/atom-to-bare.pl

build: build-atom
	./build/bare-to-html.pl

load-bpo:
	./loaders/bpo.pl

load-ilovecbeebies:
	./loaders/ilovecbeebies.pl

load: load-bpo load-ilovecbeebies