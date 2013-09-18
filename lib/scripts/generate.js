/**
 * PhantomJS script to generate a snapshot of a given URL.
 * 
 * Expected usage is:
 * generate_pdf.js -url <url> -dest <destinaion> [-post <post-data>]
 * 
 * This will exit silently (with an error code) on errors
 */

// Setup and parse arguments.
var utils = require('./utils.js');
var system = require('system');
var option_definitions = {
  url: {
    title: 'URL to snapshot',
    required: true
  },
  dest: {
    title: 'Filename to save',
    required: true
  },
  post : {
    title: 'Post data to submit on snapshot',
    required: false
  }
};
var options = {};
try {
  options = utils.optionParser(option_definitions, system.args.slice(1));
} catch(err) {
  console.log(err);
  phantom.exit(1);
}
options.mode = options.dest.match(/\.pdf$/) ? 'pdf' : 'png';

// Prepage the page
var page = require('webpage').create();
page.viewportSize = { width: 1024, height: 768 };
// Callback when the page is loaded
var callback = function (status) {
  console.log("Page opened with status " + status.toString());
  if (status !== 'success') {
    phantom.exit(1);
  } else {
    window.setTimeout(function () {
      if (options.mode == 'pdf') {
        page.evaluate(function() {
          jQuery('<meta name="author" content="carrot">').appendTo('head');
        });
        var height = page.evaluate(function() {
          return document.height
        });
        page.paperSize = {width: '21cm', height: Math.ceil(2+21*height/1024).toString() + "cm", margin: '1cm'}
      }
      console.log("Rendering to " + options.dest.toString());
      page.render(options.dest);
      phantom.exit(0);
   }, 500);
 }
};
// Start the page load
console.log("Opening the page at " + options.url.toString());
if (options.post === null) {
  page.open(options.url, callback);
} else {
  page.open(options.url, 'post', options.post, callback);
}
