<?php
/* @var $this yii\web\View */
/* @var $header string file header */
/* @var $className string class name */
/* @var $content string class content */

echo $header;
?>
class <?= $className ?> extends Base<?= $className ?>

{
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            // add additional translations
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            // add additional rules
        ]);
    }

<?= $content ?>
}
