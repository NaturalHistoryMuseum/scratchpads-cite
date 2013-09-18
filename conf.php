<?php
# Configuration.
#
# Paths are relative to application root unless otherwise noted.
#
# The Application class is availabe (for constants), though should
# not be assumed to have been otherwise initialised.

# Base URL to use when creating links to the generated files
$conf['base_url'] = 'http://cite.scratchpads.eu';

# IPs that are allowed to use this service
$conf['allowed_ips'] = array('127.0.0.1');

# Log configuration. Available log levels are LOG_NONE, LOG_ERROR and LOG_INFO
$conf['log_level'] = Application::LOG_INFO; 
$conf['log_file'] = 'log/log.txt';

# Path to the phantomjs script used to generate the files.
# Script is invoked with '-url <url to snapshot> -post <post data to submit> -dest <destination to store file>
$conf['generate_script'] = 'lib/scripts/generate.js';

# Relative path to preview folder. Don't name this 'preview', as it will clash with
# the URL used to generate previews.
$conf['preview_folder'] = 'prev';

# Path to phantomjs executable (absolute path)
$conf['phantomjs_path'] = '/usr/local/bin/phantomjs';

# Command to set PDF meta data (absolute path)
# This is invoked with '-Title=<title> -Author=<author> <filename>'
$conf['exiftool'] = '/usr/bin/exiftool';

# Database
$conf['database'] = array(
  'host' => '',
  'user' => '',
  'password' => '',
  'database' => ''
);
