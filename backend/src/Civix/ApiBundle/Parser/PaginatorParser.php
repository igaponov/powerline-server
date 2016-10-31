<?php
namespace Civix\ApiBundle\Parser;

use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Nelmio\ApiDocBundle\Parser\CollectionParser;
use Nelmio\ApiDocBundle\Parser\JmsMetadataParser;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use Nelmio\ApiDocBundle\Parser\PostParserInterface;

class PaginatorParser implements ParserInterface, PostParserInterface
{
    /**
     * @var JmsMetadataParser
     */
    private $jmsMetadataParser;
    /**
     * @var CollectionParser
     */
    private $collectionParser;

    public function __construct(
        JmsMetadataParser $jmsMetadataParser,
        CollectionParser $collectionParser
    ) {
        $this->jmsMetadataParser = $jmsMetadataParser;
        $this->collectionParser = $collectionParser;
    }

    /**
     * Return true/false whether this class supports parsing the given class.
     *
     * @param array $item containing the following fields: class, groups. Of which groups is optional
     *
     * @return boolean
     */
    public function supports(array $item)
    {
        return isset($item['collection']) && $item['collection'] === true
            && isset($item['collectionName']) && $item['collectionName'] === 'paginator';
    }

    /**
     * This doesn't parse anything at this stage.
     *
     * @param array $item
     *
     * @return array
     */
    public function parse(array $item)
    {
        return array();
    }

    /**
     * @param array|string $item       The string type of input to parse.
     * @param array        $parameters The previously-parsed parameters array.
     *
     * @return array
     */
    public function postParse(array $item, array $parameters)
    {
        $metadata = $this->jmsMetadataParser->parse([
            'class' => SlidingPagination::class,
            'groups' => ['paginator'],
            'options' => [],
        ]);
        $parameters = $this->collectionParser->postParse(
            $item,
            $this->jmsMetadataParser->parse($item)
        );
        $metadata['payload'] = $parameters['paginator'];

        return $metadata;
    }
}