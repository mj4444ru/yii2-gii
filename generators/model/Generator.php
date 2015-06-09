<?php

namespace mj4444\yii2gii\generators\model;

use yii\gii\CodeFile;

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
    public $timestampBehaviorValueString = 'null';
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
                $replaceStr = "class {$class}Base extends \\{$baseClass}\n";
                $content = str_replace($searchStr, $replaceStr, $file->content);
                if (isset($this->classesConsts[$class])) {
                    if (($i = strpos($content, "\n{")) !== false) {
                        $classConsts = $this->classesConsts[$class];
                        foreach ($classConsts as $key => &$value) {
                            $value = "    const {$key} = '{$value}';\n";
                        }
                        $content = substr_replace($content, implode('', $classConsts)."\n", $i + 3, 0);
                    }
                }
                if (($i = strrpos($content, "\n}")) !== false) {
                    $addContent = "";
                    if (isset($this->timestampTables[$class])) {
                        $createdAtAttribute = isset($this->timestampTables[$class]['create']) ? "'".$this->timestampTables[$class]['create']."'" : 'false';
                        $updatedAtAttribute = isset($this->timestampTables[$class]['update']) ? "'".$this->timestampTables[$class]['update']."'" : 'false';
                        $addContent .= "

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => \\yii\\behaviors\\TimestampBehavior::className(),
                'createdAtAttribute' => {$createdAtAttribute},
                'updatedAtAttribute' => {$updatedAtAttribute},
                'value' => $this->timestampBehaviorValueString
            ]
        ];
    }";
                    }
                    if (isset($this->classesEnumValues[$class])) {
                        $enumValues = [];
                        foreach ($this->classesEnumValues[$class] as $key => $value) {
                            if (!$value) {
                                $enumValues[] = "'{$key}' => []'";
                            } else {
                                $enumValues[] = "'{$key}' => [self::".implode(", self::", $value)."]";
                            }
                        }
                        $enumValues = "\$consts = [\n".
                            "            ".implode(",\n            ", $enumValues)."\n".
                            "        ];\n".
                            "        if (is_null(\$field)) {\n".
                            "            return \$consts;\n".
                            "        }\n".
                            "        return isset(\$const[\$field]) ? \$const[\$field] : [];";
                    } else {
                        $enumValues = "return [];";
                    }
                    $addContent .= "

    public function enumValues(\$field = null)
    {
        {$enumValues}
    }";
                    $content = substr_replace($content, $addContent,  $i, 0);
                }
                $_files[] = new CodeFile("{$dirname}/{$class}Base.php", $content);
                $params = [
                    'header' => substr($file->content, 0, $searchStrPos),
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
                $_files[] = $file;
            }
        }
        return $_files;
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
        $rules = parent::generateRules($table);
        foreach ($table->columns as $column) {
            if (isset($column->enumValues) && is_array($column->enumValues)) {
                $enumConstants = [];
                foreach ($column->enumValues as $enumValue) {
                    $constName = strtoupper("{$column->name}_{$enumValue}");
                    $enumConstants[] = $constName;
                    $this->classesConsts[$className][$constName] = $enumValue;
                }
                $this->classesEnumValues[$className][$column->name] = $enumConstants;
                if (strncasecmp($column->dbType, 'enum', 4) == 0) {
                    $rules[] = "[['{$column->name}'], 'in', 'range' => [self::".implode(", self::", array_keys($this->classesConsts[$className]))."]]";
                }
            }
        }
        return $rules;
    }
}
