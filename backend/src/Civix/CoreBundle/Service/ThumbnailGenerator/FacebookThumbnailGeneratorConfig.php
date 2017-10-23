<?php

namespace Civix\CoreBundle\Service\ThumbnailGenerator;

class FacebookThumbnailGeneratorConfig
{
    /**
     * @var string
     */
    private $fontRegular;
    /**
     * @var string
     */
    private $fontBold;
    /**
     * @var string
     */
    private $fontItalic;
    /**
     * @var string
     */
    private $fontOpenSans;
    /**
     * @var string
     */
    private $colorRegular;
    /**
     * @var string
     */
    private $colorBlue;
    /**
     * @var string
     */
    private $colorLightBlue;
    /**
     * @var string
     */
    private $colorGrey;
    /**
     * @var string
     */
    private $background;
    /**
     * @var int
     */
    private $width;
    /**
     * @var int
     */
    private $padding;
    /**
     * @var int
     */
    private $fontSize;

    public function __construct(
        string $fontRegular,
        string $fontBold,
        $fontItalic,
        $fontOpenSans,
        $colorRegular = '#000',
        $colorBlue = '#000066',
        $colorLightBlue = '#55C5FF',
        $colorGrey = '#808080',
        $background = '#fff',
        $width = 480,
        $padding = 15,
        $fontSize = 20
    ) {
        $this->fontRegular = $fontRegular;
        $this->fontBold = $fontBold;
        $this->fontItalic = $fontItalic;
        $this->fontOpenSans = $fontOpenSans;
        $this->colorRegular = $colorRegular;
        $this->colorBlue = $colorBlue;
        $this->colorLightBlue = $colorLightBlue;
        $this->colorGrey = $colorGrey;
        $this->background = $background;
        $this->width = $width;
        $this->padding = $padding;
        $this->fontSize = $fontSize;
    }

    /**
     * @return string
     */
    public function getFontRegular(): string
    {
        return $this->fontRegular;
    }

    /**
     * @return string
     */
    public function getFontBold(): string
    {
        return $this->fontBold;
    }

    /**
     * @return string
     */
    public function getFontItalic(): string
    {
        return $this->fontItalic;
    }

    /**
     * @return string
     */
    public function getFontOpenSans(): string
    {
        return $this->fontOpenSans;
    }

    /**
     * @return string
     */
    public function getColorRegular(): string
    {
        return $this->colorRegular;
    }

    /**
     * @return string
     */
    public function getColorBlue(): string
    {
        return $this->colorBlue;
    }

    /**
     * @return string
     */
    public function getColorLightBlue(): string
    {
        return $this->colorLightBlue;
    }

    /**
     * @return string
     */
    public function getColorGrey(): string
    {
        return $this->colorGrey;
    }

    /**
     * @return string
     */
    public function getBackground(): string
    {
        return $this->background;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getPadding(): int
    {
        return $this->padding;
    }

    /**
     * @return int
     */
    public function getFontSize(): int
    {
        return $this->fontSize;
    }
}