<?php

namespace Civix\CoreBundle\Service\ThumbnailGenerator;

use Civix\Component\ThumbnailGenerator\ObjectThumbnailGeneratorInterface;
use Civix\CoreBundle\Model\FacebookContent;
use Intervention\Image\AbstractFont;
use Intervention\Image\AbstractShape;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class FacebookThumbnailGenerator implements ObjectThumbnailGeneratorInterface
{
    private const MIN_LENGTH = 250;
    private const MAX_LENGTH = 3000;
    private const LINE_HEIGHT_FACTOR = 1.46;
    private const AVATAR_DIAMETER = 70;

    /**
     * @var ImageManager
     */
    private $manager;
    /**
     * @var FacebookThumbnailGeneratorConfig
     */
    private $config;

    public function __construct(
        ImageManager $manager,
        FacebookThumbnailGeneratorConfig $config
    ) {
        $this->manager = $manager;
        $this->config = $config;
    }

    public function supports($object): bool
    {
        return $object instanceof FacebookContent;
    }

    /**
     * @param FacebookContent $object
     * @return Image
     */
    public function generate($object): Image
    {
        $length = mb_strlen($object->getText());

        $text = $this->createText($object->getText());

        $userAvatar = $this->createAvatar($object->getUserAvatar());
        $groupAvatar = $this->createAvatar($object->getGroupAvatar());
        $logo = $this->manager
            ->make($this->config->getLogo());
        $watermark = $this->manager
            ->make($this->config->getWatermark());

        $groupName = $this->createGroupName($object->getGroupName());

        $headerHeight = 100;
        $footerHeight = 80;
        $height = $headerHeight + $text->height() + $footerHeight;
        $bodyHeight = $headerHeight + $text->height() + 35;

        $image = $this->manager
            // main
            ->canvas($this->config->getWidth(), $height, $this->config->getBackground())
            ->insert($watermark, 'top-right')

            // header
            ->insert($userAvatar, 'top-left', $this->config->getPadding(), $this->config->getPadding())
            ->text($object->getUserFullName(), 90, 45, function(AbstractFont $font) {
                $font->file($this->config->getFontBold());
                $font->size($this->config->getFontSize());
                $font->color($this->config->getColorBlue());
            })
            ->text('@'.$object->getUsername(), 90, 70, function(AbstractFont $font) {
                $font->file($this->config->getFontBold());
                $font->size($this->config->getFontSize());
                $font->color($this->config->getColorBlue());
            })
            ->insert($groupAvatar, 'top-right', $this->config->getPadding(), $this->config->getPadding())

            // text
            ->insert($text, 'top-left', $this->config->getPadding(), $headerHeight)

            // footer
            ->text(
                date('d/m/y @ g:iA'),
                $this->config->getPadding(),
                $bodyHeight,
                function(AbstractFont $font) {
                    $font->file($this->config->getFontRegular());
                    $font->size(12);
                    $font->color($this->config->getColorGrey());
                }
            )
            ->text(
                $length > self::MAX_LENGTH ? 'Read more on' : 'Posted on',
                $length > self::MAX_LENGTH ? 220 : 260,
                $bodyHeight,
                function(AbstractFont $font) {
                    $font->file($this->config->getFontRegular());
                    $font->size($this->config->getFontSize());
                    $font->color($this->config->getColorBlue());
                }
            )
            ->insert($logo, 'top-right', $this->config->getPadding(), $bodyHeight - 22)
            ->insert($groupName, 'bottom-right', $this->config->getPadding(), 10);

        return $image;
    }

    private function createAvatar($avatar): Image
    {
        $mask = $this->manager
            ->canvas(self::AVATAR_DIAMETER, self::AVATAR_DIAMETER)
            ->circle(66, 35, 35, function (AbstractShape $shape) {
                $shape->background($this->config->getBackground());
            });
        $circle = $this->manager
            ->canvas(self::AVATAR_DIAMETER, self::AVATAR_DIAMETER)
            ->circle(66, 35, 35, function (AbstractShape $shape) {
                $shape->border(2, $this->config->getColorBlue());
            })
            ->opacity(40);

        return $this->manager
            ->make($avatar)
            ->resize(self::AVATAR_DIAMETER, self::AVATAR_DIAMETER)
            ->mask($mask, true)
            ->insert($circle);
    }

    private function createText($text): Image
    {
        $text = mb_substr($text, 0, self::MAX_LENGTH);
        $length = mb_strlen($text);
        if ($length >= 1500) {
            $chars = 59;
            $size = 14;
        } elseif ($length >= 1000) {
            $chars = 50;
            $size = 17;
        } else {
            $chars = 44;
            $size = $this->config->getFontSize();
        }
        $wordwrap = wordwrap($text, $chars);
        $lines = preg_match_all("{\n}", $wordwrap) + 1;

        $height = max(128, $lines * ($size + self::LINE_HEIGHT_FACTOR));
        $width = $this->config->getWidth() - $this->config->getPadding() * 2;

        $image = $this->manager->canvas($width, $height);
        if ($length < self::MIN_LENGTH) {
            $strings = preg_split("{\n}", $wordwrap, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($strings as $key => $string) {
                $image->text($string, $width / 2, $height / 2 - (count($strings) / 2 * $size) + $key * $size,
                    function (AbstractFont $font) use ($size) {
                        $font->file($this->config->getFontOpenSans());
                        $font->size($size);
                        $font->color($this->config->getColorRegular());
                        $font->align('center');
                        $font->valign('top');
                    });
            }
        } else {
            $image->text($wordwrap, 0, $size, function (AbstractFont $font) use ($size) {
                    $font->file($this->config->getFontOpenSans());
                    $font->size($size);
                    $font->color($this->config->getColorRegular());
                }
            );
        }

        return $image;
    }

    private function createGroupName($groupName): Image
    {
        $image = $this->manager->canvas($this->config->getWidth(), 25, '#fff');
        $image
            ->text('in', 0, $this->config->getFontSize(), function(AbstractFont $font) {
                $font->file($this->config->getFontRegular());
                $font->size($this->config->getFontSize());
                $font->color($this->config->getColorBlue());
            })
            ->text($groupName, 27, $this->config->getFontSize(), function(AbstractFont $font) {
                $font->file($this->config->getFontItalic());
                $font->size($this->config->getFontSize());
                $font->color($this->config->getColorBlue());
            });
        $width = $this->config->getWidth() - 1;
        while ($width > 0) {
            for ($i = 3; $i < 23; $i++) {
                $color = $image->pickColor($width, $i, 'hex');
                if ($color !== $this->config->getBackground()) {
                    break 2;
                }
            }
            $width--;
        }
        $image->crop($width + 2, 25, 0, 0);

        return $image;
    }
}