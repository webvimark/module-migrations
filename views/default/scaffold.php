<?php
/**
 * @var $this yii\web\View
 * @var $title string
 */
use webvimark\modules\migrations\forms\ScaffoldForm;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

?>
<?php $this->title = 'Scaffold' ?>
<h1><?= $this->title ?></h1>

<?= $this->render('_buttons') ?>
<br/>

<div class="well">
	<b>Example:</b>
<pre>
book active:tinyint(1) not null default 1 | sorter:int not null | name:string not null | author_id:int <b>fk</b> user (id) ON DELETE CASCADE ON UPDATE CASCADE
</pre>

	<b>Pivot table example:</b>
<pre>
user_has_book user_id:int <b>fk_pk</b> user (id) ON DELETE CASCADE ON UPDATE CASCADE | book_id:int <b>fk_pk</b> book (id) ON DELETE CASCADE ON UPDATE CASCADE | amount:int not null
</pre>

	To create several tables, separated them with new line (just press Enter)
	<br/>
	ID and timestamps are auto created
</div>

<?php $form = ActiveForm::begin([
	'id'      => 'create-migration',
//	'options' => ['class' => 'form-horizontal'],
]) ?>


	<?= $form->field($model, 'path')
		->dropDownList(ScaffoldForm::getModulesAsArray(), ['prompt'=>'--- Application ---', 'autofocus'=>true]) ?>

	<?= $form->field($model, 'name') ?>

	<?= $form->field($model, 'code')->textarea(['rows'=>10]) ?>

	<?= Html::submitButton('Create', ['class' => 'btn btn-primary']) ?>

<?php ActiveForm::end() ?>