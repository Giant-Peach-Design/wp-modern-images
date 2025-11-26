<?php

namespace Giantpeach\WpModernImages;

class Image
{
    private int $imageId;
    private array $sizes;
    private array $options;
    private ImageProcessor $processor;

    private array $defaultOptions = [
        'retina' => true,
        'picture_class' => '',
        'img_class' => '',
        'alt' => '',
        'loading' => 'lazy',
    ];

    public function __construct(int $imageId, array $sizes, array $options = [])
    {
        $this->imageId = $imageId;
        $this->sizes = $sizes;
        $this->options = array_merge(
            $this->defaultOptions,
            apply_filters('gp_modern_images_default_options', $this->defaultOptions),
            $options
        );
        $this->processor = ImageProcessor::getInstance();
    }

    public function render(): string
    {
        if (!$this->imageId || empty($this->sizes)) {
            return '';
        }

        $sources = $this->generateSources();
        $fallbackImg = $this->generateFallbackImg();

        if (empty($sources) || empty($fallbackImg)) {
            return '';
        }

        $pictureClass = $this->options['picture_class'] ? ' class="' . esc_attr($this->options['picture_class']) . '"' : '';

        $html = '<picture' . $pictureClass . '>' . "\n";
        $html .= implode("\n", $sources);
        $html .= "\n" . $fallbackImg;
        $html .= "\n</picture>";

        return apply_filters('gp_modern_images_picture_html', $html, $this->imageId, $this->sizes, $this->options);
    }

    private function generateSources(): array
    {
        $sources = [];
        $sortedSizes = $this->sizes;
        krsort($sortedSizes);

        foreach ($sortedSizes as $breakpoint => $sizeConfig) {
            $imageId = $this->getImageIdForSize($sizeConfig);
            [$width, $height, $fit] = $this->parseSizeConfig($sizeConfig);

            $originalFormat = $this->processor->getOriginalFormat($imageId);

            $webpSource = $this->generateSource($imageId, $width, $height, $fit, 'webp', $breakpoint);
            if ($webpSource) {
                $sources[] = $webpSource;
            }

            if ($originalFormat !== 'webp') {
                $fallbackSource = $this->generateSource($imageId, $width, $height, $fit, $originalFormat, $breakpoint);
                if ($fallbackSource) {
                    $sources[] = $fallbackSource;
                }
            }
        }

        return $sources;
    }

    private function generateSource(int $imageId, int $width, int $height, string $fit, string $format, int $breakpoint): ?string
    {
        $url1x = $this->processor->process($imageId, $width, $height, $fit, $format);
        if (!$url1x) {
            return null;
        }

        $srcset = esc_url($url1x) . ' 1x';

        if ($this->options['retina']) {
            $url2x = $this->processor->process($imageId, $width * 2, $height * 2, $fit, $format);
            if ($url2x) {
                $srcset .= ', ' . esc_url($url2x) . ' 2x';
            }
        }

        $mimeType = $this->processor->getMimeType($format);
        $mediaAttr = $breakpoint > 0 ? ' media="(min-width: ' . $breakpoint . 'px)"' : '';

        return sprintf(
            '<source%s srcset="%s" type="%s">',
            $mediaAttr,
            $srcset,
            esc_attr($mimeType)
        );
    }

    private function generateFallbackImg(): string
    {
        $smallestBreakpoint = min(array_keys($this->sizes));
        $sizeConfig = $this->sizes[$smallestBreakpoint];

        $imageId = $this->getImageIdForSize($sizeConfig);
        [$width, $height, $fit] = $this->parseSizeConfig($sizeConfig);

        $originalFormat = $this->processor->getOriginalFormat($imageId);

        $url1x = $this->processor->process($imageId, $width, $height, $fit, $originalFormat);
        if (!$url1x) {
            return '';
        }

        $srcset = esc_url($url1x) . ' 1x';

        if ($this->options['retina']) {
            $url2x = $this->processor->process($imageId, $width * 2, $height * 2, $fit, $originalFormat);
            if ($url2x) {
                $srcset .= ', ' . esc_url($url2x) . ' 2x';
            }
        }

        $alt = $this->options['alt'] ?: get_post_meta($this->imageId, '_wp_attachment_image_alt', true);
        $imgClass = $this->options['img_class'] ? ' class="' . esc_attr($this->options['img_class']) . '"' : '';
        $loading = $this->options['loading'] ? ' loading="' . esc_attr($this->options['loading']) . '"' : '';

        return sprintf(
            '<img src="%s" srcset="%s" alt="%s"%s%s>',
            esc_url($url1x),
            $srcset,
            esc_attr($alt),
            $imgClass,
            $loading
        );
    }

    private function getImageIdForSize(array $sizeConfig): int
    {
        return isset($sizeConfig[3]) ? (int) $sizeConfig[3] : $this->imageId;
    }

    private function parseSizeConfig(array $sizeConfig): array
    {
        return [
            (int) ($sizeConfig[0] ?? 0),
            (int) ($sizeConfig[1] ?? 0),
            (string) ($sizeConfig[2] ?? 'cover'),
        ];
    }
}
