<?php
namespace Civix\CoreBundle\Entity\Micropetitions;

use JMS\Serializer\Annotation as Serializer;

class Metadata
{
    /**
     * @var string
     * @Serializer\Expose()
     * @Serializer\Groups({
     *     "api-petitions-list",
     *     "api-petitions-info",
     *     "api-leader-micropetition",
     *     "api-activities"
     * })
     */
    private $title;

    /**
     * @var string
     * @Serializer\Expose()
     * @Serializer\Groups({
     *     "api-petitions-list",
     *     "api-petitions-info",
     *     "api-leader-micropetition",
     *     "api-activities"
     * })
     */
    private $description;

    /**
     * @var string
     * @Serializer\Expose()
     * @Serializer\Groups({
     *     "api-petitions-list",
     *     "api-petitions-info",
     *     "api-leader-micropetition",
     *     "api-activities"
     * })
     */
    private $image;

    /**
     * @var string
     * @Serializer\Expose()
     * @Serializer\Groups({
     *     "api-petitions-list",
     *     "api-petitions-info",
     *     "api-leader-micropetition",
     *     "api-activities"
     * })
     */
    private $url;

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param string $image
     * @return $this
     */
    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
}