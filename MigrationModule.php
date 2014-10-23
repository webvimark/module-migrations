<?php

namespace webvimark\modules\migrations;

use yii\di\ServiceLocator;
use yii\helpers\ArrayHelper;

class MigrationModule extends \yii\base\Module
{
	/**
	 * @var string
	 */
	public $executableYii = '@app/yii';

	/**
	 * @var string
	 */
	public $migrationsFolder = 'migrations';

	/**
	 * Default layout
	 *
	 * @var string
	 */
	public $layout = '//main';

	public $controllerNamespace = 'webvimark\modules\migrations\controllers';

	/**
	 * Init
	 */
	public function init()
	{
		parent::init();

//		$this->setMigrationComponent();
		// custom initialization code goes here
	}

	protected function setMigrationComponent()
	{
		$locator = new ServiceLocator;

		$controllerMap = ArrayHelper::merge(\Yii::$app->controllerMap, [
			'migrate' => [
				'class' => 'webvimark\modules\migrations\components\MigrateController',
//				'migrationTable' => 'my_custom_migrate_table',
			]
		]);


		$locator->set('controllerMap', $controllerMap);
	}
}
