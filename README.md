# WP-CLI Extend

This plugin adds some extended functionality to [WP-CLI](http://wp-cli.org/).

## Rebuilding Auth Keys/Salts

A very simple usage of this plugin is to get new auth keys/salts. It is a good idea to change these from time to time and this plugin makes that super easy.

```
wp extend resalt;
```

That single line, entered into the command line, will rebuild all the auth keys/salts for a site.

Please Note: users will be logged out!