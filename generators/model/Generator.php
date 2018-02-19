<?php

namespace mj4444\yii2gii\generators\model;

use yii\gii\CodeFile;
use yii\helpers\Inflector;

class Generator extends \yii\gii\generators\model\Generator
{
    /**
     * @var string the attribute that will receive timestamp value
     * Set this property to false if you do not want to record the creation time.
     */
    public $createdAtAttribute = 'created_at';

    /**
     * @var string the attribute that will receive timestamp value.
     * Set this property to false if you do not want to record the update time.
     */
    public $updatedAtAttribute = 'updated_at';

    /**
     * @var callable|Expression The expression that will be used for generating the timestamp.
     * This can be either an anonymous function that returns the timestamp value,
     * or an [[Expression]] object representing a DB expression (e.g. `new Expression('NOW()')`).
     * If not set, it will use the value of `time()` to set the attributes.
     */
    public $timestampBehaviorValueString;

    /**
     * @var string[][]
     * Index - class name, Value - array attributes
     */
    public $timestampTables = [];

    /**
     * @var string[][]
     */
    public $classesConsts = [];

    /**
     * @var string[][]
     */
    public $classesEnumValues = [];

    /**
     * @var string[]
     */
    public $excludedTables = ['migration'];

    /**
     * @var boolean|string[]
     */
    public $enumWithoutValidators = false;

    /**
     * @var boolean|string[]
     */
    public $uniqueKeysWithoutValidators = false;

    /**
     * @var boolean|string[]
     */
    public $foreignKeysWithoutValidators = false;

