<?php

namespace craftnet\controllers\console;

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
        $org = $this->getAllowedOrgFromRequest(required: true);
        $filter = $this->request->getParam('query');
        $limit = $this->request->getParam('limit', 10);
        $page = (int)$this->request->getParam('page', 1);

        $data = Module::getInstance()->getSaleManager()->getSalesByPluginOwner($org, $filter, $limit, $page);
        $total = Module::getInstance()->getSaleManager()->getTotalSalesByPluginOwner($org, $filter);

        return $this->asSuccess(data: $this->formatPagination($data, $total, $page, $limit));
    }
}
