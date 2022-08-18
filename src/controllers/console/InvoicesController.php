<?php

namespace craftnet\controllers\console;

use craft\commerce\stripe\Plugin as StripePlugin;
use craft\helpers\DateTimeHelper;
use craftnet\Module;
use Throwable;
use yii\web\Response;

/**
 * Class InvoicesController
 */
class InvoicesController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Get invoices.
     *
     * @return Response
     */
    public function actionGetInvoicesForUser(int $userId = null): Response
    {
        $owner = $this->getAllowedOrgFromRequest() ?? $this->currentUser;
        $filter = $this->request->getParam('query');
        $limit = $this->request->getParam('limit', 10);
        $page = (int)$this->request->getParam('page', 1);
        $orderBy = $this->request->getParam('orderBy');
        $ascending = $this->request->getParam('ascending');

        $this->restrictToUser($userId);

        try {
            $invoices = Module::getInstance()
                ->getInvoiceManager()
                ->getInvoices($owner, $filter, $limit, $page, $orderBy, $ascending);

            $total = Module::getInstance()->getInvoiceManager()->getTotalInvoices($owner, $filter);
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
                'data' => $invoices,
            ]);
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * Get invoice by its number.
     *
     * @return Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetInvoiceByNumber(): Response
    {
        $number = $this->request->getRequiredParam('number');

        try {
            $invoice = Module::getInstance()->getInvoiceManager()->getInvoiceByNumber($this->currentUser, $number);

            return $this->asSuccess(data: ['invoice' => $invoice]);
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * Get invoices.
     *
     * @return Response
     */
    public function actionGetSubscriptionInvoices(): Response
    {
        $invoices = StripePlugin::getInstance()->getInvoices()->getUserInvoices($this->currentUser->id);

        $data = [
            'invoices' => [],
        ];

        foreach ($invoices as $invoice) {
            $invoiceData = $invoice->invoiceData;

            $latestStart = 0;

            // Find the latest subscription start time and make it the invoice date.
            foreach ($invoiceData['lines']['data'] as $lineItem) {
                $latestStart = max($latestStart, $lineItem['period']['start']);
            }

            $data['invoices'][] = [
                'date' => DateTimeHelper::toDateTime($latestStart)->format('Y-m-d'),
                'amount' => $invoiceData['total'] / 100,
                'url' => $invoiceData['invoice_pdf'],
            ];
        }

        return $this->asSuccess($data);
    }
}
