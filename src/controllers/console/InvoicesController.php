<?php

namespace craftnet\controllers\console;

use craft\commerce\stripe\Plugin as StripePlugin;
use craft\helpers\DateTimeHelper;
use craftnet\Module;
use yii\web\Response;

/**
 * Class InvoicesController
 */
class InvoicesController extends BaseController
{
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

        return $this->asSuccess(data: $data);
    }
}
