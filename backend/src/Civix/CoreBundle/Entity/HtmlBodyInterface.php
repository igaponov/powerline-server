<?php
namespace Civix\CoreBundle\Entity;

interface HtmlBodyInterface
{
    /**
     * @return string
     */
    public function getBody();

    /**
     * @param string $html
     * @return $this
     */
    public function setHtmlBody($html);
}