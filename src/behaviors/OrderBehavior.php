<?php

namespace craftnet\behaviors;

use Craft;
use craft\base\Element;
use craft\commerce\elements\Order;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\elements\Address;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craftnet\cms\CmsLicense;
use craftnet\db\Table;
use craftnet\Module;
use craftnet\orders\PdfRenderer;
use craftnet\orgs\Org;
use craftnet\plugins\PluginLicense;
use craftnet\plugins\PluginPurchasable;
use DateTime;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Throwable;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\db\Exception;

/**
 * @property Order $owner
 */
class OrderBehavior extends Behavior
{
    private ?int $orgId = null;
    private ?int $creatorId = null;
    private ?int $purchaserId = null;
    public ?int $approvalRequestedForOrgId = null;
    private ?int $approvalRequestedById = null;
    private ?int $approvalRejectedById = null;
    private ?DateTime $approvalRejectedDate = null;

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

    public function setApprovalRejectedDate(string|DateTime|null $approvalRejectedDate): static
    {
        $this->approvalRejectedDate = DateTimeHelper::toDateTime($approvalRejectedDate) ?: null;

        return $this;
    }

    public function getApprovalRejectedDate(): ?DateTime
    {
        return $this->approvalRejectedDate;
    }

    public function isPendingApproval(): bool
    {
        return $this->approvalRequestedById && !$this->approvalRejectedById;
    }

