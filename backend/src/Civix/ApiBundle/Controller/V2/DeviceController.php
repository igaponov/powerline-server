<?php

namespace Civix\ApiBundle\Controller\V2;

use Civix\ApiBundle\Form\Type\DeviceType;
use Civix\Component\Notification\Model\Device;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/devices")
 */
class DeviceController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Post("")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Create a device",
     *     input="Civix\ApiBundle\Form\Type\DeviceType",
     *     statusCodes={
     *         401="Authorization required",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @REST\View(statusCode=201)
     *
     * @param Request $request
     *
     * @return array|FormInterface
     */
    public function postDeviceAction(Request $request)
    {
        $token = $this->tokenStorage->getToken();
        $device = new Device($token->getUser());
        $form = $this->formFactory->create(DeviceType::class, $device);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->em->persist($device);
            $this->em->flush();

            return ['id' => $device->getId()];
        }

        return $form;
    }

    /**
     * @REST\Delete("/{id}", requirements={"id" = "^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$"})
     *
     * @Security("is_granted('delete', device)")
     *
     * @ApiDoc(
     *     authentication=true,
     *     section="Users",
     *     description="Delete a device",
     *     statusCodes={
     *         401="Authorization required",
     *         403="Access Denied",
     *         405="Method Not Allowed"
     *     }
     * )
     *
     * @param Device $device
     */
    public function deleteDeviceAction(Device $device): void
    {
        $this->em->remove($device);
        $this->em->flush();
    }
}
