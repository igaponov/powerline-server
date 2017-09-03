<?php

namespace Civix\Component\Doctrine\Common\DataFixtures;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

class FixtureLoader
{
    /**
     * @var array
     */
    private static $cachedMetadatas = array();
    /**
     * @var ORMExecutor
     */
    public static $executor;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $classNames
     * @param null $omName
     * @param null $purgeMode
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\Tools\ToolsException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function loadFixtures(array $classNames, $omName = null, $purgeMode = null): void
    {
        $container = $this->container;
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');

        $referenceRepository = new ReferenceRepository($em);
        $purger = new ORMPurger();
        if (null !== $purgeMode) {
            $purger->setPurgeMode($purgeMode);
        }
        $executor = new ORMExecutor($em, $purger);
        $executor->setReferenceRepository($referenceRepository);
        /** @var CacheProvider $cacheDriver */
        $cacheDriver = $em->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }

        $connection = $em->getConnection();
        if ($connection->getDriver() instanceof AbstractSQLiteDriver) {
            $params = $connection->getParams();
            if (isset($params['master'])) {
                $params = $params['master'];
            }

            $name = $params['path'] ?? $params['dbname'] ?? false;
            if (!$name) {
                throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
            }

            if (!isset(self::$cachedMetadatas[$omName])) {
                self::$cachedMetadatas[$omName] = $em->getMetadataFactory()->getAllMetadata();
                usort(self::$cachedMetadatas[$omName], function ($a, $b) {
                    return strcmp($a->name, $b->name);
                });
            }
            $metadatas = self::$cachedMetadatas[$omName];

            $backup = $container->getParameter('kernel.cache_dir').'/test_'.md5(serialize($metadatas)).'.db';
            $cacheSqliteDb = $container->getParameter('liip_functional_test.cache_sqlite_db');
            if ($cacheSqliteDb
                && file_exists($backup) && file_exists($backup.'.ser')
            ) {
                $em->flush();
                $em->clear();

                copy($backup, $name);

            } else {
                $schemaTool = new SchemaTool($em);
                $schemaTool->dropDatabase();
                if (!empty($metadatas)) {
                    $schemaTool->createSchema($metadatas);
                }

                if ($cacheSqliteDb) {
                    copy($name, $backup);
                }
            }
        } else {
            $executor->purge();
        }

        /** @var Loader $loader */
        $loader = $this->getFixtureLoader($container, $classNames);

        $executor->execute($loader->getFixtures(), true);

        self::$executor = $executor;
    }

    public function clear(): void
    {
        self::$executor = null;
    }

    /**
     * Retrieve Doctrine DataFixtures loader.
     *
     * @param ContainerInterface $container
     * @param array              $classNames
     *
     * @return Loader
     */
    protected function getFixtureLoader(ContainerInterface $container, array $classNames): Loader
    {
        $loaderClass = class_exists(ContainerAwareLoader::class)
            ? ContainerAwareLoader::class
            : (class_exists('Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader')
                // This class is not available during tests.
                // @codeCoverageIgnoreStart
                ? 'Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader'
                // @codeCoverageIgnoreEnd
                : 'Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader');

        $loader = new $loaderClass($container);

        foreach ($classNames as $className) {
            $this->loadFixtureClass($loader, $className);
        }

        return $loader;
    }

    /**
     * Load a data fixture class.
     *
     * @param Loader $loader
     * @param string $className
     */
    protected function loadFixtureClass($loader, $className): void
    {
        $fixture = new $className();

        if ($loader->hasFixture($fixture)) {
            unset($fixture);

            return;
        }

        $loader->addFixture($fixture);

        if ($fixture instanceof DependentFixtureInterface) {
            foreach ($fixture->getDependencies() as $dependency) {
                $this->loadFixtureClass($loader, $dependency);
            }
        }
    }
}