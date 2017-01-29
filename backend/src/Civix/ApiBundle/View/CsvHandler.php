<?php

namespace Civix\ApiBundle\View;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements a custom handler for CSV leveraging the ViewHandler
 *
 * @author Lukas K. Smith <smith@pooteeweet.org>
 */
class CsvHandler
{
    /**
     * Handles wrapping a JSON response into a JSONP response
     *
     * @param ViewHandler $handler
     * @param View $view
     * @param Request $request
     *
     * @return Response
     */
    public function createResponse(ViewHandler $handler, View $view, Request $request)
    {
        $tempName = 'php://temp';
        $output = fopen($tempName, 'r+');
        $data = $handler->prepareTemplateParameters($view);
        if (isset($data[$view->getTemplateVar()])) {
            $data = $data[$view->getTemplateVar()];
        } else {
            throw new \InvalidArgumentException('Data is not supported for csv serialization');
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Data must be an array');
        }
        if (count($data)) {
            fputcsv($output, array_keys(reset($data)));
        }
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        $length = ftell($output);
        if (!$length) {
            $content = '';
        } else {
            rewind($output);
            $content = fread($output, $length);
        }
        fclose($output);

        $response = new Response($content);

        $fileName = $request->attributes->get('_filename') ?: 'file.csv';
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $fileName));
        $response->headers->set('Content-Type', 'text/csv');

        return $response;
    }
}
