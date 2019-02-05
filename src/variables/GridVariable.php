<?php
/**
 * Grid plugin for Craft CMS 3.x
 *
 * Content manage CSS grids for matrix and relation fields.
 *
 * @link      http://wbrowar.com
 * @copyright Copyright (c) 2018 Will Browar
 */

namespace wbrowar\grid\variables;

use wbrowar\grid\Grid;

use Craft;

/**
 * @author    Will Browar
 * @package   Grid
 * @since     1.0.0
 */
class GridVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */
    public function value(array $target, array $grid, array $args=[])
    {
        return Grid::$plugin->grid->getGridValue($target, $grid, $args);
    }
}
