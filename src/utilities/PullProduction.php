<?php

namespace craftnet\utilities;

use Craft;
use craft\base\Utility;

class PullProduction extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Pull Production');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'pull-production';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): ?string
    {
        return Craft::getAlias('@app/icons/database.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $request = Craft::$app->getRequest();

        return Craft::$app->getView()->renderTemplate('craftnet/sales-report/_content', [
        ]);
    }
}
