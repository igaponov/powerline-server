<?php
namespace Civix\ApiBundle\Controller\V2_2;

use Civix\ApiBundle\Configuration\SecureParam;
use Civix\Component\Doctrine\ORM\Cursor;
use Civix\CoreBundle\Entity\CommentedInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/posts/{id}/comments")
 */
class PostCommentsController extends AbstractCommentsController
{
    /**
     * Get root comments.
     *
     * @Route("", requirements={"id"="\d+"})
     * @Method("GET")
     *
     * @QueryParam(name="cursor", requirements="\d+", default="0")
     * @QueryParam(name="limit", requirements=@Assert\Range(min="1", max="20"), default="20")
     *
     * @ParamConverter("entity", class="Civix\CoreBundle\Entity\Post")
     *
     * @Security(expression="is_granted('view', entity)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Posts",
     *     description="Get comments",
     *     output={
     *          "class" = "array<Civix\CoreBundle\Entity\Post\Comment>",
     *          "groups" = {"api-comments", "api-comments-parent", "comments-rate"},
     *          "parsers" = {
     *              "Nelmio\ApiDocBundle\Parser\CollectionParser",
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *          }
     *     },
     *     statusCodes={
     *         403="Access Denied",
     *         404="Post Not Found",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @View(serializerGroups={"api-comments", "api-comments-parent", "comments-rate", "comment-root"})
     *
     * @param ParamFetcher $params
     * @param CommentedInterface $entity
     *
     * @return Cursor
     */
    public function getCommentsAction(ParamFetcher $params, CommentedInterface $entity): Cursor
    {
        return $this->getComments($params, $entity);
    }
}