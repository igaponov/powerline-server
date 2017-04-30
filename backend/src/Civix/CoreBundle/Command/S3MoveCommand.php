<?php

namespace Civix\CoreBundle\Command;

use Aws\S3\Command\S3Command;
use Guzzle\Service\Exception\CommandTransferException;
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
        $s3 = $container->get('aws_s3.client');
        $bucket = $container->getParameter('amazon_s3.bucket');
        $from = trim($input->getOption('from'), '/');
        $to = trim($input->getOption('to'), '/');
        $keys = $input->getArgument('keys');
        $batch = $objects = [];
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
        $style = new SymfonyStyle($input, $output);
        try {
            $successful = $s3->execute($batch);
            $failed = [];
        } catch (CommandTransferException $e) {
            $style->error('Errors during multi transfer');
            $successful = $e->getSuccessfulCommands();
            $failed = $e->getFailedCommands();
        }
        $closure = function (S3Command $command) {
            return [
                'name' => $command->getOperation()->getName(),
                'key' => $command->get('Key'),
                'copy' => $command->get('CopySource'),
                'errors' => implode(
                    "\n",
                    array_map(
                        function ($error) {
                            return wordwrap($error['reason'] ?? '', 45);
                        },
                        $command->getOperation()->getErrorResponses()
                    )
                ),
            ];
        };
        $style->title('Successful commands:');
        $style->table(
            ['Name', 'Key', 'Copy', 'Errors'],
            array_map($closure, $successful)
        );
        $style->title('Failed commands:');
        $style->table(
            ['Name', 'Key', 'Copy', 'Errors'],
            array_map($closure, $failed)
        );
        try {
            $s3->deleteObjects([
                'Bucket' => $bucket,
                'Objects' => $objects,
            ]);
        } catch (\Exception $e) {
            $style->error($e->getMessage());
        }
    }
}