    /**
     * @throws \yii\base\Exception
     * @throws Throwable
     * @throws ElementNotFoundException
     */
    public function requestApproval(User $member, Org $org): bool
    {
        if (!$org->hasMember($member)) {
            throw new UserException('User is not a member of this organization.');
        }

        if ($this->isPendingApproval()) {
            throw new UserException('Order already has a pending approval request.');
        }

        if (!$this->hasCustomer($member)) {
            throw new UserException('Order does not belong to this user');
        }

        $this->setApprovalRequestedBy($member);
        $this->approvalRequestedForOrgId = $org->id;

        $saved = Craft::$app->getElements()->saveElement($this->owner);

        if (!$saved) {
            return false;
        }

        return Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_ORDER_APPROVAL_REQUEST, [
                'recipient' => $org->getOwner(),
                'sender' => $member,
                'order' => $this->owner,
                'org' => $org,
            ])
            ->setTo($org->getOwner()->email)
            ->send();
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     * @throws UserException
     */
    public function rejectApproval(User $user, Org $org): bool
    {
        if (!$org->canRejectOrders($user)) {
            throw new UserException('Only organization owners may reject approval requests.');
        }

        if ($this->getApprovalRejectedBy()) {
            throw new UserException('Order has already been rejected.');
        }

        if (!$this->isPendingApproval()) {
            throw new UserException('Order has no pending approval request.');
        }

        $this->setApprovalRejectedBy($user);
        $saved = Craft::$app->getElements()->saveElement($this->owner);

        if (!$saved) {
            return false;
        }

        $recipient = $this->getApprovalRequestedBy();

        return Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_ORDER_APPROVAL_REJECT, [
                'recipient' => $recipient,
                'sender' => $user,
                'order' => $this->owner,
                'org' => $org,
            ])
            ->setTo($recipient->email)
            ->send();
    }

    public function getOrg(): ?Org
    {
        return $this->orgId
            ? Org::find()->id($this->orgId)->one()
            : null;
    }

    public function setOrg(Org|int|null $org): static
    {
        $this->orgId = $org instanceof Org ? $org->id : $org;

        return $this;
    }

    /**
     * TODO: Commerce 4.2 may do this for us
     */
    public function setValidBillingAddress(?Address $address): static
    {
        if (!$address) {
            $this->owner->setBillingAddress(null);
        }  else {
            $isCloned = $address->id === $this->owner->sourceBillingAddressId;

            if (!$isCloned && $address->ownerId !== $this->owner->id) {
                $this->owner->sourceBillingAddressId = $address->id;
                $newAddress = Craft::$app->getElements()->duplicateElement($address, [
                    'ownerId' => $this->owner->id,
                ]);
                $this->owner->setBillingAddress($newAddress);
            }
        }

        return $this;
    }

    public function getOrgId(?int $orgId): ?int
    {
        return $this->orgId;
    }

    public function setOrgId(?int $orgId): static
    {
        return $this->setOrg($orgId);
    }

    public function getCreator(): ?User
    {
        return $this->creatorId
            ? User::find()->id($this->creatorId)->one()
            : null;
    }

    public function setCreator(int|User|null $creator): static
    {
        $this->creatorId = $creator instanceof User ? $creator->id : $creator;

        return $this;
    }

    public function setCreatorId(?int $creatorId): static
    {
        return $this->setCreator($creatorId);
    }

    public function getPurchaser(): ?User
    {
        return $this->purchaserId
            ? User::find()->id($this->purchaserId)->one()
            : null;
    }

    public function setPurchaser(int|User|null $purchaser): static
    {
        $this->purchaserId = $purchaser instanceof User ? $purchaser->id : $purchaser;

        return $this;
    }

    public function setPurchaserId(?int $purchaserId): static
    {
        return $this->setPurchaser($purchaserId);
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

        $this->_approveOrgOrder();
        $this->_updateDeveloperFunds();
        $this->_sendReceipt();
    }

    public function setApprovalRejectedBy(int|User|null $approvalRejectedBy): static
    {
        $this->approvalRejectedById = $approvalRejectedBy instanceof User ? $approvalRejectedBy->id : $approvalRejectedBy;

        if (!$this->approvalRejectedById) {
            $this->approvalRejectedDate = null;
        } else if (!$this->approvalRejectedDate) {
            $this->approvalRejectedDate = new DateTime();
        }

        return $this;
    }

    public function getApprovalRejectedBy(): ?User
    {
        return $this->approvalRejectedById
            ? User::find()->id($this->approvalRejectedById)->one()
            : null;
    }

    public function setApprovalRejectedById(?int $approvalRejectedById): static
    {
        return $this->setApprovalRejectedBy($approvalRejectedById);
    }

    public function setApprovalRequestedBy(int|User|null $approvalRequestedBy): static
    {
        $this->approvalRequestedById = $approvalRequestedBy instanceof User ? $approvalRequestedBy->id : $approvalRequestedBy;
        $this->setApprovalRejectedBy(null);

        return $this;
    }

    public function getApprovalRequestedBy(): ?User
    {
        return $this->approvalRequestedById
            ? User::find()->id($this->approvalRequestedById)->one()
            : null;
    }

    public function setApprovalRequestedById(?int $approvalRequestedById): static
    {
        return $this->setApprovalRequestedBy($approvalRequestedById);
    }

    public function hasCustomer(User $user): bool
    {
        return $user->id === $this->owner->customerId;
    }

    /**
     * Updates developers' accounts and attempts to transfer $$ to them after an order has completed.
     */
    private function _updateDeveloperFunds()
    {
        // See if any plugin licenses were purchased/renewed
        /** @var Org[] $developers */
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
            if ($developer->slug === 'pixelandtonic') {
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
     * @param Org $developer
     * @param $lineItems
     * @throws \yii\base\InvalidConfigException
     */
    private function _sendDeveloperSaleEmail(Org $developer, $lineItems)
    {
        $mailer = Craft::$app->getMailer();

        $mailer
            ->composeFromKey(Module::MESSAGE_KEY_DEVELOPER_SALE, [
                'developer' => $developer,
                'lineItems' => $lineItems,
            ])
            ->setFrom($mailer->from)
            ->setTo($developer->owner->email)
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
        if ($this->orgId) {
            Db::upsert(Table::ORGS_ORDERS, [
                'id' => $this->owner->id,
                'orgId' => $this->orgId,
                'creatorId' => $this->creatorId,
                'purchaserId' => $this->purchaserId,
            ]);
        } else {
            Db::delete(Table::ORGS_ORDERS, [
                'id' => $this->owner->id,
            ]);
        }

        if ($this->approvalRequestedForOrgId) {
            Db::upsert(Table::ORGS_ORDERAPPROVALS, [
                'orderId' => $this->owner->id,
                'orgId' => $this->approvalRequestedForOrgId,
                'requestedById' => $this->approvalRequestedById,
                'rejectedById' => $this->approvalRejectedById,
                'dateRejected' => Db::prepareDateForDb($this->approvalRejectedDate),
            ]);
        } else {
            Db::delete(Table::ORGS_ORDERAPPROVALS, [
                'orderId' => $this->owner->id,
            ]);
        }

        return true;
    }

    /**
     * @throws Exception
     * @throws \yii\base\Exception
     * @throws InvalidConfigException
     */
    private function _approveOrgOrder(): bool
    {
        if (!$this->orgId) {
            return false;
        }

        $org = $this->getOrg();
        $requestedBy = $this->getApprovalRequestedBy();
        $this->approvalRequestedForOrgId = null;
        $this->_updateOrgOrders();

        if (!$requestedBy) {
            return false;
        }

        return Craft::$app->getMailer()
            ->composeFromKey(Module::MESSAGE_KEY_ORG_ORDER_APPROVAL_APPROVE, [
                'recipient' => $requestedBy,
                'sender' => $org->owner,
                'order' => $this->owner,
                'org' => $org,
            ])
            ->setTo($requestedBy->email)
            ->send();
    }
}
