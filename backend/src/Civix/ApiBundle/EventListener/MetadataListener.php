<?php
namespace Civix\ApiBundle\EventListener;

use Civix\CoreBundle\Entity\Metadata;
use Civix\CoreBundle\Entity\Post;
use Civix\CoreBundle\Entity\UserPetition;
use Civix\CoreBundle\Service\HTMLMetadataParser;
use Doctrine\ORM\Event\LifecycleEventArgs;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use LayerShifter\TLDExtract\Extract;

class MetadataListener
{
    const PATTERN = '@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.\,]*(\?\S+)?)?)*)@';

    /**
     * @var Client
     */
    private $client;
    /**
     * @var HTMLMetadataParser
     */
    private $parser;
    /**
     * @var Extract
     */
    private $tldExtract;

    public function __construct(
        Client $client,
        HTMLMetadataParser $parser,
        Extract $tldExtract
    ) {
        $this->client = $client;
        $this->parser = $parser;
        $this->tldExtract = $tldExtract;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ((!$entity instanceof Post && !$entity instanceof UserPetition)
            || !preg_match_all(self::PATTERN, $entity->getBody(), $matches)
        ) {
            return;
        }

        foreach ($matches[0] as $url) {
            $result = $this->tldExtract->parse($url);
            if (!$result->isValidDomain()) {
                continue;
            }
            try {
                $response = $this->client->get($url);
            } catch (GuzzleException $e) {
                continue;
            }
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
            break;
        }
    }
}