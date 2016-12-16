<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Entity\Metadata;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\HTMLMetadataParser;
use Doctrine\ORM\Event\LifecycleEventArgs;
use GuzzleHttp\Client;

class MetadataListener
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

        if (!$entity instanceof Post && !$entity instanceof UserPetition) {
            return;
        }

        if (preg_match(self::PATTERN, $entity->getBody(), $matches)) {
            $url = $matches[1];
            $response = $this->getResponse($url);
            $header = $response->getHeaderLine('content-type');
            if ($header && strpos($header, 'image') === 0) {
                $metadata = new Metadata();
                $metadata->setImage($url)
                    ->setUrl($url);
                $entity->setMetadata($metadata);
            } else {
                $metadata = $this->parser->parse($response->getBody());
                if ($metadata->getTitle()) {
                    $metadata->setUrl($url);
                    $entity->setMetadata($metadata);
                }
            }
        }
    }

    protected function getResponse($url)
    {
        $client = new Client();
        return $client->get($url);
    }
}