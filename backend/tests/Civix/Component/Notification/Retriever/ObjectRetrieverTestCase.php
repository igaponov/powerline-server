<?php

namespace Tests\Civix\Component\Notification\Retriever;

use Civix\Component\Notification\Model\RecipientInterface;
use Civix\Component\Notification\Retriever\RetrieverInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

class ObjectRetrieverTestCase extends TestCase
{
    public function retrieve(string $class, string $entity, string $retriever): void
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $repository = $this->createMock(ObjectRepository::class);
        $array = [new $class($recipient)];
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $recipient])
            ->willReturn($array);
        $om = $this->createMock(ObjectManager::class);
        $om->expects($this->once())
            ->method('getRepository')
            ->with($entity)
            ->willReturn($repository);
        /** @var RetrieverInterface $retriever */
        $retriever = new $retriever($om);
        $this->assertSame($array, $retriever->retrieve($recipient));
    }
}