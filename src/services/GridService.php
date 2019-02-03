<?php
/**
 * Grid plugin for Craft CMS 3.x
 *
 * Content manage CSS grids for matrix and relation fields.
 *
 * @link      http://wbrowar.com
 * @copyright Copyright (c) 2018 Will Browar
 */

namespace wbrowar\grid\services;

use craft\helpers\Json;
use craft\helpers\StringHelper;

use Craft;
use craft\base\Component;

/**
 * @author    Will Browar
 * @package   Grid
 * @since     1.0.0
 */
class GridService extends Component
{
    private $_render = [];

    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
    public function getChildrenValue()
    {
        $value = [];
        if (!empty($this->_render['field']) && !empty($this->_render['target'])) {
            if (($this->_render['field'] ?? false) && is_array($this->_render['field']) && ($this->_render['target'] ?? false) && is_array($this->_render['target'])) {
                foreach ($this->_render['target'] as $item) {
                    $value[] = [
                        'content' => $item,
                    ];
                }
            }
        }

        return $value;
    }

    /*
     * @return string
     */
    public function registerGridCss($args)
    {
        if (!empty($args['field'])
            && !empty($args['selector'])
            && !empty($args['target'])
            && !empty($args['value'])) {
            // BEGIN @supports
            $css = '@supports (--custom:property) {';

//            Craft::dd($args['value']);

                for ($i=0; $i<count($args['field']['layout']['breakpoints']); $i++) {
                    $breakpoint = $args['field']['layout']['breakpoints'][$i];
                    $minWidth = $breakpoint['minWidth'];
                    $maxWidth = $i<count($args['field']['layout']['breakpoints']) ? ($args['field']['layout']['breakpoints'][$i + 1] ?? false) ? $args['field']['layout']['breakpoints'][$i + 1]['minWidth'] - 1 : null : null;
                    if ($minWidth === 0) {
                        $css .= ' .' . $args['selector'] . ' {';
                            $css .= 'display: grid;';
                            $css .= $this->_gridCssForBreakpoint($breakpoint);
                        $css .= '}';

                        if ($args['preview'] ?? false) {
                            $css .= ' .' . $args['selector'] . '__preview { display: flex; align-items: center; justify-content: center; height: 100%; border: 1px dashed rgba(0, 0, 0, .3); box-sizing: border-box; }';
                            for ($j=0; $j<count($args['target']['items']); $j++) {
                                $index = $j + 1;
                                $css .= ' .'.$args['selector'].'__preview:nth-child(' . $index . ') { background-color: hsl(' . ($index * 15) . ', 100%, 32%); background-color: hsla(' . ($index * 15) . ', 100%, 46%, .7); }';
                            }
                        }

                        if ($maxWidth) {
                            $css .= ' @media (max-width: ' . $maxWidth . 'px) {';
                        }
                            $css .= $this->_gridItemCssForBreakpoint($args['target']['items'], $args['value']['id' . 0], $args['selector']);
                        if ($maxWidth) {
                            $css .= '}';
                        }
                    } else {
                        $maxWidthQuery = $maxWidth ?? false ? ' and (max-width: ' . $maxWidth . 'px)' : '';
                        $css .= ' @media (min-width: ' . $minWidth . 'px)' . $maxWidthQuery . ' {';
                            $css .= ' .' . $args['selector'] . ' {';
                                $css .= $this->_gridCssForBreakpoint($breakpoint);
                            $css .= '}';
                            $css .= $this->_gridItemCssForBreakpoint($args['target']['items'], $args['value']['id' . $minWidth], $args['selector']);
                        $css .= '}';
                    }
                }

            // END @supports
            $css .= '}';

            Craft::$app->getView()->registerCss($css);
        }
    }

    /*
     * @return string
     */
    public function renderGridItemNodeOpen($index, $arguments)
    {
        $this->_render['child'] = [
            'arguments' => $arguments,
            'element' => 'div',
            'selector' => '',
        ];

        $attributes = '';
        $classes = '';

        // Process arguments from Twig Parser
        if (!empty($this->_render['child']['arguments'])) {
            $arguments = $this->_render['child']['arguments'];

            if ($arguments['attributes'] ?? false) {
                $attributes = $arguments['attributes'];
            }
            if ($arguments['classes'] ?? false) {
                $classes = $arguments['classes'];
            }
            if ($arguments['element'] ?? false) {
                $this->_render['child']['element'] = $arguments['element'];
            }
        }

        $selector = $this->_render['parent']['selector'] . '__item--' . $index;

        if ($this->_render['preview']) {
            $classes .= ' ' . $this->_render['parent']['selector'] . '__preview';
        }

        return '<' . $this->_render['child']['element'] . ' class="' . $selector . (!empty($classes) ? ' ' . $classes : '') . '"' . (!empty($attributes) ? ' ' . $attributes : '') . '>';
    }

    /*
     * @return string
     */
    public function renderGridItemNodeClose()
    {
        $element = $this->_render['child']['element'];

        $this->_render['child'] = [];

        unset($this->_render['child']);

        return '</' . $element . '>';
    }

