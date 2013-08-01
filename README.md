AccessRestrictable
==================

This behavior adds automatic access restriction to your ActiveRecord queries.

By doing so it introduces a new security layer right inside the models. If any user
tries to access resources that he doesn't have permission to, the query result will simply be empty.

# Installation

We recommend to install the extension with [composer](http://getcomposer.org/). Add this to
the `require` section of your `composer.json`:

    'codemix/accessrestrictable' : 'dev-master'

> Note: There's no stable version yet.

You also need to include composer's autoloader on top of your `index.php`:

    require_once __DIR__.'/protected/vendor/autoload.php';

Make sure to fix the path to your composer's `vendor` directory. Finally you also need to
configure an `alias` in your `main.php`:

```php
$vendor = realpath(__DIR__.'/../vendor');
return array(
    'alias' => array(
        'AccessRestrictable' => $vendor.'/codemix/AccessRestrictable/src/AccessRestrictable',
    ),
    ...
```

# Configuration

To enable access restriction you have to attach the behavior to an ActiveRecord like so:

```php
<?php
class Post extends CActiveRecord
{
    public function behaviors()
    {
        return array(
            'restrictable' => array(
                'class'             => AccessRestrictable\Behavior',

                // We use a callback to give you max. flexibility
                'beforeAccessCheck' => function($model) {
                    $table      = $model->getTableAlias();

                    return array(
                        'condition' => "$table.user_id = :id",
                        'params'    => array(':id' => Yii::app()->user->id),
                    );
                },
            ),
        );
    }
```

The `beforeAccessCheck` parameter expects a [PHP callback](http://php.net/manual/en/language.types.callable.php)
that receives a `$model` parameter. This is the model as returned by the `model()` method of an ActiveRecord. It
can either return an array with parameters for `CDbCriteria`, a concrete `CDbCriteria` or `false` to restrict
access right away.

# Usage

## Access restriction by default

If you attached the behavior, then whenever you do a query like for example


```php
<?php
$posts = Post::model()->findAll();
```

only the records that fullfill the access condition will be returned.

## Override restriction

But what if you want to query for all records, e.g. for an admin panel you may ask.
You can use the `unrestricted()` scope for this:


```php
<?php
$posts = Post::model()->unrestricted()->findAll();
```

## Disable automatic restriction

You can also disable the automatic access restriction and only do restricted queries
on explicit demand. To do so you need to set `enableRestriction` to false in the
behavior configuration.

You then can apply the restriction condition through a named scope:

```php
<?php
$posts = Post::model()->restricted()->findAll();
```
