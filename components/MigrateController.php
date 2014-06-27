<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 6/9/14
 * Time: 10:54 AM
 */

namespace webvimark\modules\migrations\components;


use webvimark\modules\migrations\forms\CreateForm;
use yii\console\Exception;
use yii\db\Connection;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use Yii;

class MigrateController extends \yii\console\controllers\MigrateController
{
	/**
	 * Adding 0766 mode to [[FileHelper::createDirectory()]]
	 * And create "path" column in migration table if it doesn't exists
	 *
	 * @inheritdoc
	 */
	public function beforeAction($action)
	{
		$path = Yii::getAlias($this->migrationPath);
		if ( !is_dir($path) )
		{
			echo "";
			FileHelper::createDirectory($path, 0777);
		}
		$this->migrationPath = $path;

		if ( $action->id !== 'create' )
		{
			if ( is_string($this->db) )
			{
				$this->db = Yii::$app->get($this->db);
			}
			if ( !$this->db instanceof Connection )
			{
				throw new Exception("The 'db' option must refer to the application component ID of a DB connection.");
			}
		}

//		$this->checkPathColumn();

		$version = Yii::getVersion();
		echo "Yii Migration Tool (based on Yii v{$version})\n\n";

		return true;
	}

	/**
	 * Chmod added
	 *
	 * @inheritdoc
	 */
	public function actionCreate($name)
	{
		if (!preg_match('/^\w+$/', $name)) {
			throw new Exception("The migration name should contain letters, digits and/or underscore characters only.");
		}

		$name = 'm' . gmdate('ymd_His') . '_' . $name;
		$file = $this->migrationPath . DIRECTORY_SEPARATOR . $name . '.php';

		if ($this->confirm("Create new migration '$file'?")) {
			$content = $this->renderFile(Yii::getAlias($this->templateFile), ['className' => $name]);

			file_put_contents($file, $content);
			chmod($file, 0766);

			echo "New migration created successfully.\n";
		}
	}

