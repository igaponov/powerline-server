<?php

namespace Tests\AwsSesMonitorBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use SerendipityHQ\Bundle\AwsSesMonitorBundle\Model\Bounce;
use SerendipityHQ\Bundle\AwsSesMonitorBundle\Model\Complaint;
use SerendipityHQ\Bundle\AwsSesMonitorBundle\Model\EmailStatus;
use SerendipityHQ\Bundle\AwsSesMonitorBundle\Model\MailMessage;

class LoadEmailMonitorData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        // 2 hard bounces
        $status1 = new EmailStatus('jane@example.com');
        $manager->persist($status1);
        // 1 soft bounce + 1 hard
        $status2 = new EmailStatus('richard@example.com');
        $manager->persist($status2);
        // 1 complaint
        $status3 = new EmailStatus('john@example.com');
        $manager->persist($status3);

        $mailMessage1 = new MailMessage();
        $mailMessage1->setMessageId($faker->uuid)
            ->setSentOn(new \DateTime())
            ->setSentFrom('support@powerli.ne')
            ->setSourceArn('arn:aws:ses:us-west-2:888888888888:identity/example.com')
            ->setSendingAccountId('123456789012');
        $manager->persist($mailMessage1);
        
        $bounce = new Bounce();
        $bounce->setMailMessage($mailMessage1)
            ->setType(Bounce::TYPE_PERMANENT)
            ->setSubType(Bounce::TYPE_PERM_GENERAL)
            ->setBouncedOn(new \DateTime())
            ->setFeedbackId($faker->uuid);
        $status1->addBounce($bounce);
        $manager->persist($bounce);

        $bounce = new Bounce();
        $bounce->setMailMessage($mailMessage1)
            ->setType(Bounce::TYPE_TRANSIENT)
            ->setSubType(Bounce::TYPE_TRANS_ATTACHREJECTED)
            ->setBouncedOn(new \DateTime())
            ->setFeedbackId($faker->uuid);
        $status2->addBounce($bounce);
        $manager->persist($bounce);

        $mailMessage2 = new MailMessage();
        $mailMessage2->setMessageId($faker->uuid)
            ->setSentOn(new \DateTime())
            ->setSentFrom('support@powerli.ne')
            ->setSourceArn('arn:aws:ses:us-west-2:888888888888:identity/example.com')
            ->setSendingAccountId('123456789012');
        $manager->persist($mailMessage2);

        $bounce = new Bounce();
        $bounce->setMailMessage($mailMessage2)
            ->setType(Bounce::TYPE_PERMANENT)
            ->setSubType(Bounce::TYPE_PERM_SUPPRESSED)
            ->setBouncedOn(new \DateTime())
            ->setFeedbackId($faker->uuid);
        $status1->addBounce($bounce);
        $manager->persist($bounce);

        $bounce = new Bounce();
        $bounce->setMailMessage($mailMessage2)
            ->setType(Bounce::TYPE_PERMANENT)
            ->setSubType(Bounce::TYPE_PERM_NOEMAIL)
            ->setBouncedOn(new \DateTime())
            ->setFeedbackId($faker->uuid);
        $status2->addBounce($bounce);
        $manager->persist($bounce);

        $complaint = new Complaint();
        $complaint->setMailMessage($mailMessage2)
            ->setComplainedOn(new \DateTime())
            ->setFeedbackId($faker->uuid);
        $status3->addComplaint($complaint);
        $manager->persist($complaint);

        $manager->flush();
    }
}