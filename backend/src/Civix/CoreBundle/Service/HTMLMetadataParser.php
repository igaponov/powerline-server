<?php
namespace Civix\CoreBundle\Service;

use Civix\CoreBundle\Entity\Metadata;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class HTMLMetadataParser
{
    /**
     * @param string $html
     * @return Metadata
     */
    public function parse($html)
    {
        $metadata = new Metadata();
        $data = [];
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($html);
        /** @var \DOMElement $element */
        foreach ($document->getElementsByTagName("title") as $element) {
            $data['title'] = $element->nodeValue;
        }
        foreach ($document->getElementsByTagName("meta") as $element) {
            $data[$element->getAttribute('name')] = $element->getAttribute("content");
        }
        $properties = ['title', 'description', 'image'];
        $accessor = new PropertyAccessor();
        foreach ($data as $key => $item) {
            foreach ($properties as $property) {
                if (strpos($key, $property) !== false) {
                    $accessor->setValue($metadata, $property, $item);
                }
            }
        }

        return $metadata;
    }
}