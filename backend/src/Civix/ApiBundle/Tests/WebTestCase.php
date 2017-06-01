<?php
namespace Civix\ApiBundle\Tests;

use Civix\CoreBundle\Entity\User;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\HttpFoundation\Response;

abstract class WebTestCase extends \Liip\FunctionalTestBundle\Test\WebTestCase
{
    protected function onNotSuccessfulTest($e): void
    {
        $this->containers = [];
        parent::onNotSuccessfulTest($e);
    }

    public function assertResponseHasErrors(
        Response $response,
        array $errors,
        bool $checkExtraErrors = true
    ): void {
        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Validation Failed', $data['message']);
        /** @var array $children */
        $children = $data['errors']['children'];
        foreach ($errors as $child => $error) {
            if (is_int($child)) {
                $index = array_search($error, $data['errors']['errors'], true);
                $this->assertNotFalse($index, "\"$error\" is not in form errors.\nErrors:\n - ".implode("\n - ", $data['errors']['errors']));
                unset($data['errors']['errors'][$index]);
            } elseif (isset($children[$child]['errors'])) {
                $index = array_search($error, $children[$child]['errors'], true);
                $this->assertNotFalse($index, "\"$error\" is not in form[$child] errors.\nErrors:\n - ".implode("\n - ", $children[$child]['errors']));
                unset($children[$child]['errors'][$index]);
            } else {
                $this->fail("form[$child] has no errors (\"$error\")");
            }
        }
        if ($checkExtraErrors) {
            if (!empty($data['errors']['errors'])) {
                $this->fail("Form contains extra errors.\nErrors:\n - ".implode("\n - ", $data['errors']['errors']));
            }
            foreach ($children as $child => $array) {
                if (!empty($array['errors'])) {
                    $this->fail("Form[$child] contains extra errors.\nErrors:\n - ".implode("\n - ", $array['errors']));
                }
            }
        }
    }

    /**
     * Get the login token user passing the user object.
     * 
     * This method only will authenticate using the same
     * password than the username.
     *
     * @author Habibillah <habibillah@gmail.com>
     * @param User $user
     * @return NULL|string
     */
    protected function getLoginToken($user): ?string
    {
    	return $this->getUserToken($user->getUsername(), $user->getUsername());
    }
    
    /**
     * Get the login token user passing username and password credentials
     *
     * @author Habibillah <habibillah@gmail.com>
     * @param string $username
     * @param string $password
     * 
     * @return NULL|string
     */
    protected function getUserToken($username = NULL, $password = NULL): ?string
    {
    	$client = static::createClient();
    	$client->request('POST', '/api/secure/login', [
    			'username' => $username,
    			'password' => $password
    	]);
    
    	$response = $client->getResponse();
    
    	if ($response->getStatusCode() === 200)
    	{
    		return json_decode($response->getContent())->token;
    	}
    
    	return NULL;
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param $serializationObject
     * @param $groups
     * @param string $type
     * @return string
     */
    protected function jmsSerialization($serializationObject, $groups, $type = 'json'): string
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->getContainer()->get('jms_serializer');
        $serializerContext = SerializationContext::create()->setGroups($groups);

        return $serializer->serialize($serializationObject, $type, $serializerContext);
    }

    /**
     * @author Habibillah <habibillah@gmail.com>
     * @param $content
     * @param $class
     * @param $groups
     * @param string $type
     * @return array|mixed|object
     */
    protected function jmsDeserialization($content, $class, $groups, $type = 'json')
    {
        /** @var $serializer \JMS\Serializer\Serializer */
        $serializer = $this->getContainer()->get('jms_serializer');
        $serializerContext = DeserializationContext::create()->setGroups($groups);

        return $serializer->deserialize($content, $class, $type, $serializerContext);
    }

    /**
     * @param $content
     * @param int $page
     * @param int $count
     * @param int $total
     * @return SlidingPagination
     */
    protected function deserializePagination($content, int $page, int $count, int $total)
    {
        /** @var SlidingPagination $pagination */
        $pagination = $this->getContainer()->get('serializer')->deserialize(
            $content,
            SlidingPagination::class,
            'json'
        );
        if ($page !== null) {
            $this->assertSame($page, $pagination->getCurrentPageNumber());
        }
        if ($count !== null) {
            $this->assertSame($count, $pagination->getTotalItemCount());
            $this->assertCount($count, $pagination->getItems());
        }
        if ($total !== null) {
            $this->assertSame($total, $pagination->getItemNumberPerPage());
        }

        return $pagination;
    }
}