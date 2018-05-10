# Laravel SignRequest
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/laravel-newsletter/master.svg?style=flat-square)](https://styleci.io/analyses/z36WeP)
[![StyleCI](https://styleci.io/repos/35035915/shield?branch=master)](https://styleci.io/repos/132089985)
[![Total Downloads](https://img.shields.io/packagist/dt/altelma/laravel-signrequest.svg?style=flat-square)](https://packagist.org/packages/altelma/laravel-signrequest)

## Inspiration
https://github.com/AtaneNL/SignRequest

## Installation

You can install this package via composer using:

```bash
composer require altelma/laravel-signrequest
```
The package will automatically register itself.

To publish the config file to `config/signrequest.php` run:

```bash
php artisan vendor:publish --provider="Altelma\LaravelSignRequest\SignRequestServiceProvider"
```

## Usage
```php
$file = 'http://www.example.com/example.pdf'
$cdr = SignRequest::createDocumentFromURL($file);
$sender = 'admin@example.com';
$recipients = [
            [
                'email' => 'receiver@domain.com',
                'from_email_name' => 'John Doe',
            ],
        ];
$message = 'Hey, please sign this document.'; // optional
$request = SignRequest::sendSignRequest($cdr->uuid, $sender, $recipients, $message);
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Feedback
Welcome for any help and suggestions.
