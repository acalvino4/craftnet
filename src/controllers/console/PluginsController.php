<?php

namespace craftnet\controllers\console;

use Craft;
use craft\elements\Category;
use craft\elements\User;
use craftnet\behaviors\UserBehavior;
use Throwable;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Class PluginsController
 */
class PluginsController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * @throws Throwable
     * @throws InvalidConfigException
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    public function actionGetPlugins(): Response
    {
        $this->requireLogin();
        $org = $this->getAllowedOrgFromRequest();
        $data = [];

        if (!$org) {
            throw new ForbiddenHttpException();
        }

        foreach ($org->getPlugins() as $plugin) {
            $data[] = $this->pluginTransformer($plugin);
        }

        return $this->asSuccess(data: ['plugins' => $data]);
    }

    /**
     * Get categories.
     *
     * @return Response
     */
    public function actionGetCategories(): Response
    {
        $this->requireLogin();

        $data = [];
        $categories = Category::find()
            ->group('pluginCategories')
            ->all();

        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->id,
                'title' => $category->title,
            ];
        }

        return $this->asSuccess(data: ['categories' => $data]);
    }
}
