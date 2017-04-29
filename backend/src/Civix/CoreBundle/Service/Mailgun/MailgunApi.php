<?php
/**
 * Created by PhpStorm.
 * User: sofien
 * Date: 11/10/15
 * Time: 11:50.
 */
namespace Civix\CoreBundle\Service\Mailgun;

use Mailgun\Connection\Exceptions\MissingEndpoint;
use Mailgun\Mailgun;
use Psr\Log\LoggerInterface;

class MailgunApi
{
    const GROUP_EMAIL = '@powerlinegroups.com';

    /**
     * @var Mailgun
     */
    private $client;
    /**
     * @var Mailgun
     */
    private $publicClient;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Mailgun $client,
        Mailgun $publicClient,
        LoggerInterface $logger
    )
    {
        $this->client = $client;
        $this->publicClient = $publicClient;
        $this->logger = $logger;
    }

    public function listGetAction()
    {
        $result = $this->client->get('lists');

        return $this->JsonResponse($result);
    }

    public function listCreateAction($listname, $description)
    {
        try {
            $params = array(
                'address' => $listname.self::GROUP_EMAIL
            );
            $validation = $this->publicClient->get('address/validate', $params);
            $validationresponse = json_decode(json_encode($validation), true);
            if ($validationresponse['http_response_code'] == 200 && (isset($validationresponse['http_response_body']['is_valid']) && $validationresponse['http_response_body']['is_valid'] === false)) {
                return false;
            }
            $params = array(
                'address' => $listname.self::GROUP_EMAIL,
                'description' => $description,
                'access_level' => 'readonly',
            );
            $this->client->post('lists', $params);
        } catch (\Exception $e) {
            $this->logError($e, __METHOD__, $params);
            return false;
        }

        return true;
    }

    public function listAddMemberAction($listname, $address, $name)
    {
        $listAddress = $listname.self::GROUP_EMAIL;

        $this->logger->debug('Testing adding member '.$address);
        $params = array(
            'address' => $address,
            'name' => $name,
            'subscribed' => true,
            'upsert' => true,
        );
        try {
            $this->client->post(
                "lists/$listAddress/members",
                $params
            );
        } catch (\Exception $e) {
            $this->logError($e, __METHOD__, $params);
            return false;
        }

        return true;
    }

    public function listRemoveMemberAction($listname, $address)
    {
        $listAddress = $listname.self::GROUP_EMAIL;
        $listMember = $address;
        try {
            $this->client->delete("lists/$listAddress/members/$listMember");
        } catch (MissingEndpoint $e) {
            // if a mailing list doesn't exist - skip removing
            $this->logger->info($e->getMessage(), ['address' => $listAddress, 'member' => $listMember]);
            return true;
        } catch (\Exception $e) {
            $this->logError($e, __METHOD__, ['address' => $listAddress, 'member' => $listMember]);
            return false;
        }

        return true;
    }

    public function listRemoveAction($listname)
    {
        $listAddress = $listname.self::GROUP_EMAIL;
        try {
            $this->client->delete("lists/$listAddress");
        } catch (\Exception $e) {
            $this->logError($e, __METHOD__, ['address' => $listAddress]);
            return false;
        }

        return true;
    }

    public function listAddMembersAction($listname, array $users)
    {
        $listAddress = $listname.self::GROUP_EMAIL;
        $params = array(
            'members' => json_encode($users),
            'upsert' => true,
        );
        try {
            $this->logger->info('Testing adding members', $users);
            $this->client->post(
                "lists/$listAddress/members.json",
                $params
            );
        } catch (\Exception $e) {
            $this->logError($e, __METHOD__, $params);
            return false;
        }

        return true;
    }

    public function listExistsAction($listname)
    {
        $return = true;
        $listAddress = $listname.self::GROUP_EMAIL;
        try {
            $this->client->get(
                'lists',
                array(
                    'address' => $listAddress,
                )
            );
        } catch (MissingEndpoint $e) {
            $return = false;
        }

        return $return;
    }

    public function JsonResponse($result)
    {
        return json_decode(json_encode($result), true);
    }

    private function logError(\Exception $e, $method, $params)
    {
        $this->logger->critical(
            sprintf('Mailgun error in method %s: %s', $method, $e->getMessage()),
            $params
        );
    }
}
