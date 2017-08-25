<?php
namespace Civix\CoreBundle\Entity;

interface HtmlBodyInterface
{
    /**
     * @return string
     */
    public function getBody(): ?string ;

    /**
     * @param string $html
     * @return $this
     */
    public function setHtmlBody(string $html);
}