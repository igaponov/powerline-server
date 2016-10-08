<?php
namespace Civix\CoreBundle\Test;

use Civix\CoreBundle\Entity\SocialActivity;
use Doctrine\ORM\EntityManager;
use Webmozart\Assert\Assert;

class SocialActivityTester
{
    private $activities = [];

    public function __construct(EntityManager $em)
    {
        $this->activities = $em->getConnection()
            ->query('SELECT type, recipient_id, following_id FROM social_activities')
            ->fetchAll(\PDO::FETCH_GROUP);
    }

    public function assertActivitiesCount($expected, $message = '')
    {
        Assert::eq($expected, array_reduce($this->activities, function ($sum, $array) {
            return $sum + count($array);
        }, 0), $message);
    }

    public function assertActivity($type, $recipient = null, $following = null, $message = '')
    {
        $constraint = new \PHPUnit_Framework_Constraint_ArrayHasKey($type);
        $constraint->evaluate($this->activities, "A social activity with a type $type is not found");
        $activities = array_filter($this->activities[$type], function ($activity) use ($recipient, $following) {
            return $activity['recipient_id'] == $recipient && $activity['following_id'] == $following;
        });
        if (!$message) {
            $message = "A social activity with parameters type=$type, recipient=$recipient and following=$following is not found";
        }
        Assert::eq(1, count($activities), $message);
    }
}