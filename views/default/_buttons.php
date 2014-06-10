<?php
use yii\helpers\Html;
?>
<div class="row">
	<div class="col-sm-12">
		<?= Html::a('Apply migrations', ['up'], [
			'class'=>'btn btn-success',
			'data-confirm'=>'Apply new migrations ?',
		]) ?>
		<?= Html::a('New migrations', ['index'], ['class'=>'btn btn-default']); ?>
		<?= Html::a('Migration history', ['history'], ['class'=>'btn btn-default']) ?>
		<?= Html::a('Create migration', ['create'], ['class'=>'btn btn-primary']) ?>
		<?= Html::a('Revert last migration', ['down'], [
			'class'=>'btn btn-warning pull-right',
			'data-confirm'=>'Revert last migration ?',
		]) ?>
	</div>
</div>