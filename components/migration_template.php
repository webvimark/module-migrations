<?php
/**
 * This view is used by console/controllers/MigrateController.php
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name */
/* @var $upCode string  */
/* @var $downCode string  */

echo "<?php\n";
?>

use yii\db\Migration;

class <?= $className ?> extends Migration
{
	public function safeUp()
	{
		<?= urldecode($upCode) ?>

	}

	public function safeDown()
	{
		<?= urldecode($downCode) ?>

	}
}
