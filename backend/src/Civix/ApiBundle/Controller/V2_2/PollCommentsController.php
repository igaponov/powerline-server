<?php
namespace Civix\ApiBundle\Controller\V2_2;

use Civix\Component\Doctrine\ORM\Cursor;
use Civix\CoreBundle\Entity\Poll\Question;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/polls/{id}/comments", requirements={"id"="\d+"})
 */
class PollCommentsController extends AbstractCommentsController
{
    /**
     * Get root comments.
     *
     * @REST\Get("")
     *
     * @QueryParam(name="cursor", requirements="\d+", default="0")
     * @QueryParam(name="limit", requirements=@Assert\Range(min="1", max="20"), default="20")
     *
     * @Security(expression="is_granted('view', entity)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Polls",
     *     description="Get comments",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Poll\Comment>",
     *          "groups" = {"api-comments", "api-comments-parent", "comments-rate"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\CollectionParser",
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Poll Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-comments", "api-comments-parent", "comments-rate"})
     *
     * @param ParamFetcher $params
     * @param Question $entity
     *
     * @return Cursor
     */
    public function getCommentsAction(ParamFetcher $params, Question $entity): Cursor
    {
        return $this->getComments($params, $entity);
    }
}