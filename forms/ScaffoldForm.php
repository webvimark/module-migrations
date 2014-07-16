<?php
namespace webvimark\modules\migrations\forms;


use yii\base\Model;
use yii\helpers\ArrayHelper;

class ScaffoldForm extends Model
{
	public $name;
	public $code;
	public $path;

	protected static $_excludeSystemModules = ['gii', 'debug'];

	/**
	 * @return array
	 */
	public static function getModulesAsArray()
	{
		$result = [];
		foreach (\Yii::$app->modules as $moduleId => $unusedStuff)
		{
			if ( in_array($moduleId, self::$_excludeSystemModules) )
			{
				continue;
			}

			$module = \Yii::$app->getModule($moduleId);
			$result[$module->basePath . '/migrations'] = $module->id;
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function getUpCode()
	{
		$lines = explode("\n", $this->code);

		$tables = [];

		foreach ($lines as $line)
		{
			$tableFields = [];
			$line = trim($line);

			if ( $line )
			{
				$words = explode(' ', $line);

				if ( isset( $words[0] ) )
				{
					$fks = [];

					$tableFields['id'] = 'pk';

					$line = ltrim($line, $words[0]);

					$fields = explode('|', $line);

					foreach ($fields as $field)
					{
						$field = trim($field);

						$fieldParts = explode(':', $field);

						if ( stripos($fieldParts[1], 'fk') !== false )
						{
							$fkParts = explode(' ', $fieldParts[1]);
							$fieldType = '';
							$fkString = '';

							foreach ($fkParts as $fkPart)
							{
								if ( stripos($fkPart, 'fk') !== false )
								{
									array_shift($fkParts);

									$fkString = implode(' ', $fkParts);
									break;
								}

								$fieldType .= $fkPart . ' ';
								array_shift($fkParts);
							}
							$tableFields[trim($fieldParts[0])] = trim($fieldType);

							$fks[] = 'FOREIGN KEY (' . $fieldParts[0] . ') REFERENCES ' . $fkString;
						}
						else
						{
							$tableFields[trim($fieldParts[0])] = trim($fieldParts[1]);
						}

					}

					$tableFields['created_at'] = 'int not null';
					$tableFields['updated_at'] = 'int not null';

					foreach ($fks as $fk)
					{
						$tableFields[] = $fk;
					}

					$tables[$words[0]] = $tableFields;
				}
			}
		}

		$result = "\$tableOptions = null;
		if ( \$this->db->driverName === 'mysql' )
		{
			\$tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
		}\n\n";


		foreach ($tables as $table => $fields)
		{
			$result .= "\$this->createTable('{$table}', " . var_export($fields, true) .", \$tableOptions);\n\n\n";
		}

		return urlencode($result);
	}

	/**
	 * @return string
	 */
	public function getDownCode()
	{
		$tables = $this->getTables();

		$result = '';

		foreach (array_reverse($tables) as $table)
		{
			$result .= "\$this->dropTable('{$table}');\n";
		}

		return urlencode($result);
	}

	/**
	 * Array of tables to be created
	 *
	 * @return array
	 */
	public function getTables()
	{
		$lines = explode("\n", $this->code);

		$tables = [];

		foreach ($lines as $line)
		{
			$line = trim($line);

			if ( $line )
			{
				$words = explode(' ', $line);

				if ( isset( $words[0] ) )
				{
					$tables[] = $words[0];
				}
			}
		}

		return $tables;
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return [
			[['code', 'name'], 'filter', 'filter'=>'trim'],
			['path', 'safe'],
			[['name', 'code'], 'required'],
//			['name', 'replaceSpaces'],
		];
	}

	public function replaceSpaces()
	{
		if ( $this->name )
		{
			$this->name = str_replace($this->name, ' ', '_');
		}
	}

	public function attributeLabels()
	{
		return [
			'name'=>'Migration name',
			'code'=>'Migration Code',
			'path'=>'Module',
		];
	}

} 