<?php
/**
 * Grid plugin for Craft CMS 3.x
 *
 * Content manage CSS grids for matrix and relation fields.
 *
 * @link      http://wbrowar.com
 * @copyright Copyright (c) 2018 Will Browar
 */

namespace wbrowar\grid\assetbundles\grid;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Will Browar
 * @package   Grid
 * @since     1.0.0
 */
class GridAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@wbrowar/grid/assetbundles/grid/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/grid.1.0.0.js',
        ];

        $this->css = [
            'css/grid.1.0.0.css',
        ];

        parent::init();
    }
}
