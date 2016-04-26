<?php
namespace Civix\CoreBundle\Service;

class PollClassNameFactory
{
    /**
     * Get the Poll Question class name entity (prefix) based in the repository type
     *
     * @param string $type The repository type
     * @param string $prefix The base prefix for classname loading
     *
     * @return string
     */
    public static function getEntityClass($type, $prefix)
    {
        $prefix = ucfirst($prefix);
        switch($type)
        {
            case 'petition':
                $className = $prefix . 'Petition';
                break;
            case 'news':
                $className = $prefix . 'News';
                break;
            case 'payment_request':
                $className = $prefix . 'PaymentRequest';
                break;
            case 'event':
                $className = $prefix . 'Event';
                break;
            default:
                $className = $prefix;
                break;
        }

        return 'Civix\\CoreBundle\\Entity\\Poll\\Question\\' . $className;
    }
}