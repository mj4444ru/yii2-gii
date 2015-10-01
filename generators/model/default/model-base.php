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

/*
    public function __get($name)
    {
        if (!substr_compare($name, 'Array', -5, 5)) {
            $value = parent::__get(substr($name, 0, -5));
            return $value && is_string($value) ? explode(',', $value) : [];
        }
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (!substr_compare($name, 'Array', -5, 5)) {
            parent::__set(substr($name, 0, -5), is_array($value) ? implode(',', $value) : '');
        } else {
            parent::__set($name, $value);
        }
    }
*/

<?= $content ?>
}