    /*
     * @return string
     */
    public function renderGridNodeOpen($field, $target, $children, $arguments)
    {
        // Set defaults and reset _render
        $this->_render = [
            'children' => $children,
            'field' => $field,
            'preview' => false,
            'target' => $target,
        ];
        $this->_render['parent'] = [
            'arguments' => $arguments,
            'element' => 'div',
            'selector' => '',
        ];

        if (($this->_render['field'] ?? false)
            && ($this->_render['field']['field'] ?? false)
            && ($this->_render['field']['field']['handle'] ?? false)
            && ($this->_render['field']['target'] ?? false)
            && ($this->_render['field']['target']['handle'] ?? false)) {

            // Figure out which element is the target
            if (empty($target)) {
                $matchedElement = Craft::$app->urlManager->getMatchedElement();
                if ($matchedElement ?? false) {
                    $fieldValue = $matchedElement->getFieldValue($this->_render['field']['target']['handle']);
                    $this->_render['target'] = !is_array($fieldValue) ? $fieldValue->all() : $fieldValue;
                }
            }

            // Set grid selector
            // This is also suffixed later for child items
            $this->_render['parent']['selector'] = 'grid__' . StringHelper::toSnakeCase($this->_render['field']['field']['handle']) . '__' . StringHelper::toSnakeCase($this->_render['field']['target']['handle']);
//            Craft::dd($this->_render['parent']['selector']);
        }


        // Process arguments from Twig Parser
        if (!empty($this->_render['parent']['arguments'])) {
            $arguments = $this->_render['parent']['arguments'];

            if ($arguments['attributes'] ?? false) {
                $attributes = $arguments['attributes'];
            }
            if ($arguments['classes'] ?? false) {
                $classes = $arguments['classes'];
            }
            if ($arguments['element'] ?? false) {
                $this->_render['parent']['element'] = $arguments['element'];
            }
            if ($arguments['preview'] ?? false) {
                $this->_render['preview'] = $arguments['preview'];
            }
        }

        return '<' . $this->_render['parent']['element'] . ' class="' . $this->_render['parent']['selector'] . (!empty($classes) ? ' ' . $classes : '') . '"' . (!empty($attributes) ? ' ' . $attributes : '') . '>';
    }

    /*
     * @return string
     */
    public function renderGridNodeClose()
    {
        $this->registerGridCss([
            'field' => $this->_render['field']['field'],
            'preview' => $this->_render['preview'],
            'selector' => $this->_render['parent']['selector'],
            'target' => $this->_render['field']['target'],
            'value' => $this->_render['field']['value'],
        ]);

        $element = $this->_render['parent']['element'];

        $this->_render['parent'] = [];

        unset($this->_render['parent']);

        return '</' . $element . '>';
    }

    /*
     * @return bool
     */
    public function resaveElementForNewMinWidths($elementId, $fieldHandle, $newMinWidths)
    {
        $element = Craft::$app->getElements()->getElementById($elementId);
        $fieldValue = $element->getFieldValue($fieldHandle);
        $saveElement = false;

        if ($fieldValue['value'] ?? false) {
            foreach ($newMinWidths as $widthMap) {
                if ($fieldValue['value']['id' . $widthMap['old']] ?? false) {
                    $fieldValue['value']['id' . $widthMap['new']] = $fieldValue['value']['id' . $widthMap['old']];
                    unset($fieldValue['value']['id' . $widthMap['old']]);
                    $saveElement = true;
                }
            }
        }

        if ($saveElement) {
            $updatedGridFields = [$fieldHandle => Json::encode($fieldValue)];
            $element->setFieldValues($updatedGridFields);
            $saved = Craft::$app->getElements()->saveElement($element);

            return $saved;
        }

        return false;
    }

    /*
     * @return string
     */
    private function _gridItemCssForBreakpoint($items, $value, $selector)
    {
        $css = '';
        for ($i=0; $i<count($items); $i++) {
            $itemSelector = ' .' . $selector . '__item--' . $i;
            if (!empty($value['id' . $items[$i]['id']]) && $items[$i]['status'] === 'enabled') {
                $item = $value['id' . $items[$i]['id']];
                $css .= $itemSelector . ' {';
                    $css .= 'grid-column: ' . $item['columnStart'] . ' / ' . $item['columnEnd'] . ';';
                    $css .= 'grid-row: ' . $item['rowStart'] . ' / ' . $item['rowEnd'] . ';';
                $css .= '}';
            } else {
                $css .= $itemSelector . ' {';
                    $css .= 'display: none;grid-column: 1 / 2;grid-row: 1 / 2;visibility: hidden;opacity: 0;';
                $css .= '}';
            }
        }
        return $css;
    }

    /*
     * @return string
     */
    private function _gridCssForBreakpoint($breakpoint)
    {
        $css = '';
        switch ($breakpoint['modeColumns']) {
            case 'auto':
                $css .= 'grid-auto-columns: ' . $breakpoint['autoColumns'] . ';';
                break;
            case 'fixed':
                $css .= 'grid-template-columns: ' . join(' ', $breakpoint['columns']) . ';';
                break;
        }
        switch ($breakpoint['modeRows']) {
            case 'auto':
                $css .= 'grid-auto-rows: ' . $breakpoint['autoRows'] . ';';
                break;
            case 'fixed':
                $css .= 'grid-template-rows: ' . join(' ', $breakpoint['rows']) . ';';
                break;
        }
        return $css;
    }
}
