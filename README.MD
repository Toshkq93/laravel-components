##  Installation

Install the package via composer

```bash
composer require --dev toshkq93/laravel-components
```
## Usage

Go to the root folder of your project and enter the command:
```
php artisam make:components {namespace model} --all
```
Example:
```php
php artisam make:components App\Models\User --all
```

and your project will create:
- DTOs (input (App\DTO\Input) and output (App\DTO\Input) classes),
- controller (App\Http\Controllers\API), requests (App\Http\Requests),
- resources (App\Http\Resources),
- repository and service (interface (App\Contracts\Repositories) + class (App\Repositories)),
- service (interface ( App\Contracts\Services) + class (App\Services))

you can also use other flags in various combination:
- --dto - create DTO classes input and output,
- --controller - create controller class,
- --resource - create resource classes,
- --service - create service class,
- --request - create request classes,
- --repository - create repository class.

## Security

If you discover any security related issues, please email ***a.stanovoi170993@gmail.com*** instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
