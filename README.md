yii2-gii
===========

Extended models for Gii, the code generator of Yii2 Framework

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

    php composer.phar require --dev --prefer-dist mj4444/yii2-gii:dev-master

The generators are registered automatically in the application bootstrap process, if the Gii module is enabled

Usage
-----

Visit your application's Gii (eg. `index.php?r=gii` and choose one of the generators from the main menu screen.

For basic usage instructions see the [Yii2 Guide section for Gii](http://www.yiiframework.com/doc-2.0/guide-tool-gii.html).

```php
if (!YII_ENV_TEST) {
    Yii::$container->set('mj4444\yii2gii\generators\model\Generator', ['requiredStrict' => true]);
}
```

Differences from standard generator
-----------------------------------

- Additional class of models for user code
- Autogenerate validators for default values
- Autogenerate validators for fields enum (possible partial/full deactivation)
- Autogenerate constants for fields and enum set
- Autogenerate behaviors for models containing fields handled TimestampBehavior
- Table 'migration' is excluded from autogeneration (configurable)
- Can configure ignore list tables
- Completely redesigned creating names for relations

Отличия от стандартного генератора
----------------------------------

- Дополнительный класс модели для пользовательского кода
- Автогенерация валидаторов для значений по умолчанию
- Автогенерация валидаторов для полей enum (возможно частичное/полное отключение)
- Автогенерация констант для полей enum и set
- Автогенерация behaviors для моделей содержащих поля обрабатываемые TimestampBehavior
- Таблица 'migration' исключается из автогенерации (настраивается)
- Возможно настроить список игнорируемых таблиц
- Полностью переработано создание имён для связей

Links
-----

- [GitHub](https://github.com/mj4444ru/yii2-gii.git)
- [Packagist](https://packagist.org/packages/mj4444/yii2-gii)
- [Yii Extensions]()
