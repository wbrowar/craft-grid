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

use craft\elements\MatrixBlock;
use craft\events\ModelEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\fields\Matrix;
use craft\web\View;
use wbrowar\grid\services\Grid as GridService;
use wbrowar\grid\twigextensions\GridTwigExtension;
use wbrowar\grid\variables\GridVariable;
use wbrowar\grid\fields\Grid as GridField;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
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
    public $schemaVersion = '1.0.0';

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

        Craft::info(
            Craft::t(
                'grid',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

}
