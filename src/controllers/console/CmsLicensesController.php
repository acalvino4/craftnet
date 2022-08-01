<?php

namespace craftnet\controllers\console;

use Craft;
use craft\errors\UploadFailedException;
use craft\web\UploadedFile;
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
 * Class CmsLicensesController
 *
 * @property Module $module
 */
class CmsLicensesController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Claims a license.
     *
     * @return Response
     * @throws BadRequestHttpException|Throwable
     */
    public function actionClaim(): Response
    {
        $key = $this->request->getBodyParam('key');
        $licenseFile = UploadedFile::getInstanceByName('licenseFile');
        $user = Craft::$app->getUser()->getIdentity();
        $owner = $this->getAllowedOrgFromRequest() ?? $user;

        try {

            if ($licenseFile) {
                if ($licenseFile->getHasError()) {
                    throw new UploadFailedException($licenseFile->error);
                }

                $licenseFilePath = $licenseFile->tempName;

                $key = file_get_contents($licenseFilePath);
            }

            if ($key) {
                $this->module->getCmsLicenseManager()->claimLicense($owner, $key, $user);
                return $this->asSuccess();
            }

            throw new Exception("No license key provided.");
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * Download license file.
     *
     * @return Response
     * @throws ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function actionDownload(): Response
    {
        $user = Craft::$app->getUser()->getIdentity();
        $licenseId = $this->request->getParam('id');
        $license = $this->module->getCmsLicenseManager()->getLicenseById($licenseId);

        if ($license->canManage($user)) {
            return $this->response->sendContentAsFile(chunk_split($license->key, 50), 'license.key');
        }

        throw new ForbiddenHttpException('User is not authorized to perform this action');
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
            $total = Module::getInstance()->getCmsLicenseManager()->getExpiringLicensesTotal($user);

            return $this->asSuccess(data: ['total' => $total]);
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
            $license = Module::getInstance()->getCmsLicenseManager()->getLicenseById($id);

            if (!$license->canManage($user)) {
                throw new UnauthorizedHttpException('Not Authorized');
            }

            $licenseArray = Module::getInstance()->getCmsLicenseManager()->transformLicenseForOwner($license, $owner, ['pluginLicenses']);

            return $this->asSuccess(data: ['license' => $licenseArray]);
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }

    /**
     * Returns the current user’s licenses for `vue-tables-2`.
     *
     * @return Response
     */
    public function actionGetLicenses(): Response
    {
        $filter = $this->request->getParam('query');
        $perPage = $this->request->getParam('limit', 10);
        $page = (int)$this->request->getParam('page', 1);
        $orderBy = $this->request->getParam('orderBy');
        $ascending = (bool)$this->request->getParam('ascending');

        try {
            $user = Craft::$app->getUser()->getIdentity();
            $owner = $this->getAllowedOrgFromRequest() ?? $user;
            $licenses = Module::getInstance()->getCmsLicenseManager()->getLicensesByOwner($owner, $filter, $perPage, $page, $orderBy, $ascending);
            $totalLicenses = Module::getInstance()->getCmsLicenseManager()->getTotalLicensesByOwner($owner, $filter);

            $lastPage = ceil($totalLicenses / $perPage);
            $nextPageUrl = '?next';
            $prevPageUrl = '?prev';
            $from = ($page - 1) * $perPage;
            $to = ($page * $perPage) - 1;

            return $this->asSuccess(data: [
                'total' => $totalLicenses,
                'per_page' => $perPage,
                'count' => $totalLicenses,
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
     * Releases a license.
     *
     * @return Response
     * @throws LicenseNotFoundException
     * @throws ForbiddenHttpException
     */
    public function actionRelease(): Response
    {
        $key = $this->request->getParam('key');
        $user = Craft::$app->getUser()->getIdentity();
        $manager = $this->module->getCmsLicenseManager();
        $license = $manager->getLicenseByKey($key);

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
                    $note .= " for $org->title";
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
     */
    public function actionSave(): Response
    {
        $key = $this->request->getParam('key');
        $user = Craft::$app->getUser()->getIdentity();
        $manager = $this->module->getCmsLicenseManager();
        $license = $manager->getLicenseByKey($key);
        $owner = $license->getOwner();
        $org = $owner instanceof Org ? $owner : null;

        try {
            if ($license->canManage($user)) {
                $domain = $this->request->getParam('domain');
                $notes = $this->request->getParam('notes');

                if ($domain !== null) {
                    $oldDomain = $license->domain;
                    $license->domain = $domain ?: null;
                    $license->allowCustomDomain = true;
                }

                if ($notes !== null) {
                    $license->notes = $notes;
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

                if (!$manager->saveLicense($license)) {
                    throw new Exception("Couldn't save license.");
                }

                if ($domain !== null && $license->domain !== $oldDomain) {
                    $note = $license->domain ? "tied to domain {$license->domain}" : "untied from domain {$oldDomain}";
                    $note = "{$note} by {$user->email}";
                    if ($org) {
                        $note .= " for $org->title";
                    }
                    $manager->addHistory($license->id, $note);
                }

                return $this->asSuccess(data: [
                    'license' => $manager->transformLicenseForOwner($license, $user),
                ]);
            }

            throw new LicenseNotFoundException($key);
        } catch (Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }
}