    /**
     * @var boolean
     */
    public $requiredStrict = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        return parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Double Model Generator';
    }

    /**
     * @inheritdoc
     */
    protected function getTableNames()
    {
        $tables = parent::getTableNames();
        if (count($tables) > 1) {
            $_this = $this;
            $checker = function($val) use ($_this) {
                return !in_array($val, $_this->excludedTables);
            };
            $tables = array_filter($tables, $checker);
        }
        return $tables;
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $files = parent::generate();
        $_files = [];
        $baseClass = ltrim($this->baseClass, "\\");
        foreach ($files as $file) {
            $class = basename($file->path, '.php');
            $dirname = dirname($file->path);
            $searchStr = "class {$class} extends \\{$baseClass}\n";
            $searchStrPos = strpos($file->content, $searchStr);
            if ($searchStrPos === false) {
                $_files[] = $file;
            } else {
                $_files[] = $this->generateFileBase($file->content, $class, $baseClass, $dirname, $searchStr);
                $header = substr($file->content, 0, $searchStrPos);
                $_files[] = $this->generateFilePrimary($class, $dirname, $header);
            }
        }
        return $_files;
    }

    public function generateFileBaseConst(&$content, $class)
    {
        if (isset($this->classesConsts[$class])) {
            if (($i = strpos($content, "\n{")) !== false) {
                $classConsts = $this->classesConsts[$class];
                foreach ($classConsts as $key => &$value) {
                    $value = "    const {$key} = '{$value}';\n";
                }
                $content = substr_replace($content, implode('', $classConsts) . "\n", $i + 3, 0);
            }
        }
    }

    public function generateFileBaseTimestamp(&$content, $class)
    {
        if (isset($this->timestampTables[$class])) {
            if (($i = strrpos($content, "\n}")) !== false) {
                $ts = $this->timestampTables[$class];
                $createdAtAttribute = isset($ts['create']) ? "'" . $ts['create'] . "'" : 'false';
                $updatedAtAttribute = isset($ts['update']) ? "'" . $ts['update'] . "'" : 'false';
                $addContent = "\n
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => \\yii\\behaviors\\TimestampBehavior::className(),
                'createdAtAttribute' => {$createdAtAttribute},
                'updatedAtAttribute' => {$updatedAtAttribute}" . ($this->timestampBehaviorValueString ? ",
                'value' => {$this->timestampBehaviorValueString}" : "") . "
            ]
        ];
    }";
                $content = substr_replace($content, $addContent, $i, 0);
            }
        }
    }

    public function generateFileBaseAttributeEnumLabels(&$content, $class)
    {
        if (isset($this->classesEnumValues[$class]) && $this->classesEnumValues[$class]) {
            if (($i = strrpos($content, "\n}")) !== false) {
                $enumValues = '';
                foreach ($this->classesEnumValues[$class] as $key => $value) {
                    $enumAttrValues = '';
                    foreach ($value as $attrEnumName) {
                        $attrEnumValue = $this->generateString(Inflector::camel2words($attrEnumName));
                        $enumAttrValues .= "                '{$attrEnumName}' => {$attrEnumValue},\n";
                    }
                    $enumValues .= "            '{$key}' => [
{$enumAttrValues}            ],\n";
                }
                $addContent = "\n
    public function attributeEnumLabels()
    {
        return [
{$enumValues}        ];
    }";
                $content = substr_replace($content, $addContent, $i, 0);
            }
        }
    }

    public function generateFileBase($content, $class, $baseClass, $dirname, $searchStr)
    {
        $replaceStr = "class Base{$class} extends \\{$baseClass}\n";
        $content = str_replace($searchStr, $replaceStr, $content);
        $this->generateFileBaseConst($content, $class);
        $this->generateFileBaseTimestamp($content, $class);
        $this->generateFileBaseAttributeEnumLabels($content, $class);
        return new CodeFile("{$dirname}/Base{$class}.php", $content);
    }

    public function generateFilePrimary($class, $dirname, $header)
    {
        $params = [
            'header' => $header,
            'className' => $class,
            'content' => '',
        ];
        $file = new CodeFile("{$dirname}/{$class}.php", $this->render('model-base.php', $params));
        if ($file->operation == 'overwrite') {
            $oldFileContent = file_get_contents($file->path);
            if (!preg_match('~/\\*\\*(?:.*?) \\*/~s', $file->content, $commentNew)) {
                $file = new CodeFile($file->path, $oldFileContent);
            } else {
                if (!preg_match('~/\\*\\*(?:.*?) \\*/~s', $oldFileContent, $commentOld)) {
                    $file = new CodeFile($file->path, $oldFileContent);
                } else {
                    $commentNew = reset($commentNew);
                    $commentOld = reset($commentOld);
                    if ($commentNew == $commentOld) {
                        $file = new CodeFile($file->path, $oldFileContent);
                    } else {
                        $file->content = str_replace($commentOld, $commentNew, $oldFileContent);
                    }
                }
            }
        }
        return $file;
    }

    public function requiredTemplates()
    {
        $files = parent::requiredTemplates();
        $files[] = 'model-base.php';
        return $files;
    }

    public function generateRules($table)
    {
        $className = $this->generateClassName($table->name);
        if (isset($table->columns[$this->createdAtAttribute])) {
            $this->timestampTables[$className]['create'] = $this->createdAtAttribute;
            $table->columns[$this->createdAtAttribute]->autoIncrement = true;
        }
        if (isset($table->columns[$this->updatedAtAttribute])) {
            $this->timestampTables[$className]['update'] = $this->updatedAtAttribute;
            $table->columns[$this->updatedAtAttribute]->autoIncrement = true;
        }
        $firstRusles = [];
        $rules = [];
        $defaultRules = [];
        $setNullColumns = [];
        $requiredColumns = [];
        foreach ($table->columns as $columnIndex => $column) {
            if (isset($column->defaultValue) && $column->defaultValue !== null) {
                $value = var_export($column->defaultValue, true);
                $defaultRules[serialize($value)][] = $column->name;
            }
        }
        if ($defaultRules) {
            foreach ($defaultRules as $value => $columns) {
                $value = unserialize($value);
                $columns = "'" . implode("', '", $columns) . "'";
                $rules[] = "[[{$columns}], 'default', 'value' => {$value}]";
            }
        }
        $enumWiVa = $this->enumWithoutValidators;
        if ($enumWiVa === false || is_array($enumWiVa)) {
            $inRules = [];
            $eachInRules = [];
            foreach ($table->columns as $columnIndex => $column) {
                    // Bug <
        if (preg_match('/^(\w+)(?:\(([^\)]+)\))?/', $column->dbType, $matches)) {
            $type = strtolower($matches[1]);
            if (!empty($matches[2])) {
                if ($type === 'set') {
                    $values = explode(',', $matches[2]);
                    foreach ($values as $i => $value) {
                        $values[$i] = trim($value, "'");
                    }
                    $column->enumValues = $values;
                }
            }
        }
                    // Bug >
                if (isset($column->enumValues) && is_array($column->enumValues)) {
                    $enumValues = [];
                    foreach ($column->enumValues as $enumValue) {
                        $constName = preg_replace('~[^A-Z0-9_]~', '_', strtoupper("{$column->name}_{$enumValue}"));
                        $enumValues[] = $enumValue;
                        $this->classesConsts[$className][$constName] = $enumValue;
                    }
                    $this->classesEnumValues[$className][$column->name] = $enumValues;
                    if (strncasecmp($column->dbType, 'enum', 4) == 0) {
                        if ($column->allowNull) {
                            $setNullColumns[] = $column->name;
                        } else {
                            $requiredColumns[] = $column->name;
                        }
                        if (!is_array($enumWiVa) || !in_array($table->name, $enumWiVa)) {
                            $enumValues = "'" . implode("', '", $enumValues) . "'";
                            $inRules[serialize($enumValues)][] = $column->name;
                        }
                    }
                    if (strncasecmp($column->dbType, 'set', 3) == 0) {
                        if (!is_array($enumWiVa) || !in_array($table->name, $enumWiVa)) {
                            $enumValues = "'" . implode("', '", $enumValues) . "'";
                            $eachInRules[serialize($enumValues)][] = $column->name;
                        }
                    }
                }
            }
            if ($inRules) {
                foreach ($inRules as $values => $columns) {
                    $values = unserialize($values);
                    $columns = "'" . implode("', '", $columns) . "'";
                    $rules[] = "[[{$columns}], 'in', 'range' => [{$values}], 'strict' => true]";
                }
            }
            if ($eachInRules) {
                foreach ($eachInRules as $values => $columns) {
                    $values = unserialize($values);
                    $columns = "'" . implode("Array', '", $columns) . "Array'";
                    $rules[] = "[[{$columns}], 'each', 'rule' => ['in', 'range' => [{$values}], 'strict' => true]]";
                }
            }
        }
        $baseRules = parent::generateRules($table);
        if ($this->requiredStrict) {
            foreach ($baseRules as &$rule) {
                if (!substr_compare($rule, ", 'required']", -13, 13)) {
                    $rule = substr($rule, 0, -1).", 'strict' => true]";
                }
                if (!substr_compare($rule, ", 'integer']", -12, 12)) {
                    //$firstRusles[] = substr($rule, 0, -9)."default', 'value' => null]";
                    $setNullColumns[] = substr($rule, 3, -14);
                }
            }
            unset($rule);
        }
        $unWiVa = $this->uniqueKeysWithoutValidators;
        if ($unWiVa === true || (is_array($unWiVa) && in_array($table->name, $unWiVa))) {
            foreach ($baseRules as $i => $rule) {
                if (strpos($rule, ", 'unique'") !== false) {
                    unset($baseRules[$i]);
                }
            }
        }
        $fkWiVa = $this->foreignKeysWithoutValidators;
        if ($fkWiVa === true || (is_array($fkWiVa) && in_array($table->name, $fkWiVa))) {
            foreach ($baseRules as $i => $rule) {
                if (strpos($rule, ", 'exist', 'skipOnError' => true,") !== false) {
                    unset($baseRules[$i]);
                }
            }
        }
        if ($requiredColumns) {
            $rules[] = "[['" . implode("', '", $requiredColumns) . "'], 'required']";
        }
        if ($setNullColumns) {
            $firstRusles[] = "[['" . implode("', '", $setNullColumns) . "'], 'default', 'value' => null]";
        }
        return array_merge($firstRusles, $baseRules, $rules);
    }

    private function generateRelationsSort($relations)
    {
        $result = ['simple' => [], 'viaTable' => []];
        foreach ($relations as $tableName => $tableRelations) {
            foreach ($tableRelations as $relationName => $relation) {
                $viaTable = strpos($relation[0], ')->viaTable(') !== false;
                $result[$viaTable ? 'viaTable' : 'simple'][] = [
                    'tableName' => $tableName,
                    'oldName' => $relationName,
                    'relation' => $relation];
            }
        }
        return $result;
    }

    private function generateRelationsAddSimpleRelations(&$relations, $simpleRelations)
    {
        $db = $this->getDbConnection();
        $tablePrefix = $db->tablePrefix;
        $trPairs = [];
        foreach ($simpleRelations as $simpleRelation) {//var_dump($simpleRelation);//die();
            $fullTableName = $simpleRelation['tableName'];
            $tableName = $fullTableName;
            if ($tablePrefix && strpos($tableName, $tablePrefix) === 0) {
                $tableName = substr($tableName, strlen($tablePrefix));
            }
            $code = $simpleRelation['relation'][0];
            $pregTableName = preg_quote('_'.$tableName);
            $key = $simpleRelation['relation'][1];
            if (preg_match("/\\['(\\w+?){$pregTableName}_(\\w+?)' => '\\2'\\]/", $code, $match)) {
                $key = $match[1].$key;
            } elseif (preg_match("/\\['(\\w+?){$pregTableName}' => '(\\w+?)'\\]/", $code, $match)) {
                $key = $match[1].$key;
            } elseif (preg_match("/\\['(\\w+?)_(\\w+?)' => '\\2'\\]/", $code, $match)) {
                if ($tableName != $match[1]) {
                    $key = $match[1].$key;
                }
            } elseif (preg_match("/\\['(\\w+?)' => '(\\w+?)_\\1'\\]/", $code, $match)) {
                $key = $match[2];
            } elseif (preg_match("/\\['(\\w+?)' => '(\\w+?)'\\]/", $code, $match)) {
                if ($match[1] != $match[2]) {
                    if ($simpleRelation['oldName']{0} == '@') {
                        $key = $match[1].$key;
                    } else {
                        $key = $match[2];
                    }
                }
            }
            $tableSchema = $db->getTableSchema($fullTableName);
            $newRelName = parent::generateRelationName($relations, $tableSchema, $key, $simpleRelation['relation'][2]);
            $relations[$fullTableName][$newRelName] = $simpleRelation['relation'];
            if ($simpleRelation['oldName'] != $newRelName) {
                $tName = $this->generateClassName($fullTableName);
                $trPairs[$tName]["inverseOf('" . lcfirst($simpleRelation['oldName']) . "')"] = "inverseOf('" . lcfirst($newRelName) . "')";
            }
        }
        foreach ($relations as $tName => &$rlTable) {
            foreach ($rlTable as &$relation) {
                if (isset($trPairs[$relation[1]])) {
                    $relation[0] = strtr($relation[0], $trPairs[$relation[1]]);
                }
            }
        }
    }

    private function generateRelationsAddViaTableRelations(&$relations, $viaTableRelations)
    {
        $db = $this->getDbConnection();
        foreach ($viaTableRelations as $viaTableRelation) {
            $tableName = $viaTableRelation['tableName'];
            $code = $viaTableRelation['relation'][0];
            $key = $viaTableRelation['relation'][1];
            $multiple = $viaTableRelation['relation'][2];
            if (preg_match("/\\['(\\w+?)' => '(\w+?)_\\1'\\]\\)\\->viaTable\\('(\{\{%)?(\w+?)(\}\})?',/", $code, $match)) {
                if ($multiple) {
                    $key = Inflector::pluralize($match[2]);
                    $multiple = false;
                }
                $key = $key . 'Via_' . $match[4];
            } elseif (preg_match("/\\['(\\w+?)' => '(\w+?)'\\]\\)\\->viaTable\\('(\{\{%)?(\w+?)(\}\})?',/", $code, $match)) {
                if ($multiple) {
                    $key = Inflector::pluralize($match[2]);
                    $multiple = false;
                }
                $key = $key . 'Via_' . $match[4];
            } elseif (preg_match("/->viaTable\\('(\{\{%)?(\w+?)(\}\})?',/", $code, $match)) {
                if ($multiple) {
                    $key = Inflector::pluralize($key);
                    $multiple = false;
                }
                $key = $key . 'Via_' . $match[2];
            }

            $tableSchema = $db->getTableSchema($tableName);
            $newRelName = parent::generateRelationName($relations, $tableSchema, $key, $multiple);
            $relations[$tableName][$newRelName] = $viaTableRelation['relation'];
        }
    }

    protected function generateRelations()
    {
        $relations = [];
        $sortedRelations = $this->generateRelationsSort(parent::generateRelations());
        $this->generateRelationsAddSimpleRelations($relations, $sortedRelations['simple']);
        $this->generateRelationsAddViaTableRelations($relations, $sortedRelations['viaTable']);
        return $relations;
    }

    protected function generateRelationName($relations, $table, $key, $multiple)
    {
        if (ctype_upper($key{0})) {
            $key = "@{$key}";
        }
        return parent::generateRelationName($relations, $table, $key, $multiple);
    }
}
