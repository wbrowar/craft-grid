<?php
/**
 * Grid plugin for Craft CMS 3.x
 *
 * Content manage CSS grids for matrix and relation fields.
 *
 * @link      http://wbrowar.com
 * @copyright Copyright (c) 2018 Will Browar
 */

namespace wbrowar\grid\twigextensions;

use wbrowar\grid\Grid;

/**
 * @author    Will Browar
 * @package   Grid
 * @since     1.0.0
 */
class GridItemNode extends \Twig_Node
{
    // Public Methods
    // =========================================================================

    /**
     * @param \Twig_Compiler $compiler
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("if (array_key_exists('_key',\$context)) {\n")
            ->write("echo " . Grid::class . "::\$plugin->grid->renderGridItemNodeOpen(\$context['_key'],");

            if ($this->hasNode('arguments')) {
                $compiler
                    ->subcompile($this->getNode('arguments'));
            } else {
                $compiler
                    ->raw("null");
            }

        $compiler
            ->write(");\n")
            ->subcompile($this->getNode('body'))
            ->write("echo " . Grid::class . "::\$plugin->grid->renderGridItemNodeClose();\n")
            ->write("}\n");
    }
}
