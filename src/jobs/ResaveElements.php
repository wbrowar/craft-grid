<?php
/**
 * Grid plugin for Craft CMS 3.x
 *
 * Content manage CSS grids for matrix and relation fields.
 *
 * @link      http://wbrowar.com
 * @copyright Copyright (c) 2019 Will Browar
 */

namespace wbrowar\grid\jobs;

use craft\base\Element;
use wbrowar\grid\Grid;

use Craft;
use craft\queue\BaseJob;

/**
 * ResaveElements job
 *
 * use wbrowar\grid\jobs\ResaveElements as ResaveElementsJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new ResaveElementsJob([
 *     'description' => Craft::t('grid', 'This overrides the default description'),
 *     'someAttribute' => 'someValue',
 * ]));
 *
 * More info: https://github.com/yiisoft/yii2-queue
 *
 * @author    Will Browar
 * @package   Grid
 * @since     1.0.0
 */
class ResaveElements extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * Element ID
     *
     * @var int
     */
    public $elementId = null;

    /**
     * Field ID
     *
     * @var int
     */
    public $fieldHandle = null;

    /**
     * New Min Widths
     *
     * @var array
     */
    public $newMinWidths = [];

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * You don't need any steps or any other special logic handling, just do the
     * jobs that needs to be done here.
     *
     * More info: https://github.com/yiisoft/yii2-queue
     */
    public function execute($queue)
    {
        $saved = Grid::$plugin->grid->resaveElementForNewMinWidths($this->elementId, $this->fieldHandle, $this->newMinWidths);

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
        return Craft::t('grid', 'ResaveElements');
    }
}
