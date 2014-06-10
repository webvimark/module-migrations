<?php

namespace app\webvimark\modules\migrations\controllers;

use app\webvimark\modules\migrations\forms\CreateForm;
use yii\web\Controller;

class DefaultController extends Controller
{
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
		$script = \Yii::getAlias($this->module->executableYii);

		if ( $action == 'create' )
		{
			$command = " {$script} migrate/{$action} {$params['name']} --interactive=0";
			unset($params['name']);
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
