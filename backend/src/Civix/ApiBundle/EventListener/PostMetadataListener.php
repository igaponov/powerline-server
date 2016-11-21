<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Service\HTMLMetadataParser;
use Doctrine\ORM\Event\LifecycleEventArgs;
use GuzzleHttp\Client;

class PostMetadataListener
{
    const PATTERN = '@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.\,]*(\?\S+)?)?)*)@';

    /**
     * @var HTMLMetadataParser
     */
    private $parser;

    public function __construct(HTMLMetadataParser $parser)
    {
        $this->parser = $parser;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof Post) {
            return;
        }

        if (preg_match(self::PATTERN, $entity->getBody(), $matches)) {
            $url = $matches[1];
            $response = $this->getResponse($url);
            $metadata = $this->parser->parse($response->getBody());
            if ($metadata->getTitle()) {
                $metadata->setUrl($url);
                $entity->setMetadata($metadata);
            }
        }
    }

    protected function getResponse($url)
    {
        $client = new Client();
        return $client->get($url);
    }
}