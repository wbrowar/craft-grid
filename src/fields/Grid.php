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

use craft\helpers\ElementHelper;
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

    /**
     * Temporary list of the ids of the Target Value Items
     *
     * @var array
     */
    public $onSaveTargetItems = [];

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
    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        $this->_updateOnSaveTargetItems($element);

        return parent::beforeElementSave($element, $isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew)
    {
        $this->_updateTargetIds($element);
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
            'value' => $this->_getInputValue($element, $target, $value),
        ];

        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("$('#" . $namespacedId . "-field').GridField(" . $jsonVars . ");");

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
        }

        if (empty($value)) {
            $value = [];
        }

        // Set field information
        $value['field'] = [
            'handle' => $this->handle,
            'layout' => Json::decodeIfJson($this->layout),
        ];

        return $value;
    }


    private function _getInputValue(ElementInterface $element, $target, $value)
    {
        if ($target['class'] == 'craft\\fields\\Matrix') {
            // Get IDs of matrix blocks
            $targetValue = $element->getFieldValue($target['handle']);
            $targetIds = $targetValue->ids();

            // Get IDs from grid field layout and compare them to matrix block IDs
            $unmatchedIds = [];
            if (($value['target'] ?? false) && ($value['target']['items'] ?? false)) {
                for ($i=0; $i<count($value['target']['items']); $i++) {
                  if (!empty($targetIds[$i])) {
                    $targetId = intval($targetIds[$i]);
                    $valueId = $value['target']['items'][$i]['id'];

                    if ($targetId != $valueId) {
                        $unmatchedIds[] = [
                            'target' => $targetId,
                            'value' => $valueId,
                        ];
                        $value['target']['items'][$i]['id'] = $targetId;
                    }
                  }
                }

                // Loop through grid values and change IDs if needed
                if (!empty($unmatchedIds) && $value['value']) {
                    foreach ($value['value'] as &$breakpoint) {
                        foreach ($unmatchedIds as $id) {
                            if ($breakpoint['id' . $id['value']] ?? false) {
                                $breakpoint['id' . $id['target']] = $breakpoint['id' . $id['value']];
                                unset($breakpoint['id' . $id['value']]);
                            }
                        }
                    }
                }
            }
//            Craft::dd($value);
        }

        return $value;
    }
    private function _updateOnSaveTargetItems($element)
    {
        $this->onSaveTargetItems = [];

        $fieldHandle = $this->handle;
        $fieldValue = $element->getFieldValue($fieldHandle);

        if ($fieldValue['target'] ?? false) {
            if ($fieldValue['target']['id'] !== '__none__') {
                $targetFieldId = $fieldValue['target']['id'];
                $targetField = Craft::$app->getFields()->getFieldById($targetFieldId);
                $targetElement = ElementHelper::rootElement($element);
                $targetValue = $targetElement->getFieldValue($targetField->handle);

                foreach ($targetValue->all() as $item) {
                    $this->onSaveTargetItems[] = $item['id'] ?? '__no_id__';
                }
            }
        }
    }
    private function _updateTargetIds($element)
    {
        $fieldHandle = $this->handle;
        $fieldValue = $element->getFieldValue($fieldHandle);

        // Re-save fields that use newX when adding content
        if ($fieldValue['target'] ?? false) {
            if ($fieldValue['target']['id'] !== '__none__' && count($this->onSaveTargetItems) > 0) {
                $saveElement = false;

                $targetFieldId = $fieldValue['target']['id'];
                $targetField = Craft::$app->getFields()->getFieldById($targetFieldId);
                $targetElement = ElementHelper::rootElement($element);
                $targetValue = $targetElement->getFieldValue($targetField->handle);

                $currentIds = [];
                foreach ($targetValue->all() as $item) {
                    if ($item['id'] ?? false) {
                        $currentIds[] = $item['id'] ?? '__no_id__';
                    }
                }

                // Replace old ids with new ids
                if ($fieldValue['target']['items'] ?? false) {
                    for ($i=0; $i<count($this->onSaveTargetItems); $i++) {
                        if ($this->onSaveTargetItems[$i] != $currentIds[$i]) {
                            $saveElement = true;

                            // Replace value ID on all breakpoints
                            foreach ($fieldValue['value'] as &$breakpoint) {
                              if (!empty($fieldValue['target']['items'][$i])) {
                                if ($breakpoint['id' . $fieldValue['target']['items'][$i]['id']] ?? false) {
                                    $breakpoint['id' . $currentIds[$i]] = $breakpoint['id' . $fieldValue['target']['items'][$i]['id']];
                                    unset($breakpoint['id' . $fieldValue['target']['items'][$i]['id']]);
                                }
                              }
                            }

                            // Replace target item ID
                            $fieldValue['target']['items'][$i]['id'] = $currentIds[$i];
                        }
                    }
                }

                if ($saveElement) {
                    $element->setFieldValues([$fieldHandle => Json::encode($fieldValue)]);
                    Craft::$app->getElements()->saveElement($element);
                }
            }
        }
    }
}
