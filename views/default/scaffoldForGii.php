<?php
/**
 * @var $this yii\web\View
 * @var $title string
 * @var $ns string
 * @var $path string
 * @var $tables array
 */
use yii\helpers\Html;
use yii\helpers\Inflector;

?>

<?php $this->title= $title ?>
<h1><?= $title ?></h1>

<?= $this->render('_buttons') ?>
<br/>

<pre>
<?= $output ?>
</pre>

<div class="well">
	To create models follow these links:
	<br/><br/>

	<ul>
		<?php foreach ($tables as $table): ?>
		<li>
			<?= Html::a(
				$table,
				['/gii/default/view', 'id'=>0, 'tableName'=>$table, 'modelClass'=>Inflector::id2camel($table, '_'), 'ns'=>$ns],
				['target'=>'_blank']
			) ?>
		</li>
		<?php endforeach ?>
	</ul>

</div>