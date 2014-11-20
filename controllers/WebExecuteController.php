<?php

namespace webvimark\modules\migrations\controllers;

use yii\web\Controller;
use Yii;
use yii\web\ForbiddenHttpException;

class WebExecuteController extends Controller
{
	/**
	 * Checks if key is correct and web execute is enabled
	 *
	 * @param string $key
	 *
	 * @throws \yii\web\ForbiddenHttpException
	 * @return string
	 */
	public function actionIndex($key)
	{
		if ( !empty($this->module->webExecuteKey) AND $this->module->webExecuteEnabled === true AND $this->module->webExecuteKey == $key)
		{
			$script = Yii::getAlias($this->module->executableYii);

			$command = " {$script} migrate/up --interactive=0";

			ob_start();
			echo "<pre>";
			system($command);
			echo "</pre>";
			return ob_get_clean();
		}
		else
		{
			throw new ForbiddenHttpException;
		}
	}
} 