# WP Job Manager Recruiter Plugin

This is a custom-built add-on plugin for the [WP Job Manager Plugin](https://wpjobmanager.com/). The work contained in this plugin is based on the format and logic used by the [WP Job Manager](https://wpjobmanager.com/) and [WP Job Manager - Resume Manager](https://wpjobmanager.com/add-ons/resume-manager/) plugins.

## Technical Requirements
- Latest version of [Apache Server](https://httpd.apache.org/) with "mod_rewrite" enabled or [Nginx](https://codex.wordpress.org/Nginx)
- [MySQL](https://www.mysql.com/) (v5.6+) OR [MariaDB](https://mariadb.org/) (v10+)
- [PHP](http://www.php.net/) (v7.0+)
- [WordPress](https://wordpress.org/) (v4.6+)

## Plugin Dependancies
- [Less Compiler](https://www.freeformatter.com/javascript-minifier.html)
- [JavaScript Minifer](https://www.freeformatter.com/less-compiler.html)

## Plugin Dependancies
- [WP Job Manager](https://wpjobmanager.com/) (v1.26+)
- [WP Job Manager - Applications](https://wpjobmanager.com/add-ons/applications/) (v2.2+)
- [WP Job Manager - Resume Manager](https://wpjobmanager.com/add-ons/resume-manager/) (v1.15+)

## Plugin Structure
This plugin was built to mirror the structure of the [WP Job Manager](https://wpjobmanager.com/) and [WP Job Manager - Resume Manager](https://wpjobmanager.com/add-ons/resume-manager/) plugins. As such, all logic is contained within the `includes` folder and broken out into files resmebling what you would find in both reference plugins.

### CSS and JavaScript
This plugin [less](http://lesscss.org/) to generate CSS files so you'll need a tool that compiles less files or you can use the online compiler listed above.

This plugin uses minified JavaScript files so you'll need a tool that can minify JavaScript files or you can use the online minifier listed above.

### PHP
The `includes` folder contains several PHP classes which contain the majority of the logic that controls the theme. Each class controls one specific component of the application (e.g. `class-wp-recruiter-ajax.php` controls all AJAX interaction logic. The `admin` folder contains all logic belonging to the WordPress back-end. The `forms` folder contains all logic belonging to the various submission forms.

### Updates
This plugin was built specifically for JobTag so there will be no external updates available. This plugin was built for the version of [WP Job Manager](https://wpjobmanager.com/) that was available at the time. This plugin will need to be adapated as the WP Job Manager plugin updates and changes.

## Configuration
This plugin adds settings to the WordPress Admin under `Companies > Settings`. These settings are configured in `includes/admin/class-wp-recruiter-settings.php`.

This plugin adds the "company" post type and it's related metadata in `includes/admin/class-wp-recruiter-cpt.php`, and `includes/admin/class-wp-recruiter-writepanels.php`.

This plugin requires certain logic to be added immediately after the plugin is initialized, like adding/updating user roles; that logic can be found in `includes/admin/class-wp-recruiter-setup.php`.
