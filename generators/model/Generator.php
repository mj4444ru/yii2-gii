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
     * @var string[]
     * Index - class name, Value - table name
     */
    public $timestampTables = [];

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
                if (isset($this->timestampTables[$class])) {
                    if (($i = strrpos($content, "\n}")) !== false) {
                        $content = substr_replace($content, "

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className()
        ];
    }", $i, 0);
                    }
                }
                $_files[] = new CodeFile("{$dirname}/{$class}Base.php", $content);
                $params = [
                    'header' => substr($file->content, 0, $searchStrPos),
                    'className' => $class,
                ];
                $_files[] = new CodeFile("{$dirname}/{$class}.php", $this->render('model-base.php', $params));
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
        if (isset($table->columns[$this->createdAtAttribute])) {
            $this->timestampTables[$this->generateClassName($table->name)] = $table->name;
            $table->columns[$this->createdAtAttribute]->autoIncrement = true;
        }
        if (isset($table->columns[$this->updatedAtAttribute])) {
            $this->timestampTables[$this->generateClassName($table->name)] = $table->name;
            $table->columns[$this->updatedAtAttribute]->autoIncrement = true;
        }
        return parent::generateRules($table);
    }
}
