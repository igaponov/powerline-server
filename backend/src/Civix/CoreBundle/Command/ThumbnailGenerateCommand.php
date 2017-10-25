<?php

namespace Civix\CoreBundle\Command;

use Civix\Component\ThumbnailGenerator\ThumbnailGeneratorInterface;
use Civix\CoreBundle\Model\TempFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class ThumbnailGenerateCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ThumbnailGeneratorInterface
     */
    private $converter;
    /**
     * @var UploadHandler
     */
    private $uploadHandler;
    /**
     * @var PropertyMappingFactory
     */
    private $factory;

    public function __construct(
        EntityManagerInterface $em,
        ThumbnailGeneratorInterface $converter,
        UploadHandler $uploadHandler,
        PropertyMappingFactory $factory
    ) {
        parent::__construct('civix:thumbnail:generate');
        $this->em = $em;
        $this->converter = $converter;
        $this->uploadHandler = $uploadHandler;
        $this->factory = $factory;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity class')
            ->addArgument('property', InputArgument::REQUIRED, 'Property name')
            ->addArgument('id', InputArgument::OPTIONAL, 'Entity id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $style = new SymfonyStyle($input, $output);
        $isVerbose = $output->isVerbose();
        $class = $input->getArgument('entity');
        $property = $input->getArgument('property');
        $id = $input->getArgument('id');
        if ($id) {
            $iterator = [[$this->em->find($class, $id)]];
        } else {
            $iterator = $this->em->createQueryBuilder()
                ->select('e')
                ->from($class, 'e')
                ->getQuery()->iterate();
        }
        $accessor = PropertyAccess::createPropertyAccessor();
        $buffer = [];
        foreach ($iterator as $k => $item) {
            $object = $item[0];
            if ($isVerbose) {
                $style->comment(sprintf('Handle %s, id: %d', $class, $accessor->getValue($object, 'id')));
            }
            try {
                $this->generateThumbnail($object, $accessor, $property);
                $buffer[] = $object;
            } catch (\Exception $e) {
                $style->error($e->getMessage());
            }
            if ($k % 20 === 0) {
                $this->em->flush();
                array_walk($buffer, [$this->em, 'detach']);
            }
        }
        $this->em->flush();
        $style->success('Finished');
    }

    private function generateThumbnail($object, PropertyAccessor $accessor, string $property)
    {
        $mapping = $this->factory->fromField($object, $property);
        if (!$mapping) {
            throw new \RuntimeException(
                sprintf('No mapping found for "%s" field.', $property)
            );
        }
        $accessor->setValue($object, $mapping->getFileNamePropertyName(), bin2hex(random_bytes(10)).'.png');
        $image = $this->converter->generate($object);
        $image->encode('png', 100);
        $accessor->setValue($object, $property, new TempFile($image->getEncoded()));
        $this->uploadHandler->upload($object, $property);
    }
}