<?php

namespace mj4444\yii2gii\generators\model;

use yii\gii\CodeFile;

class Generator extends \yii\gii\generators\model\Generator
{
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
        return 'mj4444 Model Generator';
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
                $file1 = new CodeFile(
                    "{$dirname}/{$class}Base.php",
                    str_replace($searchStr, $replaceStr, $file->content)
                );
                $params = [
                    'header' => substr($file->content, 0, $searchStrPos),
                    'className' => $class,
                ];
                $file2 = new CodeFile(
                    "{$dirname}/{$class}.php",
                    $this->render('model-base.php', $params)
                );
                $_files[] = $file1;
                $_files[] = $file2;
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
}
