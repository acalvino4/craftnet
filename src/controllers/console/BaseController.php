<?php

namespace craftnet\controllers\console;

use Craft;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\web\Controller;
use craftnet\orgs\MemberRoleEnum;
use craftnet\orgs\Org;
use craftnet\plugins\Plugin;
use Throwable;
use yii\base\UserException;
use yii\helpers\Markdown;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response as YiiResponse;

/**
 * Class BaseController
 *
 * @property array $apps
 */
abstract class BaseController extends Controller
{
    public function bindActionParams($action, $params)
    {
        $userId = $params['userId'] ?? null;
        $userId = $userId === 'me' ? Craft::$app->getUser()->getId() : $userId;

        if ($userId) {
            $params['userId'] = $userId;

            // Inject userId as a body param for Craft's users controllers
            $this->request->setBodyParams($this->request->getBodyParams() + [
                'userId' => $userId,
            ]);
        }

        return parent::bindActionParams($action, $params);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param Plugin $plugin
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function pluginTransformer(Plugin $plugin): array
    {
        $icon = $plugin->getIcon();
        $developer = $plugin->getDeveloper();

        // Screenshots
        $screenshotUrls = [];
        $screenshotIds = [];

        foreach ($plugin->getScreenshots() as $screenshot) {
            $screenshotUrls[] = $screenshot->getUrl() . '?' . $screenshot->dateModified->getTimestamp();
            $screenshotIds[] = $screenshot->getId();
        }

        // Last history note
        $lastHistoryNote = null;
        $history = $plugin->getHistory();

        if (count($history) > 0) {
            $lastHistoryNote = $history[0];

            if ($lastHistoryNote['devComments']) {
                $lastHistoryNote['devComments'] = Markdown::process($lastHistoryNote['devComments']);
            }
        }

        $editions = [];
        foreach ($plugin->getEditions() as $edition) {
            $editions[] = [
                'id' => $edition->id,
                'name' => $edition->name,
                'handle' => $edition->handle,
                'price' => $edition->price,
                'renewalPrice' => $edition->renewalPrice,
                'features' => $edition->features ?? [],
            ];
        }

        // Latest version
        $latestVersion = Plugin::find()
            ->withLatestReleaseInfo()
            ->id($plugin->id)
            ->select(['latestVersion'])
            ->asArray()
            ->scalar();

        $replacement = $plugin->getReplacement();

        return [
            'id' => $plugin->id,
            'enabled' => $plugin->enabled,
            'pendingApproval' => $plugin->pendingApproval,
            'status' => $plugin->status,
            'iconId' => $plugin->iconId,
            'iconUrl' => $icon ? $icon->getUrl() . '?' . $icon->dateModified->getTimestamp() : null,
            'packageName' => $plugin->packageName,
            'handle' => $plugin->handle,
            'name' => $plugin->name,
            'shortDescription' => $plugin->shortDescription,
            'longDescription' => $plugin->longDescription,
            'documentationUrl' => $plugin->documentationUrl,
            'changelogPath' => $plugin->changelogPath,
            'repository' => $plugin->repository,
            'license' => $plugin->license,
            'editions' => $editions,
            'keywords' => $plugin->keywords,
            'latestVersion' => $latestVersion ?: null,

            // 'iconUrl' => $iconUrl,
            'developerId' => $developer->id,
            'developerName' => $developer->title,
            'developerUrl' => $developer->externalUrl,

            'screenshotUrls' => $screenshotUrls,
            'screenshotIds' => $screenshotIds,
            'categoryIds' => ArrayHelper::getColumn($plugin->getCategories(), 'id'),

            'lastHistoryNote' => $lastHistoryNote,
            'activeInstalls' => $plugin->activeInstalls,
            'abandoned' => $plugin->abandoned,
            'replacementHandle' => $replacement->handle ?? null,
        ];
    }

    /**
     * Get expiry date options.
     *
     * @param \DateTime $expiryDate
     * @return array
     * @throws \Exception
     */
    protected function getExpiryDateOptions(\DateTime $expiryDate): array
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $dates = [];

        for ($i = 1; $i <= 5; $i++) {
            if ($expiryDate < $now) {
                $date = (new \DateTime('now', new \DateTimeZone('UTC')))
                    ->modify("+{$i} years");
                $dates[] = ["{$i}y", $date->format('Y-m-d')];
            } else {
                $date = clone $expiryDate;
                $date = $date->modify("+{$i} years");
                $dates[] = ["{$date->format('Y-m-d')}", $date->format('Y-m-d')];
            }
        }

        return $dates;
    }

    /**
     * @throws Throwable
     * @throws BadRequestHttpException
     */
    protected function getAllowedOrgFromRequest($required = false, string $name = 'orgId'): ?Org
    {
        $this->requireLogin();
        $user = $this->getCurrentUser();
        $orgId = $required ? $this->request->getRequiredParam($name) : $this->request->getParam($name);
        $org = $orgId ? Org::find()->id($orgId)->hasMember($user)->one() : null;

        if ($orgId && !$org) {
            throw new BadRequestHttpException('Invalid organization');
        }

        return $org;
    }

    /**
     * @throws UserException
     */
    protected function getOrgMemberRoleFromRequest($required = false, string $name = 'role'): ?MemberRoleEnum
    {
        $roleFromRequest = $required ? $this->request->getRequiredParam($name) : $this->request->getBodyParam($name);

        if ($roleFromRequest === null) {
            return null;
        }

        $role = MemberRoleEnum::tryFrom($roleFromRequest);

        if (!$role) {
            throw new BadRequestHttpException('Invalid role.');
        }

        return $role;
    }

    public function getElevatedSessionResponse(?string $message = null): ?YiiResponse
    {
        $message = $message ?? Craft::t('app', 'This action may only be performed with an elevated session.');

        return $this->asFailure($message, [
            'requireElevatedSession' => true
        ]);
    }

    protected static function transformOrg(Org $org): array
    {
        return $org->getAttributes([
                'id',
                'title',
                'requireOrderApproval',
                'paymentSourceId',
                'billingAddressId',
            ]) + [
                'orgLogo' => $org->orgLogo->one()?->getAttributes(['id', 'url']),
            ];
    }

    /**
     * @param int $id
     * @return Org
     * @throws NotFoundHttpException
     */
    protected static function getOrgById(int $id): Org
    {
        $org = Org::find()->id($id)->one();

        if (!$org) {
            throw new NotFoundHttpException();
        }

        return $org;
    }

    protected static function transformUser(?User $user): ?array
    {
        return $user ? $user->getAttributes([
            'id',
            'email',
            'name',
        ]) + [
            'photo' => $user->photo?->getAttributes(['id', 'url']),
        ] : null;
    }

    /**
     * @param int|User $user
     * @return void
     * @throws ForbiddenHttpException
     */
    protected function restrictToUser(int|User $user): void
    {
        if ($this->currentUser->admin || $this->isCurrentUser($user)) {
            return;
        }

        throw new ForbiddenHttpException('Invalid user.');
    }

    protected function isCurrentUser(int|User $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $userId === $this->currentUser->id;
    }
}
