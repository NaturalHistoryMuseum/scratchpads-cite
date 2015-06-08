=
= cite.scratchpads.eu
=

1. Changelog
============

Version 0.1: Initial release

2. Overview
===========

This is a simple server application that is used to generate and store
snapshots of a given page, to be used for citing a page at a particular
time.

The server provides:
- Generation of snapshot previews as PNGs that can be embeded easily ;
- Generation of PDFs snapshots for permanent storage ;
- Generation of Bibtex, RIS and Endnote XML files for citation ;
- List of all stored snapshots.

The current implementation relies on phantomJS to generate the snapshots and
exiftool to set PDF metadata.

3. Usage
========

  3.1 General
  -----------

- Edit conf.php as required ;
- View index page at "/" for list of held snapshots ;

  3.2 Create snapshot 
  -------------------

Send a request to "/preview" to get a preview snapshot, and to "/generate" to generate
and store a snapshot.

Parameters are:
- url: URL of the page to snapshot ;
- title: Title of the page to snapshot (used for citation) ;
- site: Human name of the site to snapshot ;
- author: Single string with author name(s) to cite ;
- author_data: array defining 'initial', 'current' and 'others'
  (for initial authors, authors of the current revision and other authors
   respectively). Each is an array defining 'lastname', 'firstname', 'title'
   and 'institution' ;
- date: Single string with the date to cite ;
- date_data: array of timestamps ;
- post: POST data to send with the request when doing the snapshot.

The requests returns a json value, defining an array of file type to file URL. In preview
mode the only entry in 'png' with a link to the preview file. In generate mode there are
entried for the pdf file ('pdf'), as well as 'bib', 'ris' and 'xml' for the Bibtex,
RIS and Endnote files respectively. 

4. Development
==============

The server is build in PHP, using a very simple custom made MVC framework.
