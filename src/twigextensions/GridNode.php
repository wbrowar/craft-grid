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
class GridNode extends \Twig_Node
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
            ->write("echo " . Grid::class . "::\$plugin->grid->renderGridNodeOpen(")
            ->subcompile($this->getNode('field'))
            ->raw(",");

        if ($this->hasNode('target')) {
            $compiler
                ->subcompile($this->getNode('target'));
        } else {
            $compiler
                ->raw("null");
        }

        $compiler
            ->raw(",'" . $this->getAttribute('children') . "',");

        if ($this->hasNode('arguments')) {
            $compiler
                ->subcompile($this->getNode('arguments'));
        } else {
            $compiler
                ->raw("null");
        }

        $compiler
            ->raw(");\n")
            ->write("\$context['" . $this->getAttribute('children') . "'] = " . Grid::class . "::\$plugin->grid->getChildrenValue();\n")
            ->subcompile($this->getNode('body'))
            ->write("unset(\$context['" . $this->getAttribute('children') . "']);\n")
            ->write("echo " . Grid::class . "::\$plugin->grid->renderGridNodeClose();\n");
    }
}
