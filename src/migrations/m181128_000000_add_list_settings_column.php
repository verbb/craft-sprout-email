<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\migrations\m181128_000000_add_list_settings_column as baseMigration;
use craft\db\Migration;
use yii\base\NotSupportedException;

/**
 * m181128_000000_add_list_settings_column migration.
 */
class m181128_000000_add_list_settings_column extends Migration
{
    /**
     * @return bool
     * @throws NotSupportedException
     */
    public function safeUp(): bool
    {
        $notificationAddColumn = new baseMigration();

        ob_start();
        $notificationAddColumn->safeUp();
        ob_end_clean();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m181128_000000_add_list_settings_column cannot be reverted.\n";

        return false;
    }
}
