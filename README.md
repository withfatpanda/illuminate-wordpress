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
building applications using other Frameworks—there is a "WordPress way," 
and it mostly leaves us wanting for easier ways of expressing ourselves
through code—oh the irony of a content management system that arrests expression!

## So, what does easier look like?

Well, here are some highlights:

### Create REST APIs with as little code as possible

```php
namespace YourPlugin;

use FatPanda\Illuminate\WordPress\Http\Router;

$router = new Router('your-plugin', 'v1');

$router->get('/option/{name}', function(\WP_REST_Request $request) {
	return get_option($request['name']);
})->when('__return_true'); // all users can read option values

$router->post('/option/{$name}', function(\WP_REST_Request $request) {
	return add_option($request['value']);
})->args([
	'value' => [ 
		'rules' => 'required|numeric', 
		'description' => 'The value to store in the given option' 
	]	
])->when(function() {
	// only admins can add options
	return current_user_can('administrator');
});

$router->delete('/option/{name}', function(\WP_REST_Request $request) {
	return delete_option($request['name']);
})->when(function() {
	// only admins can delete options
	return current_user_can('administrator');
});
```

This example uses anonymous functions to process requests; the use
of POPO controllers is also supported. 

The functions `Router::get`, `Router::post`, `Router::put`, and
`Router::delete` (among others) are each keyed to the request method
used to make the request; if `GET /wp-json/your-plugin/v1/option/foo`
is requested, that first function will be invoked, bound by `Router::get`.

The `Route::when` function
allows you to dictate whether or not the endpoint can be reached by
the current user. `Route::args` allows you to specify the arguments
an endpoint receives, validating their content using the simple,
stackable syntax provided by the Laravel [Validation component](https://laravel.com/docs/5.3/validation).

### Working data without context-switching