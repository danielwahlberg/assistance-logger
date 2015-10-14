# Assistance Logger
Assistance Logger is a mobile adapted web application for keeping track of common tasks for personal assistants to disabled persons, such as feeding and medication

## Current features
 * Set up and log regular medication and doses on given times
 * Log "when needed" medication
 * Log feeding in food categories and directly see sum of given food
 * View statistics of historical given food per category 

## How to use
 * Set up common php site, allowing .htaccess overwrites
 * Set up MySQL database using the database structure found in Mysql workbench file `assistanceLogger.mwb`
   * Database settings are stored in `api/v1/db.php`; mockup settings are stored in the checked in version; replace with your own database settings 
 * Currently, no dependency management is used so all (both..) libs needed are checked in under `assets`
 * Create user accounts and password in the `user` database table, using PHP's password hash function or `api/v1/login/generatePassword/[your password]`
 
## Used frameworks
 * Slim framework for PHP
 * Angular
