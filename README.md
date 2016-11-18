# Do more with WordPress, faster.

This library encourages developers to build rich and useful applications
on top of WordPress by providing simple, object-oriented APIs for setting
up REST APIs, querying the database, caching data, and interfacing with 
third-party systems like Stripe and Amazon's AWS.

Many of these features are powered by the [Laravel Framework](https://laravel.com/docs/5.3), 
which we have either integrated or emulated to the effect that most of 
what we love about Laravel—object relational mapping, the service container,
service providers, schema migrations, event broadcasting—lives side-by-side
with all that we love about WordPress.

Best of all: this library is 100% compatible with WordPress development
as you know it today—you can use as many or as few of this project's features
as you wish!

The only rule is that you go *faster* with it than without it.

## How did this happen?

The birth of the [WP REST API project](http://v2.wp-api.org/) inspired us
to re-examine WordPress: can it be something more than a first-class content 
management system—could it also be an application development platform? 

The answer is and has been yes—WordPress is, at its core, a really
sophisticated and complex PHP framework. But just because that framework 
happens to be primarily geared toward managing content hasn't stopped anyone from 
trying to build it out to suit more sophisticated purposes.

Unfortunately, using WordPress to build applications isn't as easy as 
building applications using other frameworks—there is a "WordPress way," 
and it mostly leaves us wanting for easier ways of expressing ourselves
through code. (Oh the irony of a content management system that arrests expression!)

## So, what does easier look like?

Well, here are some highlights:

### Create REST APIs with as little code as possible

Let's create a REST endpoint for loading settings data through 
WordPress' built-in Options API:

```php
namespace YourPlugin;

use FatPanda\Illuminate\WordPress\Http\Router;

$router = new Router('your-plugin', 'v1');

$router->get('/option/{name}', function(\WP_REST_Request $request) {
	return get_option($request['name']);
});
```

The above makes use of our special `Router` class to create a
REST API endpoint `/wp-json/your-plugin/v1/option/(?P<name>.*+)`; the
endpoint behaves exactly as you would expect any endpoint built
for the WP REST API to behave, but the implementation has the
benefit of being compact and highly readable.

The function `Router::get`, and its siblings `Router::post`, 
`Router::put`, and `Router::delete` (among others) tell `Router` which
HTTP verb to respond to with the given handler function; in the
example above, the handler function is only invoked for `GET` requests.

Expanding upon the above, let's add an endpoint that provides for
writing to our options table: 

```php
$router->post('/option/{name}', function(\WP_REST_Request $request) {
	return add_option($request['value']);
})->args([
	'value' => [ 
		'rules' => 'required|numeric', 
		'description' => 'The value to store in the given option',
		'default' => 3.1415
	]	
])
```

The example above introduces the function `Route::args`.

`Route::args` can be used to specify the arguments that are valid
for the endpoint: in addition to allowing for all the [normal configurations](http://v2.wp-api.org/extending/adding/) 
available via the WP REST API, `Route::args` introduces a `rules`
configuration argument through which you can stack any of the 
validation rules provided by [Illuminate\Validation](https://laravel.com/docs/5.3/validation#available-validation-rules).

One final example—an endpoint for deleting options:

```php
$router->delete('/option/{name}', function(\WP_REST_Request $request) {
	return delete_option($request['name']);
})->when(function() {
	// only admins can delete options
	return current_user_can('administrator');
});
```

The example above introduces the function `Route::when`.

`Route::when` is used to dictate whether or not the endpoint can be reached by
the current user. Here we are using WordPress' ACL API to restrict
access to this endpoint to users who have the `administrator` role.

### Working with data without the context-switching

More examples coming soon.