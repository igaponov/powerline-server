<?php
namespace Civix\CoreBundle\Tests\DataFixtures\ORM;

use Civix\CoreBundle\Entity\Group;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class LoadGroupFieldsData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();
        /** @var Group $group */
        $group = $this->getReference('group_1');
        $field = new Group\GroupField();
        $field->setFieldName('test-group-field');
        $group->addField($field);
        $group->updateFillFieldsRequired();
        $this->addReference('test-group-field', $field);
        foreach (['field1', 'field2', 'field3', 'field4'] as $fieldName) {
            $field = new Group\GroupField();
            $field->setFieldName('"'.$fieldName.'`');
            $group->addField($field);
        }
        $manager->persist($group);
        $manager->flush();
        /** @var Group $group */
        $group = $this->getReference('group_3');
        $field = new Group\GroupField();
        $field->setFieldName('another-group-field');
        $group->addField($field);
        $group->updateFillFieldsRequired();
        $this->addReference('another-group-field', $field);
        $manager->persist($group);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadGroupData::class];
    }
}