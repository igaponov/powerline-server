<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Civix\CoreBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadFieldValueData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');
        /** @var User $user3 */
        $user3 = $this->getReference('user_3');
        $field = $this->getReference('test-group-field');

        $fieldValue = new Group\FieldValue();
        $fieldValue->setUser($user2)
            ->setField($field)
            ->setFieldValue('test-field-value-2');
        $manager->persist($fieldValue);

        $fieldValue = new Group\FieldValue();
        $fieldValue->setUser($user3)
            ->setField($field)
            ->setFieldValue('test-field-value-3');
        $manager->persist($fieldValue);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupFieldsData::class];
    }
}