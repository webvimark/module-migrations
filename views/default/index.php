<?php
/**
 * @var $this yii\web\View
 * @var $title string
 */

$this->title = $title;
?>
<div class="panel panel-default">
	<div class="panel-body">

		<?= $this->render('_buttons') ?>
		<br/>

<pre>
<?= $output ?>
</pre>
	</div>
</div>
