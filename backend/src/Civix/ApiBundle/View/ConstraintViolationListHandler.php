<?php
namespace Civix\ApiBundle\View;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ConstraintViolationListHandler
{
    /**
     * @param ViewHandler $viewHandler
     * @param View        $view
     * @param Request     $request
     * @param string      $format
     *
     * @return Response
     */
    public function createResponse(ViewHandler $viewHandler, View $view, Request $request, $format)
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
        }

        return $viewHandler->createResponse($view, $request, $format);
    }
}