	/**
	 * @inheritdoc
	 */
	public function actionNew($limit = 10)
	{
		if ($limit === 'all') {
			$limit = null;
		}
		else
		{
			$limit = (int)$limit;
			if ( $limit < 1 )
			{
				throw new Exception("The step argument must be greater than 0.");
			}
		}

		$migrations = $this->getNewMigrations();

		if ( empty($migrations) )
		{
			echo "No new migrations found. Your system is up-to-date.\n";
		}
		else
		{
			$n = count($migrations);
			if ( $limit && $n > $limit )
			{
				$migrations = array_slice($migrations, 0, $limit);
				echo "Showing $limit out of $n new " . ($n === 1 ? 'migration' : 'migrations') . ":\n";
			}
			else
			{
				echo "Found $n new " . ($n === 1 ? 'migration' : 'migrations') . ":\n";
			}

			foreach ($migrations as $version => $path)
			{
				echo "    " . $this->niceName($version, $path);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function actionUp($limit = 0)
	{
		$migrations = $this->getNewMigrations();
		if ( empty($migrations) )
		{
			echo "No new migration found. Your system is up-to-date.\n";

			return self::EXIT_CODE_NORMAL;
		}

		$total = count($migrations);
		$limit = (int)$limit;
		if ( $limit > 0 )
		{
			$migrations = array_slice($migrations, 0, $limit);
		}

		$n = count($migrations);
		if ( $n === $total )
		{
			echo "Total $n new " . ($n === 1 ? 'migration' : 'migrations') . " to be applied:\n";
		}
		else
		{
			echo "Total $n out of $total new " . ($total === 1 ? 'migration' : 'migrations') . " to be applied:\n";
		}

		foreach ($migrations as $version => $path)
		{
			echo "    " . $this->niceName($version, $path);
		}
		echo "\n";

		if ( $this->confirm('Apply the above ' . ($n === 1 ? 'migration' : 'migrations') . "?") )
		{
			foreach ($migrations as $version => $path)
			{
				if ( !$this->migrateUp($path . '/' . $version) )
				{
					echo "\nMigration failed. The rest of the migrations are canceled.\n";

					return self::EXIT_CODE_ERROR;
				}
			}
			echo "\nMigrated up successfully.\n";
		}
	}

	/**
	 * @inheritdoc
	 */
	public function actionHistory($limit = 10)
	{
		if ($limit === 'all') {
			$limit = null;
		} else {
			$limit = (int) $limit;
			if ($limit < 1) {
				throw new Exception("The step argument must be greater than 0.");
			}
		}

		$migrations = $this->getMigrationHistory($limit);

		if (empty($migrations)) {
			echo "No migration has been done before.\n";
		} else {
			$n = count($migrations);
			if ($limit > 0) {
				echo "Showing the last $n applied " . ($n === 1 ? 'migration' : 'migrations') . ":\n";
			} else {
				echo "Total $n " . ($n === 1 ? 'migration has' : 'migrations have') . " been applied before:\n";
			}
			foreach ($migrations as $migration) {
				echo "    (" . date('Y-m-d H:i:s', $migration['apply_time']) . ') ' . $this->niceName($migration['version'], $migration['path']) . "\n";
			}
		}
	}


	/**
	 * @inheritdoc
	 */
	protected function getNewMigrations()
	{
		$applied = [];
		foreach ($this->getMigrationHistory(-1) as $migration) {
			$applied[substr($migration['version'], 1, 13)] = true;
		}

		$migrations = $this->getNewMigrationsFromDir($this->migrationPath, $applied);

		$modules = CreateForm::getModulesAsArray();

		foreach ($modules as $moduleMigrationsPath => $moduleId)
		{
			if ( is_dir($moduleMigrationsPath) )
			{
				$migrations = ArrayHelper::merge($migrations, $this->getNewMigrationsFromDir($moduleMigrationsPath, $applied));
			}
		}

		ksort($migrations);

		return $migrations;
	}


	/**
	 * Scan directory for new migrations
	 *
	 * @param string $dir
	 * @param array  $applied
	 *
	 * @return array
	 */
	protected function getNewMigrationsFromDir($dir, $applied)
	{
		$migrations = [];

		$handle = opendir($dir);
		while (($file = readdir($handle)) !== false)
		{
			if ( $file === '.' || $file === '..' )
			{
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $file;
			if ( preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($path) && !isset($applied[$matches[2]]) )
			{
				$migrations[$matches[1]] = $dir;
			}
		}
		closedir($handle);

		return $migrations;
	}

	/**
	 * Add "path" to select
	 *
	 * @inheritdoc
	 */
	protected function getMigrationHistory($limit)
	{
		if ($this->db->schema->getTableSchema($this->migrationTable, true) === null) {
			$this->createMigrationHistoryTable();
		}
		$query = new Query;
		$rows = $query->select(['version', 'apply_time', 'path'])
			->from($this->migrationTable)
			->orderBy('version DESC')
			->limit($limit)
			->createCommand($this->db)
			->queryAll();

		return $rows;
//		$history = ArrayHelper::map($rows, 'path', 'apply_time', 'version');
//		unset($history[self::BASE_MIGRATION]);
//
//		return $history;
	}

	/**
	 * Adding 'path' value to insert command
	 *
	 * @inheritdoc
	 */
	protected function migrateUp($file)
	{
		$tmp = explode('/', $file);
		$class = end($tmp);

		if ( $class === self::BASE_MIGRATION )
		{
			return true;
		}

		echo "*** applying $class\n";
		$start     = microtime(true);
		$migration = $this->createMigration($file);
		if ( $migration->up() !== false )
		{
			$path = substr($file, strlen(\Yii::$app->basePath));
			$path = substr($path, 0, -strlen($class));

			$this->db->createCommand()->insert($this->migrationTable, [
				'path'       => $path,
				'version'    => $class,
				'apply_time' => time(),
			])->execute();
			$time = microtime(true) - $start;
			echo "*** applied $class (time: " . sprintf("%.3f", $time) . "s)\n\n";

			return true;
		}
		else
		{
			$time = microtime(true) - $start;
			echo "*** failed to apply $class (time: " . sprintf("%.3f", $time) . "s)\n\n";

			return false;
		}
	}


	/**
	 * @inheritdoc
	 */
	public function actionDown($limit = 1)
	{
		if ($limit === 'all') {
			$limit = null;
		} else {
			$limit = (int) $limit;
			if ($limit < 1) {
				throw new Exception("The step argument must be greater than 0.");
			}
		}

		$migrations = $this->getMigrationHistory($limit);

		if (empty($migrations)) {
			echo "No migration has been done before.\n";

			return self::EXIT_CODE_NORMAL;
		}

		$n = count($migrations);
		echo "Total $n " . ($n === 1 ? 'migration' : 'migrations') . " to be reverted:\n";
		foreach ($migrations as $migration) {
			echo "    " . $this->niceName($migration['version'], $migration['path']);
		}
		echo "\n";

		if ($this->confirm('Revert the above ' . ($n === 1 ? 'migration' : 'migrations') . "?")) {
			foreach ($migrations as $migration) {
				if (!$this->migrateDown(\Yii::$app->basePath . '/'.$migration['path'] . '/' . $migration['version'])) {
					echo "\nMigration failed. The rest of the migrations are canceled.\n";

					return self::EXIT_CODE_ERROR;
				}
			}
			echo "\nMigrated down successfully.\n";
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function migrateDown($file)
	{
		$tmp = explode('/', $file);
		$class = end($tmp);

		if ($class === self::BASE_MIGRATION) {
			return true;
		}

		echo "*** reverting $class\n";
		$start = microtime(true);
		$migration = $this->createMigration($file);
		if ($migration->down() !== false) {
			$this->db->createCommand()->delete($this->migrationTable, [
				'version' => $class,
			])->execute();
			$time = microtime(true) - $start;
			echo "*** reverted $class (time: " . sprintf("%.3f", $time) . "s)\n\n";

			return true;
		} else {
			$time = microtime(true) - $start;
			echo "*** failed to revert $class (time: " . sprintf("%.3f", $time) . "s)\n\n";

			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function createMigration($file)
	{
		require_once($file . '.php');

		$tmp = explode('/', $file);
		$class = end($tmp);

		return new $class(['db' => $this->db]);
	}


	// ================= Added methods =================
	/**
	 * Add "path" column
	 */
	protected function checkPathColumn()
	{
		if ( $this->_migrationTableExists() AND !$this->_pathColumnExists() )
		{
			$this->db->createCommand()->addColumn($this->migrationTable, 'path', 'string not null')->execute();
			\Yii::$app->cache->flush();
		}
	}

	/**
	 * @return bool
	 */
	private function _pathColumnExists()
	{
		return true;
		return $this->db->getTableSchema($this->migrationTable)->getColumn('path') !== null;
	}

	/**
	 * @return bool
	 */
	private function _migrationTableExists()
	{
		return true;
		return $this->db->schema->getTableSchema($this->migrationTable, true) !== null;
	}

	/**
	 * Show migration name with path
	 *
	 * @param string $version
	 * @param string $path
	 *
	 * @return string
	 */
	protected function niceName($version, $path)
	{
//		$pathName = substr($path, strlen(\Yii::$app->basePath));
//		$pathName = substr($pathName, 0, -strlen('migrations'));
		$pathName = str_replace(\Yii::$app->basePath, '', $path);

		return $version . " <span class='badge alert-info'>[[" . $pathName . "]]</span>" . "\n";
	}
} 