<?php

namespace craftnet\behaviors;

use Craft;
use craft\base\Element;
use craft\commerce\elements\Order;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craftnet\cms\CmsLicense;
use craftnet\db\Table;
use craftnet\Module;
use craftnet\orders\PdfRenderer;
use craftnet\orgs\Org;
use craftnet\plugins\PluginLicense;
use craftnet\plugins\PluginPurchasable;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use yii\base\Behavior;
use yii\db\Exception;

/**
 * @property Order $owner
 * @property bool $approvalRejected
 */
class OrderBehavior extends Behavior
{
    public ?int $orgId = null;
    private bool $approvalPending = false;
    private bool $approvalRejected = false;

    /**
     * @inheritdoc
     */
    public function events()
    {
        // todo: we should probably be listening for a transaction event here
        return [
            Order::EVENT_AFTER_COMPLETE_ORDER => [$this, 'afterComplete'],
            Element::EVENT_AFTER_SAVE => [$this, 'afterSave'],
        ];
    }

    public function getOrg(): ?Org
    {
        if (!$this->orgId) {
            return null;
        }

        return Org::find()
            ->id($this->orgId)
            ->one();
    }

    /**
     * Returns any Craft licenses that were purchased by this order.
     *
     * @return CmsLicense[]
     */
    public function getCmsLicenses(): array
    {
        return Module::getInstance()->getCmsLicenseManager()->getLicensesByOrder($this->owner->id);
    }

    /**
     * Returns any plugin licenses that were purchased by this order.
     *
     * @return PluginLicense[]
     */
    public function getPluginLicenses(): array
    {
        return Module::getInstance()->getPluginLicenseManager()->getLicensesByOrder($this->owner->id);
    }

    public function afterSave(): void
    {
        $this->_updateOrgOrders();
    }

    /**
     * Handles post-order-complete stuff.
     */
    public function afterComplete()
    {
        if (!$this->owner->getIsPaid()) {
            return;
        }

        $this->approvalPending = false;
        $this->approvalRejected = false;
        $this->_updateOrgOrders();

        $this->_updateDeveloperFunds();
        $this->_sendReceipt();
    }

    /**
     * Updates developers' accounts and attempts to transfer $$ to them after an order has completed.
     */
    private function _updateDeveloperFunds()
    {
        // See if any plugin licenses were purchased/renewed
        /** @var User[]|UserBehavior[] $developers */
        $developers = [];
        $developerTotals = [];
        $developerLineItems = [];

        foreach ($this->owner->getLineItems() as $lineItem) {
            $purchasable = $lineItem->getPurchasable();
            if ($purchasable instanceof PluginPurchasable) {
                $plugin = $purchasable->getPlugin();
                $developerId = $plugin->developerId;
                if (!isset($developers[$developerId])) {
                    $developers[$developerId] = $plugin->getDeveloper();
                    $developerTotals[$developerId] = $lineItem->total;
                } else {
                    $developerTotals[$developerId] += $lineItem->total;
                }

                $developerLineItems[$developerId][] = $lineItem;
            }
        }

        if (empty($developers)) {
            return;
        }

        // find the first successful transaction on the order
        // todo: if we change the event that triggers this, then we will need to be more careful about which transaction we're looking for
        $transaction = null;
        foreach ($this->owner->getTransactions() as $t) {
            if ($t->status === TransactionRecord::STATUS_SUCCESS) {
                $transaction = $t;
                break;
            }
        }
        if (!$transaction) {
            return;
        }

        // Grab the charge id from the transaction
        $chargeId = $transaction->reference;

        // In case we're dealing with a payment intent here, grab the latest charge
        if (StringHelper::startsWith($chargeId, 'pi_')) {
            Stripe::setApiKey(App::env('STRIPE_API_KEY'));
            $stripePaymentIntent = PaymentIntent::retrieve($chargeId);
            $chargeId = $stripePaymentIntent->charges->data[0]->id;
        }

        // Try transferring funds to them
        foreach ($developers as $developerId => $developer) {
            // ignore if this is us
            if ($developer->username === 'pixelandtonic') {
                continue;
            }

            // figure out our 20% fee (up to 2 decimals)
            $lineItems = $developerLineItems[$developerId];
            $total = $developerTotals[$developerId];
            $fee = floor($total * 20) / 100;
            $developer->getFundsManager()->processOrder($this->owner->number, $lineItems, $chargeId, $total, $fee);
        }

        // Now send developer notification emails
        foreach ($developerLineItems as $developerId => $lineItems) {
            $developer = $developers[$developerId];
            $this->_sendDeveloperSaleEmail($developer, $lineItems);
        }
    }

    /**
     * @param $developer
     * @param $lineItems
     * @throws \yii\base\InvalidConfigException
     */
    private function _sendDeveloperSaleEmail($developer, $lineItems)
    {
        $mailer = Craft::$app->getMailer();

        $mailer
            ->composeFromKey(Module::MESSAGE_KEY_DEVELOPER_SALE, [
                'developer' => $developer,
                'lineItems' => $lineItems,
            ])
            ->setFrom($mailer->from)
            ->setTo($developer->email)
            ->send();
    }

    /**
     * Sends the customer a receipt email after an order has completed.
     */
    private function _sendReceipt()
    {
        // render the PDF
        $pdf = (new PdfRenderer())->render($this->owner);
        $filename = 'Order-' . strtoupper($this->owner->getShortNumber()) . '.pdf';

        $mailer = Craft::$app->getMailer();
        $mailer
            ->composeFromKey(Module::MESSAGE_KEY_RECEIPT, [
                'order' => $this->owner,
            ])
            ->setFrom($mailer->from)
            ->setTo($this->owner->getEmail())
            ->attachContent($pdf, [
                'fileName' => $filename,
                'contentType' => 'application/pdf',
            ])
            ->send();
    }

    /**
     * @throws Exception
     */
    private function _updateOrgOrders(): bool
    {
        if (!$this->orgId) {
            return false;
        }

        return (bool) Db::update(Table::ORGS_ORDERS, [
            'approvalPending' => $this->approvalRequested,
            'approvalRejected' => $this->approvalRejected,
        ], [
            'id' => $this->owner->id,
            'orgId' => $this->orgId,
        ]);
    }

    public function setApprovalRejected(?bool $approvalRejected): static
    {
        $this->approvalRejected = (bool)$approvalRejected;

        if ($approvalRejected) {
            $this->approvalPending = false;
        }

        return $this;
    }

    public function getApprovalRejected(): bool
    {
        return $this->approvalRejected;
    }

    public function getApprovalPending(): bool
    {
        return $this->approvalPending;
    }

    public function setApprovalPending(?bool $approvalPending): static
    {
        $this->approvalPending = (bool)$approvalPending;
        return $this;
    }
}
