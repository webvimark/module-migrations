Migrations module for Yii 2
=====
Provide:
* modules support
* fix original migrations permission issue
* GUI interface

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist webvimark/module-migrations "*"
```

or add

```
"webvimark/module-migrations": "*"
```

to the require section of your `composer.json` file.

Configuration
-------------

In your config/web.php

```php
	'modules'=>[
		...

		'migrations'=>[
			'class'=>'webvimark\modules\migrations\MigrationModule',
			'executableYii' => (YII_ENV == 'prod') ? '@app/yii_production' : '@app/yii',
		],

		...
	],
```

In you config/console.php

```php
	...

	'controllerMap'=>[
		'migrate'=>[
			'class'=>'webvimark\modules\migrations\components\MigrateController',
		],
	],

	...
```

Include your desired modules in config/console.php

Usage
-----

Go to http://site.com/migrations/default/index