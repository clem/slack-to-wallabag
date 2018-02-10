[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/clem/slack-to-wallabag/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/clem/slack-to-wallabag/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/clem/slack-to-wallabag/badges/build.png?b=master)](https://scrutinizer-ci.com/g/clem/slack-to-wallabag/build-status/master)

# Slack to Wallabag

![Slack to Wallabag](/_docs/slw-logo.png)

This project is a bridge between [Slack](https://slack.com/) and [Wallabag](https://wallabag.org/). 
It imports/crawls all the posted links on your favorite Slack space, to a given Wallabag server.

## Configuration

Here are the different configurations variables used in the `.env.dist` file.

### General settings

- `APP_ENV`: Application environment (should be 'prod', unless you want to develop new features or fix bugs)
- `APP_SECRET`: Your application secret
- `DATABASE_URL`: Your database url. By default, it's based on postgresql, but you can also use MySQL
- `APP_LOCALE`: Application locale. 'en' and 'fr' langs are supported

### Slack-to-Wallabag

- `APP_EXCLUDED_CHANNELS`: List of channels (separated by comma - 'general,random') that will NOT be crawled
- `APP_IMPORT_ONLY_USER_LINKS`: Import only one user's links (and not all users links)
- `HOME_SLACK_LINK`: Link to Slack homepage (could be any link, we don't use this link to crawl data)
- `HOME_WALLABAG_LINK`: Link to Wallabag homepage  (could be any link, we don't use this link to export data)
- `HOME_DISPLAYED_DAYS`: Number of displayed days on homepage chart

### Slack settings

- `SLACK_API_BASE_URL`: Slack API base URL (should be *https://slack.com*)
- `SLACK_OAUTH_ACCESS_TOKEN`: Your OAuth access token to Slack API

To create your Slack access token, just go to [legacy tokens](https://api.slack.com/custom-integrations/legacy-tokens)
page.

#### List of required Slack scopes

To crawl Slack, this app requires a (small) list of Slack scopes:

- `channels:history`: Access user’s public channels
- `channels:read`: Access information about user’s public channels
- `links:read`: View some URLs in messages 
- `users:read`: Access your workspace’s profile information

Nothing more as we don't need to write messages.

### Wallabag Settings

- `WALLABAG_API_URL`: The Wallabag API url (like *http://yourwallabag.app.com/api/*)
- `WALLABAG_CLIENT_ID`: Wallabag generated client ID
- `WALLABAG_CLIENT_SECRET`: Wallabag generated client secret
- `WALLABAG_USER_USERNAME`: Your Wallabag's username
- `WALLABAG_USER_PASSWORD`: Your Wallabag's password

To create your Wallabag client id and secret, go to the developer menu in your Wallabag application
(like *http://yourwallabag.app.com/developer*)

### Twitter settings

If you want to crawl imported Twitter links, you have to set twitter configuration.
You create your access token (and secret), you have to [create a Twitter app](https://apps.twitter.com/).
Then, go to *Keys and Access Tokens* tab to get your Applications Settings and Access Token. 
Please note that this app only requires a `read-only` access level.

- `TWITTER_OAUTH_ACCESS_TOKEN`: OAuth access token
- `TWITTER_OAUTH_ACCESS_TOKEN_SECRET`: OAuth access token secret
- `TWITTER_CONSUMER_KEY`: Your Twitter consumer key
- `TWITTER_CONSUMER_SECRET`: Your Twitter consumer secret

## Install

This version (with current configuration) is Heroku-oriented, 
but you can also install it on your local or distant web server.

### Deploy on Heroku

To deploy the project on Heroku, you will need a dedicated application.
If you don't know how to do it, just
[follow the documentation](https://devcenter.heroku.com/articles/getting-started-with-php#introduction).

Once created, open your favorite terminal app and here we go... 

```bash
# Clone and go to cloned project folder
$ git clone git@github.com:clem/slack-to-wallabag.git
$ cd slack-to-wallabag

# Configure Heroku for PHP
# Note that you will need to be logged to Heroku command line
$ heroku buildpacks:set heroku/php

# Add PostgreSQL to your app
$ heroku addons:create heroku-postgresql:hobby-dev

# Duplicate and edit configuration file with your custom settings
# Check that `DATABASE_URL` matches the PostgreSQL one
$ cp .env.dist .env
$ sublime .env

# Run env-to-heroku.sh to push all the configuration variables to Heroku
# Yeah, it's simpler and quicker than copy/paste them
$ bash env-to-heroku.sh

# Deploy your project
$ git push heroku master

# Test that the app works
$ heroku open

# When it's done, you can add a scheduler to auto-crawl and export links
# Install and open scheduler
$ heroku addons:create scheduler:standard
$ heroku addons:open scheduler
```

You can now add the following jobs:

- `php bin/console stw:crawl:slack`
- `php bin/console stw:twitter:update-twitter-links`
- `php bin/console stw:export:wallabag`

Like this: 

![Heroku CRONs](/_docs/heroku-jobs.png).

And that's it!

### Install on UNIX local host or server

This project is based on [Symfony 4](https://symfony.com/) and requires a simple Symfony installation.
You'll need [composer](https://getcomposer.org/) to install Symfony

```bash
# Clone and go to cloned project folder
$ git clone git@github.com:clem/slack-to-wallabag.git
$ cd slack-to-wallabag

# Duplicate and edit configuration file with your custom settings
$ cp .env.dist .env
$ sublime .env

# Do a composer install
$ composer install

# Create database and migrate schema
$ php bin/console doctrine:database:create
$ php bin/console doctrine:migrations:migrate

# And run server
$ php bin/console server:start

# You can now launch the following commands, add them to your crontab
# Or go to the homepage to see what is loaded... :)
$ php bin/console stw:crawl:slack # To crawl Slack users and links
$ php bin/console stw:twitter:update-twitter-links # To update Twitter links
$ php bin/console stw:export:wallabag # To export links to Wallabag
```

## Documentation

### Available commands

Slack to Wallabag has commands to:

- Crawl Slack: `php bin/console stw:crawl:slack`
- Update Twitter links: `php bin/console stw:twitter:update-twitter-links`
- Export to Wallabag: `php bin/console stw:export:wallabag`

But you can also import JSON/ZIP files from Slack export:

- Import a full Slack export (in ZIP format): `php bin/console stw:import:full-slack-export`
- Import all JSONs of Slack Messages from a given folder: `php bin/console stw:import:slack-links-folder`
- Import a given JSON of Slack messages: `php bin/console stw:import:slack-links`
- Import a given JSON of Slack Users: `php bin/console stw:import:slack-users`

#### Crawl Slack

This command is launched with `php bin/console stw:crawl:slack` and has no parameters and no options.
It will retrieve users and public messages lists, using the main configuration file.

#### Export To Wallabag

This command is launched with `php bin/console stw:export:wallabag` and has no parameters and no options.
It will export all not-exported links to Wallabag, with the link's channel and tags as tags list.

#### Update Twitter Links 

This command is launched with `php bin/console stw:twitter:update-twitter-links` and has no parameters and no options.
It will crawl all the uncrawled Twitter links and will update them with tweet info.

#### Import Slack Full Export

This command is launched with `php bin/console stw:import:full-slack-export`.
It will unzip and import users and links from the entire archive.
It has the following parameters and options:

- **parameter** `archive` - Path to ZIP archive to import
- *option* `folder` - Extract ZIP archive to this folder
- *option* `excluded-channels` - List of channels to exclude from import (list as string separated with commas)
- *option* `only-user` - Only import given user's links
- *option* `exclude-app-channels` - Don't import app excluded channels

#### Import Slack Links Folder

This command is launched with `php bin/console stw:import:slack-links-folder`.
It will open all JSON files and import all links from the given folder.
It has the following parameter:

- **parameter** `folder` - Path to JSON files folder to import

#### Import Slack Links

This command is launched with `php bin/console stw:import:slack-links`.
It will open a JSON file and import all contained links.
It has the following parameter:

- **parameter** `file` - Path to JSON file to import

#### Import Slack Users 

This command is launched with `php bin/console stw:import:slack-links`.
It will open a JSON file and import all contained users.
It has the following parameter:

- **parameter** `file` - Path to JSON file to import
