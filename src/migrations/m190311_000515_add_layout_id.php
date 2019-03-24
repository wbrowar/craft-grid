<?php

namespace wbrowar\grid\migrations;

use Craft;
use craft\db\Migration;

/**
 * m190311_000515_add_layout_id migration.
 */
class m190311_000515_add_layout_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Check if the updates can be made
        if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            // Find each instance of the Grid field and add an `id` property
            $allFields = Craft::$app->getFields()->getAllFields();

            foreach ($allFields as $field) {
                if (get_class($field) == 'wbrowar\grid\fields\Grid') {
                    $layout = json_decode($field->layout);
                    $breakpoints = $layout->breakpoints;

                    foreach ($breakpoints as &$breakpoint) {
                        // Create `id` from `minWidth` in the old pattern
                        $breakpoint->id = 'id' . $breakpoint->minWidth;

                        // Remove any `prevMinWidth` properties
                        if ($breakpoint->prevMinWidth ?? false) {
                            unset($breakpoint->prevMinWidth);
                        }
                    }

                    $layout->breakpoints = $breakpoints;

                    $newField = $field;
                    $newField->layout = json_encode($layout);

                    Craft::$app->getFields()->saveField($newField);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190311_000515_add_layout_id cannot be reverted.\n";
        return false;
    }
}
