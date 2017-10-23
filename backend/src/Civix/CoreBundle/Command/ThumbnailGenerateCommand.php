<?php

namespace Civix\CoreBundle\Command;

use Civix\Component\ThumbnailGenerator\ThumbnailGeneratorInterface;
use Civix\CoreBundle\Model\TempFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Vich\UploaderBundle\Handler\UploadHandler;

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

    public function __construct(EntityManagerInterface $em, ThumbnailGeneratorInterface $converter, UploadHandler $uploadHandler)
    {
        parent::__construct('civix:thumbnail:generate');
        $this->em = $em;
        $this->converter = $converter;
        $this->uploadHandler = $uploadHandler;
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
        $class = $input->getArgument('entity');
        $property = $input->getArgument('property');
        $id = $input->getArgument('id');
        if ($id) {
            $iterator = [[$this->em->find($class, $id)]];
        } else {
            $iterator = $this->em->createQueryBuilder()
                ->from($class, 'e')
                ->getQuery()->iterate();
        }
        $accessor = new PropertyAccessor();
        $buffer = [];
        foreach ($iterator as $k => $item) {
            $object = $item[0];
            $image = $this->converter->generate($object);
            $image->encode('png', 100);
            $accessor->setValue($object, $property, new TempFile($image->getEncoded()));
            $this->uploadHandler->upload($object, $property);
            if ($k % 20 === 0) {
                $this->em->flush();
                array_walk($buffer, [$this->em, 'detach']);
            } else {
                $buffer[] = $object;
            }
        }
        $this->em->flush();
    }
}