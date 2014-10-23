<?php
namespace webvimark\modules\migrations\forms;


use yii\base\Model;
use yii\helpers\ArrayHelper;
use Yii;

class ScaffoldForm extends Model
{
	public $name;
	public $code;
	public $path;

	protected static $_excludeSystemModules = ['gii', 'debug'];

	protected $_tableFields = [];
	protected $_tables = [];
	protected $_fks = [];
	protected $_pks = [];

	/**
	 * @return array
	 */
	public static function getModulesAsArray()
	{
		$result = [];
		foreach (Yii::$app->modules as $moduleId => $unusedStuff)
		{
			if ( in_array($moduleId, self::$_excludeSystemModules) )
			{
				continue;
			}

			$module = Yii::$app->getModule($moduleId);
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

		$this->_tables = [];

		foreach ($lines as $line)
		{
			$this->_tableFields = [];
			$line = trim($line);

			if ( $line )
			{
				$words = explode(' ', $line);

				if ( isset( $words[0] ) ) // $words[0] - table name
				{
					$this->_fks = [];

					$this->_tableFields['id'] = 'pk';

					$line = ltrim($line, $words[0]); // remove table name from line

					$fields = explode('|', $line); // extract statements like "sorter:int not null"

					foreach ($fields as $field)
					{
						$field = trim($field);

						$fieldParts = explode(':', $field);

						if ( stripos($fieldParts[1], 'fk_pk') !== false )
						{
							$this->generateFKWithPK($fieldParts, $words[0]);
						}
						elseif ( stripos($fieldParts[1], 'fk') !== false )
						{
							$this->generateFK($fieldParts);
						}
						else
						{
							$this->_tableFields[trim($fieldParts[0])] = trim($fieldParts[1]);
						}

					}

					$this->_tableFields['created_at'] = 'int not null';
					$this->_tableFields['updated_at'] = 'int not null';

					if ( isset($this->_pks[$words[0]]) )
					{
						$this->_tableFields[] = 'PRIMARY KEY ('.implode(',', $this->_pks[$words[0]]).')';
					}

					foreach ($this->_fks as $fk)
					{
						$this->_tableFields[] = $fk;
					}

					$this->_tables[$words[0]] = $this->_tableFields;
				}
			}
		}

		$result = "\$tableOptions = null;
		if ( \$this->db->driverName === 'mysql' )
		{
			\$tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
		}\n\n";


		foreach ($this->_tables as $table => $fields)
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
		$this->_tables = $this->getTables();

		$result = '';

		foreach (array_reverse($this->_tables) as $table)
		{
			$result .= "\$this->dropTable('{$table}');\n";
		}

		return urlencode($result);
	}

	/**
	 * @param array $fieldParts
	 * @param string $tableName
	 */
	protected function generateFKWithPK($fieldParts, $tableName)
	{
		unset($this->_tableFields['id']);

		$fieldName = $this->generateFK($fieldParts);

		$this->_pks[$tableName][] = $fieldName;
	}

	/**
	 * Returns field name
	 *
	 * @param array $fieldParts
	 *
	 * @return string
	 */
	protected function generateFK($fieldParts)
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
		$this->_tableFields[trim($fieldParts[0])] = trim($fieldType);

		$this->_fks[] = 'FOREIGN KEY (' . $fieldParts[0] . ') REFERENCES ' . $fkString;

		return $fieldParts[0];
	}

	/**
	 * Array of tables to be created
	 *
	 * @return array
	 */
	public function getTables()
	{
		$lines = explode("\n", $this->code);

		$this->_tables = [];

		foreach ($lines as $line)
		{
			$line = trim($line);

			if ( $line )
			{
				$words = explode(' ', $line);

				if ( isset( $words[0] ) )
				{
					$this->_tables[] = $words[0];
				}
			}
		}

		return $this->_tables;
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