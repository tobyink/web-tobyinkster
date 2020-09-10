all: load build

merge-dirs:
	./build/merge-dirs.pl

build-feed: merge-dirs
	./build/compile-feed.pl

build-atom: build-feed
	./build/atom-to-bare.pl

#build-code:
#	./build/code-page.pl

build-html: build-atom
	./build/bare-to-html.pl

build-rdf: build-html
	./build/html-to-rdf.pl

build: build-atom build-html build-rdf

load-bpo:
	./loaders/bpo.pl

load-ilovecbeebies:
	./loaders/ilovecbeebies.pl

load: load-bpo
