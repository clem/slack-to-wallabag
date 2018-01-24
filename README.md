# Slack to Wallabag

This project is a bridge between [Slack](https://slack.com/) and [Wallabag](https://wallabag.org/). 
It allows you to crawl all posted links on your favorite Slack space, to a given Wallabag server.

## Install and configuration

### Install

This project is based on [Symfony 4](https://symfony.com/) and requires a simple Symfony installation.

#### Local install on UNIX

You'll need [composer](https://getcomposer.org/) to install Symfony

```bash
# Clone and go to cloned project folder
$ git clone git@github.com:clem/slack-to-wallabag.git
$ cd slack-to-wallabag

# Do a composer install
$ composer install

# Create database and create schema
$ php bin/console doctrine:database:create
$ php bin/console doctrine:schema:update -f
```

... And that's it!

### List of required Slack scopes

To work, this app requires a (small) list of Slack scopes:

- `channels:history`: Access user’s public channels
- `channels:read`: Access information about user’s public channels
- `links:read`: View some URLs in messages 
- `users:read`: Access your workspace’s profile information

## Documentation

### Available commands

#### Crawl Slack

This command is launched with `php bin/console stw:crawl:slack` and has no parameters and no options.
It will retrieve users and public messages lists, using the main configuration file.

## Todo

- Add documentation on 'how to deploy on Heroku'
- Add documentation on all commands
