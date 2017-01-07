<?php

namespace Civix\ApiBundle\Controller\V2\Group;

use Civix\ApiBundle\Form\Type\PostType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Service\PostManager;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use JMS\DiExtraBundle\Annotation as DI;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/groups/{group}/posts")
 */
class PostController extends FOSRestController
{
    /**
     * @var PostManager
     * @DI\Inject("civix_core.post_manager")
     */
    private $manager;

    /**
     * Create a user's post in a group
     *
     * @Route("")
     * @Method("POST")
     *
     * @ParamConverter("group")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Posts",
     *     description="Create a user's post in a group",
     *     input="Civix\ApiBundle\Form\Type\PostType",
     *     statusCodes={
     *         400="Bad Request",
     *         405="Method Not Allowed"
     *     },
     *     responseMap={
     *          201 = {
     *              "class"="Civix\CoreBundle\Entity\Post",
     *              "groups"={"Default"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(statusCode=201)
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Post|\Symfony\Component\Form\Form
     */
    public function postAction(Request $request, Group $group)
    {
        $form = $this->createForm(new PostType(), null, ['validation_groups' => 'create']);
        $form->submit($request);

        // check limit petition
        if (!$this->manager->checkPostLimitPerMonth($this->getUser(), $group)) {
            $form->addError(new FormError('Your limit of posts per month is reached.'));
        }

        if ($form->isValid()) {
            /** @var Post $post */
            $post = $form->getData();
            $post->setUser($this->getUser());
            $post->setGroup($group);
            $post = $this->manager->savePost($post);

            return $post;
        }

        return $form;
    }
}
