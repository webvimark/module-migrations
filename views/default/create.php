<?php
/**
 * @var $this yii\web\View
 * @var $title string
 */
use webvimark\modules\migrations\forms\CreateForm;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

?>
<?php $this->title = 'Create migration' ?>

<div class="panel panel-default">
	<div class="panel-body">
		<?= $this->render('_buttons') ?>
		<br/>

		<?php $form = ActiveForm::begin([
			'id'      => 'create-migration',
		]) ?>

		<div class="row">

			<div class="col-sm-5">
				<?= $form->field($model, 'path')
					->dropDownList(CreateForm::getModulesAsArray(), ['prompt'=>'--- Application ---', 'autofocus'=>true]) ?>
			</div>

			<div class="col-sm-5">
				<?= $form->field($model, 'name') ?>
			</div>


			<div class="col-sm-2">
				<br/>
				<?= Html::submitButton('Create', ['class' => 'btn btn-primary']) ?>
			</div>
		</div>

		<?php ActiveForm::end() ?>
	</div>
</div>
