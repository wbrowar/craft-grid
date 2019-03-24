<?php
/**
 * Grid plugin for Craft CMS 3.x
 *
 * Content manage CSS grids for matrix and relation fields.
 *
 * @link      http://wbrowar.com
 * @copyright Copyright (c) 2018 Will Browar
 */

namespace wbrowar\grid\fields;

use wbrowar\grid\Grid as GridPlugin;
use wbrowar\grid\assetbundles\grid\GridAsset;
use wbrowar\grid\assetbundles\gridfield\GridFieldAsset;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use yii\db\Schema;
use craft\helpers\Json;

/**
 * @author    Will Browar
 * @package   Grid
 * @since     1.0.0
 */
class Grid extends Field
{
    // Public Properties
    // =========================================================================

    /**
     * The layout information as it is generated (via Javascript)
     * for different breakpoints
     *
     * @var array
     */
    public $layout = '';

    /**
     * The handle of the field that grid is laying out
     *
     * @var string
     */
    public $targetFieldId = '';

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('grid', 'Grid');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules = array_merge($rules, [
            [['targetFieldId'], 'string'],
        ]);
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        $updatedGridFields = [];

        $fieldHandle = $this->handle;
        $fieldValue = $element->getFieldValue($fieldHandle);

        // Re-save fields that use newX when adding content
        if ($fieldValue['target'] ?? false) {
            if ($fieldValue['target']['id'] !== '__none__') {
                $targetFieldId = $fieldValue['target']['id'];
                $targetField = Craft::$app->getFields()->getFieldById($targetFieldId);
                if ($element['ownerId'] ?? false) {
                    $targetElement = Craft::$app->getElements()->getElementById($element['ownerId']);
                } else {
                    $targetElement = $element;
                }

                $targetValue = $targetElement->getFieldValue($targetField->handle);

                switch ($targetValue->elementType) {
                    case 'craft\\elements\\MatrixBlock':
                        if ($fieldValue['target']['items'] ?? false) {
                            // Store all block IDs
                            $targetValueIds = [];
                            foreach ($targetValue->all() as $block) {
                                $targetValueIds[] = strval($block->id);
                            }

                            // Map all newX IDs
                            $fieldTargetItemIds = [];
                            for ($j=0; $j<count($fieldValue['target']['items']); $j++) {
                                // Store ID
                                $fieldTargetItemIds[] = strval($fieldValue['target']['items'][$j]['id']);
                            }

                            // Map all newX IDs to their new IDs
                            $xToId = [];
                            for ($j=0; $j<count($fieldTargetItemIds); $j++) {
                                if (substr($fieldTargetItemIds[$j], 0, 3) == 'new') {
                                    $xToId['id' . $fieldTargetItemIds[$j]] = 'id' . $targetValueIds[$j];
                                }
                            }

                            if (count(array_keys($xToId)) > 0) {
                                // Iterate through field value and replace newX IDs with new ID
                                foreach ($fieldValue['value'] as &$breakpoint) {
                                    // Copy each of the items that need to be replaced
                                    foreach (array_keys($breakpoint) as $item) {
                                        if ($xToId[$item] ?? false) {
                                            $breakpoint[$xToId[$item]] = $breakpoint[$item];
    //                                                unset($breakpoint[$item]);
                                        }
                                    }
                                }
                                // Iterate through target and replace newX IDs with new ID
                                for ($j=0; $j<count($fieldValue['target']['items']); $j++) {
                                    if (substr($fieldValue['target']['items'][$j]['id'], 0, 3) == 'new') {
                                        $fieldValue['target']['items'][$j]['id'] = intval(substr($xToId['id' . $fieldValue['target']['items'][$j]['id']], 2));
                                    }
                                }

                                $updatedGridFields[$fieldHandle] = Json::encode($fieldValue);
                            }
                        }
                        break;
                }

                if (!empty($updatedGridFields)) {
                    $element->setFieldValues($updatedGridFields);
                    Craft::$app->getElements()->saveElement($element);
                }
            }
        }

        parent::afterElementSave($element, $isNew);
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(GridAsset::class);

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Get target info
        if ($this->targetFieldId === '__none__') {
            $target = [
                'class' => 'index',
                'handle' => 'index',
                'id' => '__none__',
                'name' => 'Grid Items'
            ];
        } else {
            $targetFieldId = intval(substr($this->targetFieldId, 2));
            $targetField = Craft::$app->getFields()->getFieldById($targetFieldId);

            if ($targetField ?? false) {
                $target = [
                    'class' => get_class($targetField),
                    'handle' => $targetField->handle,
                    'id' => $targetFieldId,
                    'name' => $targetField->name,
                ];
            } else {
                return 'The field with the ID, ' . $this->targetFieldId . ', is not available. Please select another target field.';
            }
        }

        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id' => $id,
            'name' => $this->handle,
            'namespace' => $namespacedId,
            'prefix' => Craft::$app->getView()->namespaceInputId(''),
            'field' => [
                'layout' => Json::decodeIfJson($this->layout),
            ],
            'target' => $target,
            'value' => $value,
        ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("$('#{$namespacedId}-field').GridField(" . $jsonVars . ");");

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'grid/field/Grid_input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(GridAsset::class);

        $fieldId = date('U');

        $jsonVars = [
            'fieldId' => $fieldId,
            'layout' => Json::decodeIfJson($this->layout),
        ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("$('[data-grid-field=\"" . $fieldId . "\"]').GridFieldSettings(" . $jsonVars . ");");

        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'grid/field/Grid_settings',
            [
                'field' => $this,
                'fieldId' => $fieldId,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        // Get value for grid field
        if (is_string($value) && !empty($value)) {
            $value = Json::decodeIfJson($value);

            if (!is_array($value)) {
                $value = [];
            }

            // Set field information
            $value['field'] = [
                'handle' => $this->handle,
                'layout' => Json::decodeIfJson($this->layout),
            ];

            return $value;
        }

        GridPlugin::$plugin->log('Cannot get value from Grid field: ' . $this->handle . ' in element: ' . $element->getId(), 'error', __METHOD__);
        return 'error';
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        return parent::serializeValue($value, $element);
    }
}
