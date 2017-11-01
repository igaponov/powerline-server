<?php
namespace Civix\CoreBundle\EventListener;

use Civix\CoreBundle\Entity\HasMetadataInterface;
use Civix\CoreBundle\Entity\Metadata;
use Civix\CoreBundle\Event\PostEvent;
use Civix\CoreBundle\Event\PostEvents;
use Civix\CoreBundle\Event\UserPetitionEvent;
use Civix\CoreBundle\Event\UserPetitionEvents;
use Civix\CoreBundle\Service\HTMLMetadataParser;
use GuzzleHttp\Client;
use LayerShifter\TLDExtract\Extract;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MetadataSubscriber implements EventSubscriberInterface
{
    const /** @noinspection NotOptimalRegularExpressionsInspection */
        PATTERN = '@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.\,]*(\?\S+)?)?)*)@';

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
    /**
     * @var LoggerInterface
     */
    private $logger;

    public static function getSubscribedEvents(): array
    {
        return [
            PostEvents::POST_CREATE => 'handlePost',
            UserPetitionEvents::PETITION_CREATE => 'handlePetition',
        ];
    }

    public function __construct(
        Client $client,
        HTMLMetadataParser $parser,
        Extract $tldExtract,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->parser = $parser;
        $this->tldExtract = $tldExtract;
        $this->logger = $logger;
    }

    public function handlePost(PostEvent $event)
    {
        $entity = $event->getPost();
        $this->parseMetadata($entity, $entity->getBody());
    }

    public function handlePetition(UserPetitionEvent $event)
    {
        $entity = $event->getPetition();
        $this->parseMetadata($entity, $entity->getBody());
    }

    private function parseMetadata(HasMetadataInterface $entity, string $body)
    {
        try {
            $metadata = $this->parseBody($body);
            if ($metadata) {
                $entity->setMetadata($metadata);
            }
        } catch (\Exception $e) {
            $this->logger->critical('Metadata parsing error.', ['body' => $body, 'e' => $e]);
        }
    }

    private function parseBody(string $body): ?Metadata
    {
        /** @var array[] $matches */
        preg_match_all(self::PATTERN, $body, $matches);
        foreach ($matches[0] as $url) {
            $result = $this->tldExtract->parse($url);
            if (!$result->isValidDomain()) {
                continue;
            }
            try {
                $metadata = $this->parse($url);
                if ($metadata) {
                    return $metadata;
                }
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
                continue;
            }
        }

        return null;
    }

    private function parse($url): ?Metadata
    {
        $response = $this->client->get($url);
        $header = $response->getHeaderLine('content-type');
        if ($header && strpos($header, 'image') === 0) {
            $metadata = (new Metadata())
                ->setImage($url)
                ->setUrl($url);

            return $metadata;
        }
        $metadata = $this->parser->parse($response->getBody());
        if ($metadata->getTitle()) {
            $metadata->setUrl($url);

            return $metadata;
        }

        return null;
    }
}