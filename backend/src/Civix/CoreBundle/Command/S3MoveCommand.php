<?php

namespace Civix\CoreBundle\Command;

use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\Result;
use GuzzleHttp\Promise\Promise;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class S3MoveCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('civix:s3:move')
            ->setDescription('Move S3 objects from one folder to another')
            ->addArgument('keys', InputArgument::REQUIRED|InputArgument::IS_ARRAY)
            ->addOption('from', 'f', InputOption::VALUE_REQUIRED)
            ->addOption('to', 't', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $s3 = $container->get('aws.s3');
        $bucket = $container->getParameter('amazon_s3.bucket');
        $from = trim($input->getOption('from'), '/');
        $to = trim($input->getOption('to'), '/');
        $keys = $input->getArgument('keys');
        $batch = $objects = [];
        $style = new SymfonyStyle($input, $output);
        foreach ($keys as $key) {
            $pathTo = "$to/$key";
            $pathFrom = "$from/$key";
            $batch[] = $s3->getCommand('CopyObject', [
                'Bucket'     => $bucket,
                'Key'        => $pathTo,
                'CopySource' => "$bucket/$pathFrom",
            ]);
            $objects[] = ['Key' => $pathFrom];
        }
        $results = [];
        $pool = new CommandPool($s3, $batch, [
            'fulfilled' => function (Result $result, int $num, Promise $promise) use (&$results, $bucket, $from, $to) {
                $results[$num] = [
                    'key' => substr(strstr($result->get('ObjectURL'), $bucket), strlen($bucket) + 1),
                    'source' => str_replace($to, $from, strstr($result->get('ObjectURL'), $bucket)),
                    'message' => '',
                ];

                return $promise;
            },
            'rejected' => function (AwsException $e, int $num, Promise $promise) use (&$results, &$objects) {
                $results[$num] = [
                    'key' => $e->getCommand()->offsetGet('Key'),
                    'source' => $e->getCommand()->offsetGet('CopySource'),
                    'message' => '<error>'.wordwrap($e->getAwsErrorMessage(), 40).'</error>',
                ];
                unset($objects[$num]);

                return $promise;
            }
        ]);
        $pool->promise()->wait();
        ksort($results);
        $style->title("Results:");
        $style->table(
            ['Key', 'Copy', 'Errors'],
            $results
        );
        if (count($objects) > 0) {
            try {
                $s3->deleteObjects([
                    'Bucket' => $bucket,
                    'Delete' => [
                        'Objects' => $objects,
                    ],
                ]);
            } catch (\Exception $e) {
                $style->error($e->getMessage());
            }
        }
    }
}