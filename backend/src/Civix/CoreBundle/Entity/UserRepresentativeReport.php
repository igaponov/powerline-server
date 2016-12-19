<?php
namespace Civix\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_representative_report")
 */
class UserRepresentativeReport
{
    /**
     * @var User
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="Civix\CoreBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    private $user;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $president;

    /**
     * @var string
     * @ORM\Column(name="vice_president", type="string")
     */
    private $vicePresident;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $senator1;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $senator2;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $congressman;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPresident()
    {
        return $this->president;
    }

    /**
     * @param string $president
     * @return UserRepresentativeReport
     */
    public function setPresident($president)
    {
        $this->president = $president;

        return $this;
    }

    /**
     * @return string
     */
    public function getVicePresident()
    {
        return $this->vicePresident;
    }

    /**
     * @param string $vicePresident
     * @return UserRepresentativeReport
     */
    public function setVicePresident($vicePresident)
    {
        $this->vicePresident = $vicePresident;

        return $this;
    }

    /**
     * @return string
     */
    public function getSenator1()
    {
        return $this->senator1;
    }

    /**
     * @param string $senator1
     * @return UserRepresentativeReport
     */
    public function setSenator1($senator1)
    {
        $this->senator1 = $senator1;

        return $this;
    }

    /**
     * @return string
     */
    public function getSenator2()
    {
        return $this->senator2;
    }

    /**
     * @param string $senator2
     * @return UserRepresentativeReport
     */
    public function setSenator2($senator2)
    {
        $this->senator2 = $senator2;

        return $this;
    }

    /**
     * @return string
     */
    public function getCongressman()
    {
        return $this->congressman;
    }

    /**
     * @param string $congressman
     * @return UserRepresentativeReport
     */
    public function setCongressman($congressman)
    {
        $this->congressman = $congressman;

        return $this;
    }

    public function reset()
    {
        $this->setPresident(null)
            ->setVicePresident(null)
            ->setSenator1(null)
            ->setSenator2(null)
            ->setCongressman(null);
    }
}