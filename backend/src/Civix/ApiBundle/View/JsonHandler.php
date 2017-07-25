<?php
namespace Civix\ApiBundle\View;

use Civix\Component\Doctrine\ORM\Cursor;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class JsonHandler
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param ViewHandler $viewHandler
     * @param View        $view
     * @param Request     $request
     * @param string      $format
     *
     * @return Response
     *
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
     * @throws \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
     * @throws \Symfony\Component\Routing\Exception\InvalidParameterException
     */
    public function createResponse(ViewHandler $viewHandler, View $view, Request $request, $format): Response
    {
        $view->getContext()->setVersion($request->attributes->get('version'));
        $data = $view->getData();
        if ($data instanceof ConstraintViolationList && $data->count() > 0) {
            /** @var ConstraintViolation[] $iterator */
            $iterator = $data->getIterator();
            $code = Response::HTTP_BAD_REQUEST;
            $data = [
                'code' => $code,
                'message' => 'Validation Failed',
                'errors' => ['children' => []]
            ];
            foreach ($iterator as $violation) {
                if ($property = $violation->getPropertyPath()) {
                    if (!isset($data['errors']['children'][$property])) {
                        $data['errors']['children'][$property] = ['errors' => []];
                    }
                    $data['errors']['children'][$property]['errors'][] = $violation->getMessage();
                } else {
                    $data['errors']['errors'][] = $violation->getMessage();
                }
            }
            $view->setStatusCode($code);
            $view->setData($data);
        } elseif ($data instanceof Cursor) {
            $view->setData($data->getIterator()->getArrayCopy());
            $url = $this->router->generate($request->attributes->get('_route'), array_merge(
                $request->attributes->get('_route_params'),
                $request->query->all(),
                ['cursor' => $data->getNextCursor()]
            ), UrlGeneratorInterface::ABSOLUTE_URL);
            $view->getResponse()->headers->set('X-Cursor-Next', $url);
        }

        return $viewHandler->createResponse($view, $request, $format);
    }
}