<?php

namespace Civix\CoreBundle\Entity\Poll;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Civix\CoreBundle\Serializer\Type\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * EducationalText entity.
 *
 * @ORM\Table(name="poll_educational_context")
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 * @Vich\Uploadable
 */
class EducationalContext implements ContentInterface
{
    const VIDEO_TYPE = 'video';
    const IMAGE_TYPE = 'image';
    const TEXT_TYPE = 'text';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "activity-list"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text")
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "activity-list"})
     */
    private $text = '';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "activity-list"})
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getTypes", strict=true)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="Question", inversedBy="educationalContext", cascade={"persist"})
     * @ORM\JoinColumn(name="question_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @Assert\Valid()
     */
    private $question;

    /**
     * @Vich\UploadableField(mapping="educational_image", fileNameProperty="text")
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"api-poll", "activity-list"})
     * @Serializer\Type("Image")
     * @Serializer\SerializedName("imageSrc")
     * @Serializer\Accessor(getter="getImageSrc")
     */
    private $image;

    public static function getTypes()
    {
        return [
            self::VIDEO_TYPE,
            self::IMAGE_TYPE,
            self::TEXT_TYPE,
        ];
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set text.
     *
     * @param string $text
     *
     * @return EducationalContext
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return EducationalContext
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set question.
     *
     * @param Question $question
     *
     * @return EducationalContext
     */
    public function setQuestion(Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question.
     *
     * @return Question
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return string|UploadedFile
     * @Assert\NotBlank(groups={"Default"})
     * @Assert\Image(groups={"image"})
     */
    public function getContent()
    {
        if ($this->type == self::IMAGE_TYPE) {
            return $this->image;
        } else {
            return $this->text;
        }
    }

    public function setContent($content)
    {
        if ($this->type == self::IMAGE_TYPE) {
            $this->image = $content;
        } else {
            $this->text = $content;
        }

        return $this;
    }

    public function getImageSrc()
    {
        return $this->type === $this::IMAGE_TYPE ? new Image($this, 'image') : null;
    }

    public function hasPreviewImage()
    {
        return $this->type === $this::IMAGE_TYPE || $this->type === $this::VIDEO_TYPE;
    }

    /**
     * @return null|string
     *
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"api-poll", "activity-list"})
     * @Serializer\SerializedName("preview")
     */
    public function getPreviewSrc(): ?string
    {
        if ($this->type === $this::IMAGE_TYPE) {
            return $this->text;
        }
        if ($this->type === $this::VIDEO_TYPE && $this->text) {
            return 'https://img.youtube.com/vi/'.$this->getYoutubeId($this->text).'/0.jpg';
        }

        return null;
    }

    private function getYoutubeId($url)
    {
        if (preg_match(
            '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',
            $url, $match)) {
            return $match[1];
        }

        return null;
    }
}
