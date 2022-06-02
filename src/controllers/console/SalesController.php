<?php

namespace craftnet\controllers\console;

use Craft;
use craftnet\Module;
use yii\web\Response;

/**
 * Class SalesController
 */
class SalesController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Get sales.
     *
     * @return Response
     */
    public function actionGetSales(): Response
    {
        $user = Craft::$app->getUser()->getIdentity();

        $filter = $this->request->getParam('query');
        $limit = $this->request->getParam('limit', 10);
        $page = (int)$this->request->getParam('page', 1);

        $data = Module::getInstance()->getSaleManager()->getSalesByPluginOwner($user, $filter, $limit, $page);
        $total = Module::getInstance()->getSaleManager()->getTotalSalesByPluginOwner($user, $filter);

        $last_page = ceil($total / $limit);
        $next_page_url = '?next';
        $prev_page_url = '?prev';
        $from = ($page - 1) * $limit;
        $to = ($page * $limit) - 1;

        return $this->asJson([
            'total' => $total,
            'count' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $last_page,
            'next_page_url' => $next_page_url,
            'prev_page_url' => $prev_page_url,
            'from' => $from,
            'to' => $to,
            'data' => $data,
        ]);
    }
}
