<?php

namespace Civix\ApiBundle\View;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
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
     * @param View        $view
     *
     * @return Response
     */
    public function createResponse(ViewHandler $handler, View $view)
    {
        $tempName = 'php://temp';
        $output = fopen($tempName, 'r+');
        $data = reset($handler->prepareTemplateParameters($view));
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Data must be an array');
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

        $response->headers->set('Content-Disposition', 'attachment; filename="file.csv"');
        $response->headers->set('Content-Type', 'text/csv');

        return $response;
    }
}
