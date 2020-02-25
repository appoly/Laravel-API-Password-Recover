# Laravel: API forgotten password helper

## Introduction

Save time creating functions to manage forgotten passwords in laravel for your Api's. This package will add a new key to your users model, along with two new routes that will handle the forgotten and reset password.

## Quick Usage

### Composer

```
composer require appoly/api-password-helper
```

## Usage

Add the new key to your users table.

```
php artisan migrate
```

The Users Model needs to be _Notifiable_ as an email will be automatically sent to them

```
class User extends Authenticatable
{
    use Notifiable;
```

### Routes

Two new routes will be created in your application

```
GET: /api/forgot-password?email=johndoe@example.com
```
The Post route takes two form data parameters in the request _key_ and _password_. 

```
POST: /api/forgot-password
```

If your user model has `$fillable` ensure that `password_helper_key` is added to the list of fields that are allowed to be updated.

## SmartSchema Compatibility

If you are using [SmartSchema](https://github.com/appoly/smart-schema/) to manage your models fillables then add SmartField to your model

```
class User extends Authenticatable
{
    use Notifiable, SmartField;
```
