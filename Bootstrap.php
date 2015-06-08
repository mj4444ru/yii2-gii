<?php

namespace mj4444\yii2gii;

use yii\base\Application;
use yii\base\BootstrapInterface;

/**
 * Class Bootstrap
 */
class Bootstrap implements BootstrapInterface
{

    /**
     * Bootstrap method to be called during application bootstrap stage.
     *
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if ($app->hasModule('gii')) {

            if (!isset($app->getModule('gii')->generators['mjDoubleModel'])) {
                $app->getModule('gii')->generators['mjDoubleModel'] = 'mj4444\yii2gii\generators\model\Generator';
            }
        }
    }
}