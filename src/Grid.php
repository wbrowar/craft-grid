<?php
/**
 * Grid plugin for Craft CMS 3.x
 *
 * Content manage CSS grids for matrix and relation fields.
 *
 * @link      http://wbrowar.com
 * @copyright Copyright (c) 2018 Will Browar
 */

namespace wbrowar\grid;

use craft\web\View;
use wbrowar\grid\services\GridService;
use wbrowar\grid\twigextensions\GridTwigExtension;
use wbrowar\grid\variables\GridVariable;
use wbrowar\grid\fields\Grid as GridField;

use Craft;
use craft\base\Plugin;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;

use yii\base\Event;

/**
 * Class Grid
 *
 * @author    Will Browar
 * @package   Grid
 * @since     1.0.0
 *
 * @property  GridService $grid
 */
class Grid extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Grid
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.2.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Add in our Twig extensions
        Craft::$app->view->registerTwigExtension(new GridTwigExtension());

        if (Craft::$app->getView()->getTemplateMode() === View::TEMPLATE_MODE_CP) {
            Event::on(View::class,  View::EVENT_BEFORE_RENDER_TEMPLATE, function() {
                Craft::$app->getView()->registerJs('window.WBJsDevMode = window.WBJsDevMode || ' . (Craft::$app->getConfig()->getGeneral()->devMode ? 'true' : 'false') . ';', 1);
            });
        }

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = GridField::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('grid', GridVariable::class);
            }
        );

        $this->log('Grid plugin loaded', 'info', __METHOD__);
    }

    /*
     * Helper method used for logging to the Yii Debugger
     */
    public function log($message, $level, $category)
    {
        $message = Craft::t('grid', $message, ['name' => Grid::$plugin->name]);

        switch ($level) {
            case 'debug':
                Craft::debug($message, $category);
                break;
            case 'error':
                Craft::error($message, $category);
                break;
            case 'info':
                Craft::info($message, $category);
                break;
            case 'warning':
                Craft::warning($message, $category);
                break;
        }
    }

    // Protected Methods
    // =========================================================================

}
