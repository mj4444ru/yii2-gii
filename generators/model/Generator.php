<?php

namespace mj4444\yii2gii\generators\model;

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
        // Тут дополняем модели
        return $files;
    }
}
