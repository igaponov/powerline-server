<?php
namespace Civix\ApiBundle\Controller\V2;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\UserFollow;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/user/group-followers")
 */
class UserGroupFollowerController extends FOSRestController
{
    /**
     * @var EntityManager
     * @DI\Inject("doctrine.orm.entity_manager")
     */
    private $em;

    /**
     * Follow all group members.
     * This api will automatically follow a group member 
     * if group permission is public or private.
     *
     * @Route("/{id}", requirements={"id"="\d+"})
     * @Method("PUT")
     *
     * @ParamConverter("group", class="CivixCoreBundle:Group", options={"repository_method" = "findOneNotSecret"})
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Followers",
     *     resource=true,
     *     description="Follow all group members",
     *     requirements={
     *         {
     *             "name"="id",
     *             "dataType"="integer",
     *             "requirement"="\d+",
     *             "description"="Group id"
     *         }
     *     },
     *     statusCodes={
     *         204="Success",
     *         401="Authorization required",
     *         404="Group Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     * 
     * @param Group $group
     */
    public function putGroupAction(Group $group)
    {
        $this->em->getRepository(UserFollow::class)
            ->followGroupMembers($this->getUser(), $group);
    }
}