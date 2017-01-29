<?php

namespace Civix\ApiBundle\Controller\PublicApi;

use Civix\ApiBundle\Controller\BaseController;
use Civix\CoreBundle\Entity\TempFile;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/files")
 */
class FileController extends BaseController
{
    /**
     * Returns file for download.
     *
     * @Route("/{id}", name="civix_api_public_file")
     * @Method("GET")
     *
     * @ParamConverter("file", options={"repository_method" = "findOneByExpiredAt"})
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Groups",
     *     description="Return group's members",
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Request $request
     * @param TempFile $file
     *
     * @return array
     */
    public function getFileAction(Request $request, TempFile $file)
    {
        $request->attributes->set('_filename', $file->getFilename());
        $request->setRequestFormat($request->getFormat($file->getMimeType()));

        return unserialize($file->getBody());
    }
}