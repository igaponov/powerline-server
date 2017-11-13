<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\User;
use Civix\CoreBundle\QueryFunction\CountPriorityActivities;
use Civix\CoreBundle\Service\AvatarManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/user")
 */
class UserController extends FOSRestController
{
    /**
     * @var AvatarManager
     * @DI\Inject("civix_core.service.avatar_manager")
     */
    private $avatarManager;

    /**
     * Profile of the authenticated user
     *
     * @Route("")
     * @Method("GET")
     *
     * @ApiDoc(
     *     authentication = true,
     *     resource=true,
     *     section="Users",
     *     output = {
     *          "class" = "Civix\CoreBundle\Entity\User",
     *          "groups" = {"api-profile"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     description="Authenticated user's profile",
     *     statusCodes={
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-profile"})
     *
     * @return User
     */
    public function getAction(): User
    {
        return $this->getUser();
    }

    /**
     * Deletes user's avatar
     *
     * @Route("/avatar")
     * @Method("DELETE")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Deletes user's avatar",
     *     statusCodes={
     *         204="Success",
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function deleteAvatarAction(): void
    {
        $this->avatarManager->deleteAvatar($this->getUser());
    }

    /**
     * Return user's statistics
     *
     * **Output format**
     *
     *     {
     *         priority_item_count: 1
     *     }
     *
     * @REST\Get("/statistics")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Return user's statistics",
     *     statusCodes={
     *         405="Method Not Allowed"
     *     }
     * )
     */
    public function getStatisticAction()
    {
        $query = new CountPriorityActivities($this->get('doctrine.orm.entity_manager'));

        return [
            'priority_item_count' => $query($this->getUser(), new \DateTime('-30 days'))
        ];
    }
}
