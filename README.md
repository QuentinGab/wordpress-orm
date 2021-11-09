# wodel

Easy way to interact with WordPress database, query, insert and update posts.
And it also works with ACF.

[![Latest Version on Packagist][ico-version]](https://packagist.org/packages/quentingab/wordpress-orm)
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]](https://packagist.org/packages/quentingab/wordpress-orm)

<!-- [![Build Status][ico-travis]][link-travis] -->
<!-- [![Coverage Status][ico-scrutinizer]][link-scrutinizer] -->
<!-- [![Quality Score][ico-code-quality]][link-code-quality] -->

## Install

Via Composer

```bash
$ composer require quentingab/wordpress-orm
```

## Usage with WordPress posts

### Get all posts/page and custom post type

```php
$posts = \QuentinGab\WordpressOrm\Wodel::init()->all();
foreach($posts as $post){
    echo $post->post_title;
}
```

### Get current post with acf

```php
$post = \QuentinGab\WordpressOrm\Wodel::init()->current();
```

### Update a post

```php
$post = \QuentinGab\WordpressOrm\Wodel::init()->current();
$post->post_title = "Hello World";
$post->save();
```

### Insert a post

```php
$post = new \QuentinGab\WordpressOrm\Wodel(
    [
    'post_title'=>'Hello World'
    ]
);
$post->save();
```

## Extend the Wodel

```php
class Page extends \QuentinGab\WordpressOrm\Wodel
{
    protected $post_type = 'page';

    //only necessary if you want to insert a new post programmatically
    //otherwise the acf fields will not be populated
    //If you only get Model or update existing Model you can omit $acf_keys
    protected $acf_keys = [
        'the_field_name' => 'the_field_key',
        'color' => 'field_5f7848684c404',
    ];
}

$page = Page::init()->find(1);
echo $page->acf['color'];
```

## Usage with custom table

if you have data stored in a custom table you can use \QuentinGab\WordpressOrm\Model to interact with the database.
Under the hood it only use default WordPress object $wpdb.

### Example of a custom table

```php
global $wpdb;

$table_name = 'events';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table_name (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    title varchar(255),
    active boolean DEFAULT 0 NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY  (id)
) $charset_collate;";

dbDelta($sql);
```

### Create a Model class

```php
class Event extends \QuentinGab\WordpressOrm\Model
{
    protected $table = 'events';

    protected $primary_key = "id";

    protected $fillable = [
        'title'
    ];

    protected $casts = [
        'active' => 'bool',
        'created_at' => "date"
    ];
}
```

### Get Model

```php
$all = Event::init()->all();
$only_active = Event::init()->where(['active'=>true])->get();
$with_primary_key_1 = Event::init()->find(1);
```

### Save Model

```php
$new_event = new Event(['title'=>'my new event','active'=>false]);
$new_event->save();
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

```bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email quentin.gabriele@gmail.com instead of using the issue tracker.

## Credits

-   [quentin gabriele](https://github.com/QuentinGab)
<!-- - [All Contributors][link-contributors] -->

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/quentingab/wodel.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/quentingab/wodel/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/quentingab/wodel.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/quentingab/wodel.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/quentingab/wodel.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/quentingab/wodel
[link-travis]: https://travis-ci.org/quentingab/wodel
[link-scrutinizer]: https://scrutinizer-ci.com/g/quentingab/wodel/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/quentingab/wodel
[link-downloads]: https://packagist.org/packages/quentingab/wodel
[link-author]: https://github.com/quentingab
[link-contributors]: ../../contributors
