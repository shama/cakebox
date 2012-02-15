# Cakebox: A Dropbox API Plugin for CakePHP 2

## Requirements

PHP5+, CakePHP 2+, Dropbox account (w/ API credentials)

## Installing

1. [Sign up](https://www.dropbox.com/developers/apps) for a Dropbox app.
2. Extract the contents of this repo into *app/Plugin/Dropbox/* or use
[git clone](http://www.kernel.org/pub/software/scm/git/docs/git-clone.html) or
[git submodule](http://www.kernel.org/pub/software/scm/git/docs/git-submodule.html)
in your app/Plugin folder:

        git clone git://github.com/shama/cakebox.git Dropbox

3. Enable the plugin in your `app/Config/bootstrap.php` with:

        CakePlugin::loadAll();
        // OR
        CakePlugin::load('Dropbox');

4. Copy the following lines into `app/Config/database.php` and add your consumer
key and secret:

        public $dropbox = array(
            'datasource' => 'Dropbox.DropboxSource',
            'consumer_key' => 'CONSUMER KEY HERE',
            'consumer_secret' => 'CONSUMER SECRET HERE',
        );

## Usage

The easiest way to use this plugin is to include the `Dropbox.DropboxApi`
component in your controller:

    class PagesController extends AppController {
        public $components = array('Dropbox.DropboxApi');
    }

### Authorize with Dropbox

Before you can use the Dropbox API you need to authorize a dropbox account to
use your app. This is done through OAuth and the included component can help:

    class PagesController extends AppController {
        public $components = array('Dropbox.DropboxApi');

        public function authorize() {
            $this->DropboxApi->authorize();
        }
    }

This will take you through the redirect steps to authorize your Dropbox account
with your app.

## Issues

Please report any issues you have with the plugin to the
[issue tracker](http://github.com/shama/cakebox/issues) on github.

## Thanks!

- JR Conlin for his simple OAuth lib: [https://github.com/jrconlin/oauthsimple]

## License

Cakebox is offered under an [MIT license](http://www.opensource.org/licenses/mit-license.php).

## Copyright

2012 Kyle Robinson Young, [dontkry.com](http://dontkry.com)

If you found this release useful please let the author know! Follow on [Twitter](http://twitter.com/kyletyoung)