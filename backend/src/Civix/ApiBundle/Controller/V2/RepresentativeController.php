<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\CoreBundle\Entity\Representative;
use Civix\CoreBundle\Service\AvatarManager;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/representatives")
 */
class RepresentativeController extends FOSRestController
{
    /**
     * @var AvatarManager
     * @DI\Inject("civix_core.service.avatar_manager")
     */
    private $avatarManager;

    /**
     * Deletes representative's avatar
     *
     * @Route("/{id}/avatar")
     * @Method("DELETE")
     *
     * @SecureParam("representative", permission="edit")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Deletes representative's avatar",
     *     statusCodes={
     *         204="Success",
     *         405="Method Not Allowed"
     *     }
     * )
     * @param Representative $representative
     */
    public function deleteAvatarAction(Representative $representative)
    {
        $this->avatarManager->deleteAvatar($representative);
    }
}