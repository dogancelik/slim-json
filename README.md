# SlimJson [![Latest Stable Version](https://poser.pugx.org/dogancelik/slim-json/v/stable.png)](https://packagist.org/packages/dogancelik/slim-json)
SlimJson is an easy-to-use and advanced JSON middleware for Slim PHP framework. SlimJson helps you write web apps that return JSON output.

## How to install
You can install SlimJson with Composer by:
```
composer require dogancelik/slim-json
```
or adding this line to your `composer.json` file:
```
"dogancelik/slim-json": "dev-master"
```

## How to use
```php
require 'vendor/autoload.php';
$app = new \Slim\Slim();

// Add the middleware globally
$app->add(new \SlimJson\Middleware(array(
  'json.status' => true,
  'json.override_error' => true,
  'json.override_notfound' => true
)));

$app->get('/', function() use ($app) {
  $app->render(200, ['Hello' => 'World']);
});

$app->get('/error', function() use ($app) {
  throw new \Exception('This is an error');
});

$app->run();
```

If you go to `localhost/`, you will get: `{"Hello": "World", "_status": 200}`

If you go to `localhost/error`: `{"error": "This is an error", "_status": 500}`

If you go to `localhost/notfound`: `{"error": "'/notfound' is not found.", "_status": 404}`

### Rendering JSON
If you haven't noticed, I didn't add a JSON view to our Slim app.
It's because when you add the middleware, we add the JSON view for you so you don't have to.

You should see that we are using a different `$app->render()` method here.

Rendering parameters are these: `function render(status, data)`

* `status` is HTTP return code *integer* or *string*.
* `data` is an *array*.

## Configuration
You can initialize the Middleware with these configuration options.

Example:
```php
$app->add(new \SlimJson\Middleware([
  'json.override_error' => true,
  'json.debug' => true,
  'json.status' => true,
]));
```

**All options are disabled (*false*) by default. Set them to *true* to enable.**

### json.override_error
Configures `$app->error` to return JSON response with HTTP return code `500`.
**Only works if you add the middleware globally**

### json.override_notfound
Configures `$app->notFound` to return JSON response with HTTP return code `404`.
**Only works if you add the middleware globally**

### json.protect
Adds `while(1);` to every JSON response. [What's this?](http://stackoverflow.com/questions/2669690)

### json.status
Adds an integer `_status` field to your JSON response.

### json.debug
If you enable this option, SlimJson will add additional debugging info (named as `_debug`) on `error`.
`Exception` properties (like message, stacktrace, line, etc.) will be added to JSON response.

### json.cors
Enables [CORS](http://enable-cors.org/).

If you set this to `true`, it will set CORS to `*` (allow all domains).
If you set a string, it will set CORS to that string

### json.clear_data
**[You should read this if you don't use the middleware globally. Read why I added this option.](#slimjson-cleardata)**

### json.json_encode_options
Passes an `$options` argument to `json_encode`.

[Visit PHP.net page for available constants for json_encode.](http://php.net/manual/en/function.json-encode.php#refsect1-function.json-encode-parameters)

## Advanced

### Use SlimJson for individual routes
If you don't need JSON for your whole application and want to return JSON for individual routes:

Instead of adding the middleware globally, put `$app->add` inside the routers you want:
```php
$app->get('/', function() use ($app) {
  $app->add(new \SlimJson\Middleware([
    'json.status' => true
  ]));

  $app->render(200, ['Hello' => 'World']);
});
```

#### Use inject() for handiness and happiness :smile:
I created a static method under the middleware called `inject($app, $config)`, it is basically same as calling `$app->add();` but good for a clean and shorter code.
**Passing the arguments `$app` and `$config` is both optional**.

Replace this:
```php
$app->add(new \SlimJson\Middleware([
  'json.status' => true
]));
```

With this:
```php
\SlimJson\Middleware::inject([
  'json.status' => true
]));
```

### Set your own `$app->error` or `$app->notFound` message
If you add the middleware globally and enable `json.override_error` or `json.override_notfound`, SlimJson will use its own message format for each handler. But you can change that too!

#### Using config
```php
$app = new \Slim\Slim();

$app->add(new \SlimJson\Middleware([
  'json.override_notfound' => function($request) {
    return 'We can\'t find this page: ' . $request->getPath();
  },
]));
```

#### Using Middleware method
```php
$app = new \Slim\Slim();

$slimjson = new \SlimJson\Middleware();

// use `setNotFoundMessage` for `$app->notFound` message
$slimjson->setErrorMessage(function($exception) {
  return 'Custom error message: ' . $exception->getMessage();
});

$app->add($slimjson);
```

---
<span id="slimjson-cleardata"></span>
## Edge case option: json.clear_data
Read this option if you don't use the middleware globally. You may encounter this error.

Let's say you have a GET router (`/foobar`) and an error handler:
```php
$app->get('/foobar', function() use ($app) {
  \SlimJson\Middleware::inject();
  $app->render(200, ['foo' => 'bar']);
});

$app->error(function (\Exception $e) use ($app) {
  \SlimJson\Middleware::inject();
  $app->render(500, ['error' => $e->getMessage()]);
});
```

What happens if you take out `inject()` from the GET router? So it would be like this:
```php
$app->get('/foobar', function() use ($app) {
  $app->render(200, ['foo' => 'bar']);
});
```

Then if you go to `/foobar` you will get an error like this:
```
{"foo" => "bar", "error" => "View cannot render 200 because the template does not exist"}
```

Notice you both have `foo` and `error` keys. **It's because Slim uses `$app->view->appendData()`.**

**Why did this happen? Because we forgot to add `\SlimJson\Middleware::inject();` to the GET router; So don't forget to add this.**

If you enable this option, it will remove all `data`. It may remove other middlewares' data (like *Flash* (Session) middleware) but I haven't tested it.

But if you really want to use this then add `inject(array('json_clear_data' => true))` to your error handler:

```php
$app->error(function (\Exception $e) use ($app) {
  \SlimJson\Middleware::inject(array(
    'json.clear_data' => true
  ));
  $app->render(500, ['error' => $e->getMessage()]);
});
```
