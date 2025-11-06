<?php

namespace LeadingSystems\MerconisBundle\Controller\Backend;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class DownloadExportController
{
    private ContaoFramework $framework;
    private Security $security;
    private string $projectDir;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        ContaoFramework $framework,
        Security $security,
        string $projectDir,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->framework = $framework;
        $this->security = $security;
        $this->projectDir = $projectDir;
        $this->urlGenerator = $urlGenerator;
    }

    public function downloadAction(string $fileName, Request $request): Response
    {
        $this->framework->initialize();




        //TODO: dont work
        if ($this->security->isGranted('ROLE_ADMIN')) { //dont work?
        }

        //TODO: remove tests
        $canAccessAdmin = $this->security->isGranted('ROLE_ADMIN');

        $canAccess1 = $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'xxxx');
        $canAccess2 = $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'news');
        $canAccess3 = $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'ls_shop_export');


        //TODO: dont work
        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'ls_shop_export')) {
            //throw new AccessDeniedHttpException('Not enough permissions to download this file.');
        }




        $allowedBaseDir = realpath($this->projectDir . '/');
        if (false === $allowedBaseDir) {
            throw new \RuntimeException('Configured download base directory does not exist.');
        }

        $fullPath = realpath($allowedBaseDir . '/files/export-output/' . $fileName);

        if (false === $fullPath || !str_starts_with($fullPath, $allowedBaseDir)) {
            throw new AccessDeniedHttpException('Access denied: File is outside of the allowed download directory.');
        }

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            throw new NotFoundHttpException('File not found.');
        }

        $response = new BinaryFileResponse($fullPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($fullPath)
        );

        return $response;
    }
}