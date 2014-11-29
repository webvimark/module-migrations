<?php
namespace webvimark\modules\migrations\forms;


use yii\base\Model;
use yii\helpers\ArrayHelper;

class CreateForm extends Model
{
	public $name;
	public $path;

	protected static $_excludeSystemModules = ['gii', 'debug'];

	/**
	 * @return array
	 */
	public static function getModulesAsArray()
	{
		$result = [];
		foreach (\Yii::$app->modules as $moduleId => $unusedStuff)
		{
			if ( in_array($moduleId, self::$_excludeSystemModules) )
			{
				continue;
			}

			$module = \Yii::$app->getModule($moduleId);
			$result[$module->basePath . '/migrations'] = $module->id;
		}

		return $result;
	}

	public function rules()
	{
		return [
			['name', 'trim'],
			['name', 'match', 'pattern'=>'/^\w+$/'],
			['path', 'safe'],
			['name', 'required'],
		];
	}

	public function attributeLabels()
	{
		return [
			'name'=>'Migration name',
			'path'=>'Module',
		];
	}

} 