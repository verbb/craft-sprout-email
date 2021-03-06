<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\migrations\m190714_000001_add_notification_email_context_column;
use craft\db\Migration;
use yii\base\NotSupportedException;

/**
 * m190714_000001_add_notification_email_context_column_sproutemail migration.
 */
class m190714_000001_add_notification_email_context_column_sproutemail extends Migration
{
    /**
     * @inheritdoc
     *
     * @throws NotSupportedException
     */
    public function safeUp(): bool
    {
        $migration = new m190714_000001_add_notification_email_context_column();

        ob_start();
        $migration->safeUp();
        ob_end_clean();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m190714_000001_add_notification_email_context_column_sproutemail cannot be reverted.\n";

        return false;
    }
}
