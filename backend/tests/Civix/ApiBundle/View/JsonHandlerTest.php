<?php

namespace Tests\Civix\ApiBundle\View;

use Civix\ApiBundle\View\JsonHandler;
use Civix\Component\Cursor\CursorInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class JsonHandlerTest extends TestCase
{
    public function testCursor()
    {
        $route = uniqid('route_', true);
        $query = ['cursor' => 0, 'limit' => 10];
        $cursor = 11;
        $params = ['id' => 1];
        $request = new Request($query, [], ['_route' => $route, '_route_params' => $params]);
        $response = new Response();
        $format = 'json';
        $iterator = new \ArrayIterator(range(1, 10));
        $data = $this->createMock(CursorInterface::class);
        $data->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);
        $data->expects($this->exactly(2))
            ->method('getNextCursor')
            ->willReturn($cursor);
        $view = new View();
        $view->setResponse($response);
        $view->setData($data);
        $router = $this->createMock(RouterInterface::class);
        $url = 'http://example.com/route/1?cursor=11&limit=10';
        $router->expects($this->once())
            ->method('generate')
            ->with(
                $route,
                array_merge($params, $query, ['cursor' => $cursor]),
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($url);
        $handler = new JsonHandler($router);
        $viewHandler = $this->getViewHandlerMock(['createResponse']);
        $viewHandler->expects($this->once())
            ->method('createResponse')
            ->with($view, $request, $format)
            ->willReturn($response);
        $this->assertSame($response, $handler->createResponse($viewHandler, $view, $request, $format));
        $this->assertSame($iterator->getArrayCopy(), $view->getData());
        $this->assertSame($url, $response->headers->get('X-Cursor-Next'));
    }

    public function testNullCursor()
    {
        $request = new Request();
        $response = new Response();
        $format = 'json';
        $iterator = new \ArrayIterator(range(1, 10));
        $data = $this->createMock(CursorInterface::class);
        $data->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);
        $data->expects($this->once())
            ->method('getNextCursor')
            ->willReturn(null);
        $view = new View();
        $view->setResponse($response);
        $view->setData($data);
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->never())
            ->method('generate');
        $handler = new JsonHandler($router);
        $viewHandler = $this->getViewHandlerMock(['createResponse']);
        $viewHandler->expects($this->once())
            ->method('createResponse')
            ->with($view, $request, $format)
            ->willReturn($response);
        $this->assertSame($response, $handler->createResponse($viewHandler, $view, $request, $format));
        $this->assertSame($iterator->getArrayCopy(), $view->getData());
        $this->assertNull($response->headers->get('X-Cursor-Next'));
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|ViewHandler
     */
    private function getViewHandlerMock(array $methods = []): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockBuilder(ViewHandler::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
