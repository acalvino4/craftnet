<?php

namespace craftnet\controllers\console;

use Craft;
use craftnet\errors\LicenseNotFoundException;
use craftnet\Module;
use craftnet\orgs\Org;
use Exception;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

/**
 * Class PluginLicensesController
 *
 * @property Module $module
 */
class PluginLicensesController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Claims a license.
     *
     * @return Response
     */
    public function actionClaim(): Response
    {
        $key = $this->request->getParam('key');
        $user = Craft::$app->getUser()->getIdentity();
        $owner = $this->getAllowedOrgFromRequest() ?? $user;

        try {
            $this->module->getPluginLicenseManager()->claimLicense($owner, $user, $key);
            return $this->asSuccess();
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * Get license by ID.
     *
     * @return Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetLicenseById(): Response
    {
        $user = Craft::$app->getUser()->getIdentity();
        $id = $this->request->getRequiredParam('id');
        $owner = $this->getAllowedOrgFromRequest() ?? $user;

        try {
            $license = Module::getInstance()->getPluginLicenseManager()->getLicenseById($id);

            if (!$license->canManage($user)) {
                throw new UnauthorizedHttpException('Not Authorized');
            }

            $licenseArray = Module::getInstance()->getPluginLicenseManager()->transformLicenseForOwner($license, $owner);

            return $this->asSuccess(data: ['license' => $licenseArray]);
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * @throws Throwable
     * @throws LicenseNotFoundException
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    public function actionTransfer(): Response
    {
        $user = Craft::$app->getUser()->getIdentity();
        $licenseId = $this->request->getRequiredParam('id');
        $newOwnerId = $this->request->getRequiredParam('newOwnerId');
        $licenseManager = Module::getInstance()->getPluginLicenseManager();
        $license = $licenseManager->getLicenseById($licenseId);
        $newOwner = Craft::$app->getElements()->getElementById($newOwnerId);

        if ($license->ownerId === $newOwner->id) {
            return $this->asFailure('This license is already owned by the specified owner.');
        }

        if (!$license->canRelease($user)) {
            throw new ForbiddenHttpException('User does not have permission to transfer this license.');
        }

        if ($newOwner instanceof Org && !$newOwner->hasMember($user)) {
            throw new ForbiddenHttpException('User is not a member of organization.');
        }

        try {
            if (!$licenseManager->transferLicense($license, $newOwner, $user)) {
                return $this->asFailure('Unable to transfer license.');
            }

            return $this->asSuccess();
        } catch(Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * Returns licenses for the current user.
     *
     * @return Response
     * @throws LicenseNotFoundException
     */
    public function actionGetLicenses(): Response
    {
        $user = Craft::$app->getUser()->getIdentity();

        $filter = $this->request->getParam('query');
        $perPage = $this->request->getParam('limit', 10);
        $page = (int)$this->request->getParam('page', 1);
        $orderBy = $this->request->getParam('orderBy');
        $ascending = (bool)$this->request->getParam('ascending');

        try {
            $user = Craft::$app->getUser()->getIdentity();
            $owner = $this->getAllowedOrgFromRequest() ?? $user;
            $licenses = Module::getInstance()->getPluginLicenseManager()->getLicensesByOwner($owner, $filter, $perPage, $page, $orderBy, $ascending);
            $totalLicenses = Module::getInstance()->getPluginLicenseManager()->getTotalLicensesByOwner($owner, $filter);

            $lastPage = ceil($totalLicenses / $perPage);
            $nextPageUrl = '?next';
            $prevPageUrl = '?prev';
            $from = ($page - 1) * $perPage;
            $to = ($page * $perPage) - 1;

            return $this->asSuccess(data: [
                'total' => $totalLicenses,
                'count' => $totalLicenses,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'next_page_url' => $nextPageUrl,
                'prev_page_url' => $prevPageUrl,
                'from' => $from,
                'to' => $to,
                'data' => $licenses,
            ]);
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * Get the number of expiring licenses.
     *
     * @return Response
     */
    public function actionGetExpiringLicensesTotal(): Response
    {
        $user = Craft::$app->getUser()->getIdentity();

        try {
            $total = Module::getInstance()->getPluginLicenseManager()->getExpiringLicensesTotal($user);

            return $this->asJson(data: ['total' => $total]);
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * Releases a license.
     *
     * @return Response
     * @throws LicenseNotFoundException
     */
    public function actionRelease(): Response
    {
        $pluginHandle = $this->request->getParam('handle');
        $key = $this->request->getParam('key');
        $user = Craft::$app->getUser()->getIdentity();
        $manager = $this->module->getPluginLicenseManager();
        $license = $manager->getLicenseByKey($key, $pluginHandle);

        try {
            if (!$license->canRelease($user)) {
                throw new LicenseNotFoundException($key);
            }

            $owner = $license->getOwner();
            $org = $owner instanceof Org ? $owner : null;
            $license->ownerId = null;

            if ($manager->saveLicense($license, true, ['ownerId'])) {
                $note = "released by $user->email";
                if ($org) {
                    $note .= " for organization $org->title";
                }
                $manager->addHistory($license->id, $note);
                return $this->asSuccess();
            }

            throw new Exception("Couldn't save license.");
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * Saves a license.
     *
     * @return Response
     * @throws LicenseNotFoundException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSave(): Response
    {
        $pluginHandle = $this->request->getRequiredBodyParam('pluginHandle');
        $key = $this->request->getRequiredBodyParam('key');
        $user = Craft::$app->getUser()->getIdentity();
        $manager = $this->module->getPluginLicenseManager();
        $license = $manager->getLicenseByKey($key, $pluginHandle);
        $owner = $license->getOwner();
        $org = $owner instanceof Org ? $owner : null;

        try {
            if ($license->canManage($user)) {
                $notes = $this->request->getParam('notes');

                if ($notes !== null) {
                    $license->notes = $this->request->getParam('notes');
                }

                $oldCmsLicenseId = $license->cmsLicenseId;
                if (($cmsLicenseId = $this->request->getParam('cmsLicenseId', false)) !== false) {
                    $license->cmsLicenseId = $cmsLicenseId ?: null;
                }

                // Did they change the auto renew setting?
                $autoRenew = $this->request->getParam('autoRenew', $license->autoRenew);

                if ($autoRenew != $license->autoRenew) {
                    $license->autoRenew = $autoRenew;
                    // If they've already received a reminder about the auto renewal, then update the locked price
                    if ($autoRenew && $license->reminded) {
                        $license->renewalPrice = $license->getEdition()->getRenewal()->getPrice();
                    }
                }

                if ($manager->saveLicense($license)) {
                    if ($oldCmsLicenseId != $license->cmsLicenseId) {
                        $byLine = "by {$user->email}";

                        if ($org) {
                            $byLine .= " for organization $org->title";
                        }

                        if ($oldCmsLicenseId) {
                            $oldCmsLicense = $this->module->getCmsLicenseManager()->getLicenseById($oldCmsLicenseId);
                            $manager->addHistory($license->id, "detached from Craft license {$oldCmsLicense->shortKey} $byLine");
                        }

                        if ($license->cmsLicenseId) {
                            $newCmsLicense = $this->module->getCmsLicenseManager()->getLicenseById($license->cmsLicenseId);
                            $manager->addHistory($license->id, "attached to Craft license {$newCmsLicense->shortKey} $byLine");
                        }
                    }

                    return $this->asSuccess();
                }

                throw new Exception("Couldn't save license.");
            }

            throw new LicenseNotFoundException($key);
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }
}
