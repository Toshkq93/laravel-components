##  Installation

Install the package via composer

```bash
composer require --dev toshkq93/laravel-components
```
## Usage

Publish the config file with:
```
php artisan vendor:publish --provider=Toshkq93\Components\Providers\ComponentProvider
```

Go to the root folder of your project and enter the command:
```
php artisam make:components {namespace model} --all
```
Example:
```php
php artisam make:components App\Models\User --all
```

and your project will create:
- DTOs (input (App\DTO\Input) and output (App\DTO\Output) classes) + interfaces,
- controller (App\Http\Controllers\API)
- requests (App\Http\Requests),
- resources (App\Http\Resources),
- repository and service (interface (App\Contracts\Repositories) + class (App\Repositories) + base class),
- service (interface ( App\Contracts\Services) + class (App\Services) + base class)

you can also use other flags in various combination:
- --dto - create DTO classes input and output,
- --controller - create controller class,
- --resource - create resource classes,
- --service - create service class,
- --request - create request classes,
- --repository - create repository class.
- --all - all of the above


After generating services and repositories, bind is automatically added to the container in the App\Provides\AppServiceProvider.

After the controller is generated, a resource route is automatically added depending on the file name (api - apiResource, web - resource). You can also change the path to the 'route_path' route file in the configuration file.


## Security

If you discover any security related issues, please email ***a.stanovoi170993@gmail.com*** instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
