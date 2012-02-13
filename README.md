# Cakebox: A Dropbox API Wrapper for CakePHP

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

    var $dropbox = array(
        'datasource' => 'Dropbox.DropboxSource',
        'key' => 'CONSUMER KEY HERE',
        'secret' => 'CONSUMER SECRET HERE',
    );`

## Usage

Coming soon. For now check the test cases.

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