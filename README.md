# Cakebox: A Dropbox API Plugin for CakePHP 2

## Requirements

PHP5+, CakePHP 2+, Dropbox account (w/ API credentials)

## Installing

1. [Sign up](https://www.dropbox.com/developers/apps) for a Dropbox app.
2. Extract the contents of this repo into *app/Plugin/Dropbox/* or use
[git clone](http://www.kernel.org/pub/software/scm/git/docs/git-clone.html) or
[git submodule](http://www.kernel.org/pub/software/scm/git/docs/git-submodule.html)
in your app/Plugin folder:

```
git clone git://github.com/shama/cakebox.git Dropbox
```

3. Enable the plugin in your `app/Config/bootstrap.php` with:

``` php
<?php
CakePlugin::loadAll();
// OR
CakePlugin::load('Dropbox');
```

4. Copy the following lines into `app/Config/database.php` and add your consumer
key and secret:

``` php
<?php
public $dropbox = array(
  'datasource' => 'Dropbox.DropboxSource',
  'consumer_key' => 'CONSUMER KEY HERE',
  'consumer_secret' => 'CONSUMER SECRET HERE',
);
```

## Usage

The easiest way to use this plugin is to include the `Dropbox.DropboxApi`
component in your controller:

``` php
<?php
public $components = array('Dropbox.DropboxApi');
```

### Authorize with Dropbox

Before you can use the Dropbox API you need to authorize a Dropbox account to
use your app. This is done through OAuth and the included component can help.
Add a new method to your controller and call the `authorize()` method:

``` php
<?php
public function authorize_with_dropbox() {
  $this->DropboxApi->authorize();
}
```

This will take you through the redirect steps to authorize your Dropbox account
with your app via OAuth.

By default the tokens will be only saved in a session. You'll want to save them
longer. The recommended way is to use the `AuthComponent` by including
`Auth` in your controller's components. Then create the fields `dropbox_token`
and `dropbox_token_secret` in your users table. Then the `DropboxApi` component
will automatically save the tokens for the logged in user.

If you don't want to use the default field names you can set them when including
the component:

``` php
<?php
public $components = array(
  'Dropbox.DropboxApi' => array(
    'fields' => array(
      'dropbox_token' => 'my_token_field',
      'dropbox_token_secret' => 'my_secret_field',
    ),
  ),
);
```

Or if you want to use another Dropbox Model or User Model:

``` php
<?php
public $components = array(
  'Dropbox.DropboxApi' => array(
    'userModel' => 'Member',
    'dropboxModel' => 'MyDropbox',
  ),
);
```

Have a look in `Controller/Component/DropboxApiComponent.php` for all the
settings.

### The Dropbox API

After you have authorized your app and have your tokens; you're ready to use the
Dropbox API:

``` php
<?php
// Get a list of files from a root Dropbox folder
$files = $this->DropboxApi->ls();

// Get files within a path
$files = $this->DropboxApi->ls('Path/In/Dropbox');

// Limit the amount of files returned
$files = $this->DropboxApi->ls('Path/In/Dropbox', array('file_limit' => 10));

// Alternative syntax
$files = $this->DropboxApi->ls(array(
  'path' => 'Path/In/Dropbox',
  'file_limit' => 10,
));
```

Check the `Model/Dropbox.php` for more methods. All of the Dropbox API methods
are available. Check the Dropbox API docs for more info:
[https://www.dropbox.com/developers/reference/api].

### Your Own Dropbox Model

In order to keep your models fat and controller skinny. You may want to create
your own model to handle more complex operations with Dropbox. Create a model in
your `app/Model/` folder as such:

``` php
<?php
App::uses('Dropbox', 'Dropbox.Model');
class MyDropbox extends Dropbox {
  public $dropbox_token = 'SET THESE TO YOUR TOKENS';
  public $dropbox_token_secret = 'SET THESE TO YOUR TOKENS';

  public function complex() {
    $info = $this->account_info();
    $this->cp(array(
      'from_path' => 'Copy/This/File.zip',
      'to_path' => 'To/This/File2.zip',
    ));
    return $this->ls('To/This/');
  }
}
```

You can use the `Dropbox.Dropbox` model anywhere with 
`$Dropbox = ClassRegistry::init('Dropbox.Dropbox');`. Just be sure to set the
properties `dropbox_token` and `dropbox_token_secret` with your authenticated
tokens (*not your consumer key/secret*).

## Issues

Please report any issues you have with the plugin to the
[issue tracker](http://github.com/shama/cakebox/issues) on github.

## Thanks!

- JR Conlin for his simple OAuth lib: [https://github.com/jrconlin/oauthsimple]

## License

Cakebox is offered under an [MIT license](http://www.opensource.org/licenses/mit-license.php).

## Copyright

2012 Kyle Robinson Young, [dontkry.com](http://dontkry.com)

If you found this release useful please let the author know! Follow on [Twitter](http://twitter.com/shamakry)