<?php

namespace Civix\ApiBundle\Controller\V2_2\Group;

use Civix\ApiBundle\Form\Type\PostType;
use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Service\PostManager;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/groups/{group}/posts")
 */
class PostController
{
    /**
     * @var PostManager
     */
    private $manager;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        PostManager $manager,
        FormFactoryInterface $formFactory,
        TokenStorageInterface $tokenStorage
    ) {
        $this->manager = $manager;
        $this->formFactory = $formFactory;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Create a user's post in a group
     *
     * @REST\Post("")
     *
     * @ApiDoc(
     *     authentication=true,
     *     resource=true,
     *     section="Posts",
     *     description="Create a user's post in a group",
     *     input="Civix\ApiBundle\Form\Type\PostType",
     *     statusCodes={
     *         400="Bad Request"
     *     },
     *     responseMap={
     *          201 = {
     *              "class"="Civix\CoreBundle\Entity\Post",
     *              "groups"={"post"},
     *              "parsers" = {
     *                  "Nelmio\ApiDocBundle\Parser\JmsMetadataParser"
     *              }
     *          }
     *     }
     * )
     *
     * @View(serializerGroups={"post"}, statusCode=201)
     *
     * @param Request $request
     * @param Group $group
     *
     * @return Post|\Symfony\Component\Form\FormInterface
     */
    public function postAction(Request $request, Group $group)
    {
        $token = $this->tokenStorage->getToken();
        if (!$this->manager->checkPostLimitPerMonth($token->getUser(), $group)) {
            throw new BadRequestHttpException('Your limit of posts per month is reached.');
        }

        $form = $this->formFactory->create(PostType::class);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            /** @var Post $post */
            $post = $form->getData();
            $post->setUser($token->getUser());
            $post->setGroup($group);
            $post = $this->manager->savePost($post);

            return $post;
        }

        return $form;
    }
}
