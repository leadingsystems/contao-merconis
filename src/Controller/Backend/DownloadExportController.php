<?php

namespace LeadingSystems\MerconisBundle\Controller\Backend;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\SecurityBundle\Security;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class DownloadExportController
{
    private ContaoFramework $framework;
    private Security $security;
    private string $projectDir;

    public function __construct(
        ContaoFramework $framework,
        Security $security,
        string $projectDir
    ) {
        $this->framework = $framework;
        $this->security = $security;
        $this->projectDir = $projectDir;
    }

    public function downloadAction(string $fileName, Request $request): Response
    {
        $this->framework->initialize();

        $pathToFileExportFolder = $request->query->get('pathToFileExportFolder');

        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'ls_shop_export')) {
            throw new AccessDeniedHttpException('Not enough permissions to download this file.');
        }

        $allowedBaseDir = realpath($this->projectDir . '/');
        if (false === $allowedBaseDir) {
            throw new RuntimeException('Configured download base directory does not exist.');
        }

        $fullPath = realpath($allowedBaseDir .'/'.$pathToFileExportFolder .'/'. $fileName);

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