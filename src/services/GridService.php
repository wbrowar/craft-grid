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
    const DEFAULT_FONT_SIZE = 16;

    private $_render = [];

    // Public Methods
    // =========================================================================

    /*
     * Convert pixel-based number to `em` or `rem` units
     *
     * @return string
     */
    public function getCssSize(float $size, string $unit):string
    {
        switch ($unit) {
            case 'em':
                $result = ($size / self::DEFAULT_FONT_SIZE) . 'em';
                break;
            case 'rem':
                $result = ($size / self::DEFAULT_FONT_SIZE) . 'rem';
                break;
            default:
                $result = $size . 'px';
                break;
        }

        return $result;
    }

    /*
     * Check if all items in a target field have been laid out on a grid field
     *
     * @return mixed
     */
    public function getChildIsComplete(array $args)
    {
        $layouts = [];

        if (!empty($args['field'])
            && !empty($args['value'])) {
            // coming soon
            // @TODO check that all target items are liad out
        }

        return $layouts;
    }

    /*
     * Get all of the layouts (min-width) that a grid item is laid out for
     *
     * @return mixed
     */
    public function getChildLayouts(array $args)
    {
        $layouts = [];

        if (!empty($args['field'])
            && !empty($args['id'])
            && !empty($args['value'])) {
            $itemId = 'id' . $args['id'];

            foreach ($args['field']['layout']['widths'] as $width) {
                $widthId = 'id' . $width;

                if ($args['value'][$widthId][$itemId] ?? false) {
                    $layouts[] = $width;
                }
            }
        }

        return $layouts;
    }

    /*
     * Generates CSS for a grid field
     *
     * @return string
     */
    public function getGridCss(array $args)
    {
        if (!empty($args['field'])
            && !empty($args['selector'])
            && !empty($args['target'])
            && !empty($args['value'])) {
            $unit = $args['unit'] ?? 'px';

            // BEGIN @supports
            $css = '@supports (--custom:property) {';

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
                        $css .= ' @media (max-width: ' . $this->getCssSize($maxWidth, $unit) . ') {';
                    }
//                    Craft::dd($breakpoint['notLaidOut']);
                    $css .= $this->_gridItemCssForBreakpoint($args['target']['items'], $args['value']['id' . 0] ?? [], $args['selector'], $breakpoint['notLaidOut'] ?? 'hidden');
                    if ($maxWidth) {
                        $css .= '}';
                    }
                } else {
                    $maxWidthQuery = $maxWidth ?? false ? ' and (max-width: ' . $this->getCssSize($maxWidth, $unit) . ')' : '';
                    $css .= ' @media (min-width: ' . $this->getCssSize($minWidth, $unit) . ')' . $maxWidthQuery . ' {';
                    $css .= ' .' . $args['selector'] . ' {';
                    $css .= $this->_gridCssForBreakpoint($breakpoint);
                    $css .= '}';
                    $css .= $this->_gridItemCssForBreakpoint($args['target']['items'], $args['value']['id' . $minWidth] ?? [], $args['selector'], $breakpoint['notLaidOut'] ?? 'hidden');
                    $css .= '}';
                }
            }

            // END @supports
            $css .= '}';

            return $css;
        }

        return null;
    }

    /*
     * Get all info needed to construct grid from target field and grid field
     *
     * $args can be populated with:
     * dd | dump and die the $value after it is constructed
     * unit | convert media query units to em or rem
     *
     * @return array
     */
    public function getGridValue(array $target, array $grid, array $args=[]):array
    {
        $value = [
            'items' => [],
        ];

        $gridField = $grid['field'];
        $gridTarget = $grid['target'];
        $gridValue = $grid['value'];

        $selector = $this->_gridSelector($gridField['handle'], $gridTarget['handle']);

        // Generate CSS
        $value['css'] = $this->getGridCss([
            'field' => $gridField,
            'selector' => $selector,
            'target' => $gridTarget,
            'unit' => $args['unit'] ?? 'px',
            'value' => $gridValue,
        ]);

        // Populate Grid
        $value['grid'] = [
            'selector' => $selector,
        ];

        // Populate Grid Items
        foreach ($target as $item) {
            $value['items'][] = $this->getGridItemValue($item, $grid);
        }

        // In case you would like to preview the grid info, dump and die the value
        if (Craft::$app->getConfig()->getGeneral()->devMode && ($args['dd'] ?? false)) {
            Craft::dd($value);
        }

        return $value;
    }

    /*
     * Get all info needed to construct grid from target field and grid field
     *
     * $args can be populated with:
     * unit | convert media query units to em or rem
     *
     * @return string
     */
    public function getGridItemValue($item, array $grid, array $args=[]):array
    {
        if (($grid['field'] ?? false) && ($grid['value'] ?? false)) {
            $gridField = $grid['field'];
            $gridValue = $grid['value'];

            return [
                'content' => $item,
    //            'isComplete' => $this->getChildLayouts(['field' => $gridField, 'id' => $item->id ?? null, 'value' => $gridValue]),
                'layouts' => $this->getChildLayouts(['field' => $gridField, 'id' => $item->id ?? null, 'value' => $gridValue]),
            ];
        }

        return [
            'content' => null,
            'layouts' => null,
        ];
    }

    /*
     * Registers CSS for a grid field
     *
     * @return string
     */
    public function registerGridCss(array $args)
    {
        $css = $this->getGridCss($args);

        if (!empty($css)) {
            Craft::$app->getView()->registerCss($css);
        }
    }

    /*
     * When a layout min-width is changed, find all elements that use the grid field and map the old layout values to the new layout
     *
     * @return bool
     */
    public function resaveElementForNewMinWidths(int $elementId, string $fieldHandle, array $newMinWidths)
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

    // Twig Rendering Methods
    // =========================================================================

    /*
     * Create an opening <div GRID>
     * Populate $this->_render
     *
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
            'unit' => 'px',
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
            $this->_render['parent']['selector'] = $this->_gridSelector($this->_render['field']['field']['handle'], $this->_render['field']['target']['handle']);
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
            if ($arguments['unit'] ?? false) {
                $this->_render['unit'] = $arguments['unit'];
            }
        }

        return '<' . $this->_render['parent']['element'] . ' class="' . $this->_render['parent']['selector'] . (!empty($classes) ? ' ' . $classes : '') . '"' . (!empty($attributes) ? ' ' . $attributes : '') . '>';
    }

    /*
     * Close <div GRID>
     *
     * @return string
     */
    public function renderGridNodeClose()
    {
        $this->registerGridCss([
            'field' => $this->_render['field']['field'],
            'preview' => $this->_render['preview'] ?? false,
            'selector' => $this->_render['parent']['selector'],
            'target' => $this->_render['field']['target'],
            'unit' => $this->_render['unit'] ?? 'px',
            'value' => $this->_render['field']['value'],
        ]);

        $element = $this->_render['parent']['element'];

        $this->_render['parent'] = [];

        unset($this->_render['parent']);

        return '</' . $element . '>';
    }

    /*
     * Get value of children context variable
     *
     * @return mixed
     */
    public function getRenderChildrenValue()
    {
        $value = [];
        if (!empty($this->_render['field']) && !empty($this->_render['target'])) {
            if (($this->_render['field'] ?? false) && is_array($this->_render['field']) && ($this->_render['target'] ?? false) && is_array($this->_render['target'])) {

                foreach ($this->_render['target'] as $item) {
                    $value[] = $this->getGridItemValue($item, $this->_render['field']);
                }
            }
        }

        return $value;
    }

    /*
     * Create opening <div GRID ITEM>
     *
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

        $selector = $this->_gridItemSelector($this->_render['parent']['selector'], $index);

        if ($this->_render['preview']) {
            $classes .= ' ' . $this->_render['parent']['selector'] . '__preview';
        }

        return '<' . $this->_render['child']['element'] . ' class="' . $selector . (!empty($classes) ? ' ' . $classes : '') . '"' . (!empty($attributes) ? ' ' . $attributes : '') . '>';
    }

    /*
     * Close <div GRID ITEM>
     *
     * @return string
     */
    public function renderGridItemNodeClose()
    {
        $element = $this->_render['child']['element'];

        $this->_render['child'] = [];

        unset($this->_render['child']);

        return '</' . $element . '>';
    }

    // Private Methods
    // =========================================================================

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

    /*
     * @return string
     */
    private function _gridItemCssForBreakpoint($items, $value, $selector, $notLaidOut)
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
                if ($notLaidOut === 'hidden') {
                    $css .= $itemSelector . ' {';
                        $css .= 'display: none;grid-column: 1 / 2;grid-row: 1 / 2;visibility: hidden;opacity: 0;';
                    $css .= '}';
                }
            }
        }
        return $css;
    }

    /*
     * @return string
     */
    private function _gridSelector($fieldHandle, $targetHandle):string
    {
        return 'grid__' . StringHelper::toSnakeCase($fieldHandle) . '__' . StringHelper::toSnakeCase($targetHandle);
    }

    /*
     * @return string
     */
    private function _gridItemSelector($gridSelector, $index):string
    {
        return $gridSelector . '__item--' . $index;
    }
}
