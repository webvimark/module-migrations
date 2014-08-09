<?php

namespace webvimark\modules\migrations\controllers;

use webvimark\components\BaseController;
use webvimark\modules\migrations\forms\CreateForm;
use webvimark\modules\migrations\forms\ScaffoldForm;
use Yii;

class DefaultController extends BaseController
{
	public $layout = '//back';
	/**
	 * @return string
	 */
	public function actionIndex()
	{
		return $this->render('index', [
			'title'=>'New migrations',
			'output'=>$this->runMigrationAction('new'),
		]);
	}

	/**
	 * @return string
	 */
	public function actionUp()
	{
		return $this->render('index', [
			'title'=>'Applying migrations',
			'output'=>$this->runMigrationAction('up'),
		]);
	}

	/**
	 * @return string
	 */
	public function actionDown()
	{
		return $this->render('index', [
			'title'=>'Reverting migration',
			'output'=>$this->runMigrationAction('down'),
		]);
	}

	/**
	 * @return string
	 */
	public function actionHistory()
	{
		return $this->render('index', [
			'title'=>'Migration history',
			'output'=>$this->runMigrationAction('history'),
		]);
	}

	/**
	 * @return string
	 */
	public function actionScaffold()
	{
		$model = new ScaffoldForm();

		if ( $model->load(\Yii::$app->request->post()) AND $model->validate() )
		{
			$params['name'] = $model->name;

			if ( $model->path )
				$params['--migrationPath'] = $model->path;

			$params['upCode'] = $model->getUpCode();
			$params['downCode'] = $model->getDownCode();

			$output = $this->runMigrationAction('scaffold', $params);

			$output .= $this->runMigrationAction('up');

			$modules = ScaffoldForm::getModulesAsArray();

			$ns = str_replace('\controllers', '\models', Yii::$app->getModule($modules[$model->path])->controllerNamespace);

			return $this->render('scaffoldForGii', [
				'title'  => 'Scaffolding migration',
				'output' => $output,
				'tables' => $model->getTables(),
				'ns'     => $ns,
			]);
		}

		return $this->render('scaffold', compact('model'));
	}

	/**
	 * @return string
	 */
	public function actionCreate()
	{
		$model = new CreateForm();

		if ( $model->load(\Yii::$app->request->post()) AND $model->validate() )
		{
			$params['name'] = $model->name;

			if ( $model->path )
				$params['--migrationPath'] = $model->path;

			return $this->render('index', [
				'title'=>'Creating migration',
				'output'=>$this->runMigrationAction('create', $params),
			]);
		}

		return $this->render('create', compact('model'));
	}

	/**
	 * Runs console command and returns output as string
	 *
	 * @param string $action
	 * @param array  $params - additional params like --migrationPath etc
	 *
	 * @return string
	 */
	public function runMigrationAction($action = 'new', $params = [])
	{
		$script = Yii::getAlias($this->module->executableYii);

		if ( $action == 'create' )
		{
			$command = " {$script} migrate/{$action} {$params['name']} --interactive=0";
			unset($params['name']);
		}
		elseif ( $action == 'scaffold' )
		{
			$command = " {$script} migrate/{$action} {$params['name']} {$params['upCode']} {$params['downCode']}  --interactive=0";
			unset($params['name']);
			unset($params['upCode']);
			unset($params['downCode']);
		}
		else
		{
			$command = " {$script} migrate/{$action} --interactive=0";
		}

		foreach ($params as $option => $value)
		{
			$command .= " {$option}={$value}";
		}

		ob_start();
		system($command);
		return ob_get_clean();
	}
}
