AccessRestrictable
==================

This behavior adds automatic access restriction to your ActiveRecord queries.

By doing so it introduces a new security layer right inside the models. If any user
tries to access resources that he doesn't have permission to, the query result will simply be empty.

## Installation

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

## Configuration

To enable access restriction you have to attach the behavior to an ActiveRecord like so:

```php
<?php
class Post extends CActiveRecord
{
    public function behaviors()
    {
        return array(
            'restrictable' => array(
                'class'             => 'AccessRestrictable\Behavior',

                // Optional settings with default values
                // 'enableRestriction' => true
            ),
        );
    }
```

### Restrict read access

To restrict *read access* for all your queries, you can implement a `beforeRead()` method
in your record. It can return a `boolean` to either apply no restriction at all (`true`)
or restrict access completely (`false`).

Though the more common use case will be, to return a criteria that will be applied to
all queries, and that limits the result set to records that the current user has access to.
To do so the method can either return a `CDbCriteria` or an array with criteria parameters.

Here's an example:

```php
public function beforeRead()
{
    $user   = Yii::app()->user;
    $table  = $this->getTableAlias();

    if($user->checkAccess('admin')) {
        return true;    // no restriction for administrators
    } elseif($user->checkAccess('organizationAdmin')) {
        $userRecord = User::model()->findByPk(Yii::app()->user->id);

        // Admins in an organisation are allowed to see all users from that organisation
        return array(
            'condition' => "$table.organisation_id = :organisation",
            'params'    => array(':organisation' => $userRecord->organisation_id),
        );
    } else {
        // All other users can only query their own user record
        return array(
            'condition' => "$table.user_id = :id",
            'params'    => array(':id' => Yii::app()->user->id),
        );
    }
}
```

### Restrict write access

In order to restrict *write access* for records, you can implement a `beforeWrite()` method
in your record. It will be called before any insert, update or delete operation and must
returns, whether the operation should be performed.

For example:

```php
public function beforeWrite()
{
    $user   = Yii::app()->user;

    if($user->checkAccess('admin')) {
        return true;    // admin can always write
    } elseif($user->checkAccess('organizationAdmin')) {
        $userRecord = User::model()->findByPk(Yii::app()->user->id);

        // Admins in an organisation are allowed to update all users from that organisation
        if($userRecord->organisation_id == $this->organisation_id) {
            return true;
        }
    } elseif($user->id==$this->id) {
        // All users can update their own user record
        return true;
    }

    // All others are denied
    return false;
}
```

## Usage


If you've attached the behavior, then whenever you do a query like


```php
<?php
$posts = Post::model()->findAll();
```

only the records that fullfill the `beforeRead()` condition will be returned.

In the same way any `$post->save()` will fail, if `beforeWrite()` returns `false`.

### Override query restriction

But what if you want to query for all records, e.g. for an admin panel you may ask.
You can use the `unrestricted()` scope for this:


```php
<?php
$posts = Post::model()->unrestricted()->findAll();
```

> **Note:** If you did another (restricted) query before, the restriction condition will
> be applied to the internal model criteria. To reset any potential scopes, you could
> either call `resetScope()` or pass `true` as argument to `unrestricted()`.

### Override write restriction

For write operations you can call the `force()` method before you write. This will bypass
the `beforeWrite()` check and always save the record. For convenience it returns the same
record, so you can easily chain your calls:

```php
<?php
$post->force()->save();
```

> **Note:** Validation rules are still applied. So if your record has validation errors,
> it will not be saved, even if you called `force()` before.

### Disable automatic query and write restriction

You can also disable the automatic access restriction and only do restricted queries
and writes on explicit demand. To do so you need to set `enableRestriction` to false in the
behavior configuration.

You then can apply the restriction query condition through a named scope:

```php
<?php
$posts = Post::model()->readable()->findAll();
```

For write operations you'd use the `writeable()` constraint:

```php
<?php
$post->writeable()->save();
```

## Limitations

Due to the limitations in the ActiveRecord implementation, the constraints from this
behavior are not applied when you use one of the following methods:

 * `deleteAll()`
 * `saveAttributes()`
 * `saveCounters()`
 * `findBySql()`
 * `findAllBySql()`
 * `countBySql()`
 * `exists()`
 * `updateByPk()`
 * `updateAll()`
 * `updateCounters()`
 * `deleteByPk()`
 * `deleteAll()`
 * `deleteAllByAttributes()`

We recommend to avoid the above methods, or only use them if you're sure about the
implications.

