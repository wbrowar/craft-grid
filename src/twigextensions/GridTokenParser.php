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

use wbrowar\grid\twigextensions\GridNode;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @author    Will Browar
 * @package   Grid
 * @since     1.0.0
 */
class GridTokenParser extends AbstractTokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @param \Twig_Token $token
     *
     * @return \wbrowar\grid\twigextensions\GridNode
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [];
        $attributes = [];

        // Get grid field or grid target
        $fieldOrTarget = $this->parser->getExpressionParser()->parseExpression();

        // If 'using' token is found, set 'target' and 'field' value
        // Or just set 'field' with value
        if ($stream->test(\Twig_Token::NAME_TYPE, 'using')) {
            $stream->next();
            $nodes['field'] = $this->parser->getExpressionParser()->parseExpression();
            $nodes['target'] = $fieldOrTarget;
        } else {
            $nodes['field'] = $fieldOrTarget;
        }

        // Set children variable
        $stream->expect(\Twig_Token::NAME_TYPE, 'as');
        $children = $this->parser->getExpressionParser()->parseAssignmentExpression();
        $attributes['children'] = $children->getNode(0)->getAttribute('name');

        // Set variables
        if ($stream->test(\Twig_Token::NAME_TYPE, 'with')) {
            $stream->next();
            $nodes['arguments'] = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        $nodes['body'] = $this->parser->subparse([$this, 'decideGridEnd'], true);
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        return new GridNode($nodes, $attributes, $lineno, $this->getTag());
    }
    /**
     * @return string
     */
    public function getTag()
    {
        return 'grid';
    }
    /**
     * @param \Twig_Token $token
     *
     * @return bool
     */
    public function decideGridEnd(\Twig_Token $token)
    {
        return $token->test('endgrid');
    }
}
