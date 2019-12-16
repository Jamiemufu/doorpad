# README

Simple Site Template is a barebones website template built within the Whiskey framework. It provides a useful starting point for ITG web sites and applications.

### Where is the project hosted?

This section should advise which server (including its name and IP) the project will be hosted on.

### How do I get set up?

* Ensure that Node.js and all of the required packages in gulpfile.js are installed;
* Whilst in the public_html directory, run 'composer install' from the terminal;
* Create a .env file based upon the .env.example file in the application's root directory and populate any missing values;
* Set up a cron job as described at: https://whsky.uk/docs/structure/schedule;
* Visit the application in a web browser.

### How do I compile LESS and minify JavaScript files?
Simply run the command 'gulp' from the terminal for the following to occur:

* public_html/src/css/styles.less compiled and minified to public_html/_public/css/styles.min.css
* public_html/src/js/scripts.js compiled and minified to public_html/_public/js/scripts.min.js

Gulp will watch the source files for changes and recompile/minify them until the 'gulp' terminal process is aborted.