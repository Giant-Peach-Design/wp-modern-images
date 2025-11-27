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

    private array $defaultSizeConfig = [
        'w' => 0,
        'h' => 0,
        'fit' => 'cover',
        'q' => 80,
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
            $config = array_merge($this->defaultSizeConfig, $sizeConfig);
            $imageId = $config['image_id'] ?? $this->imageId;

            $originalFormat = $this->processor->getOriginalFormat($imageId);

            $webpSource = $this->generateSource($imageId, $config, 'webp', $breakpoint);
            if ($webpSource) {
                $sources[] = $webpSource;
            }

            if ($originalFormat !== 'webp') {
                $fallbackSource = $this->generateSource($imageId, $config, $originalFormat, $breakpoint);
                if ($fallbackSource) {
                    $sources[] = $fallbackSource;
                }
            }
        }

        return $sources;
    }

    private function generateSource(int $imageId, array $config, string $format, int $breakpoint): ?string
    {
        $url1x = $this->processor->process($imageId, $config, $format);
        if (!$url1x) {
            return null;
        }

        $srcset = esc_url($url1x) . ' 1x';

        if ($this->options['retina']) {
            $retinaConfig = array_merge($config, [
                'w' => $config['w'] * 2,
                'h' => $config['h'] * 2,
            ]);
            $url2x = $this->processor->process($imageId, $retinaConfig, $format);
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
        $config = array_merge($this->defaultSizeConfig, $sizeConfig);
        $imageId = $config['image_id'] ?? $this->imageId;

        $originalFormat = $this->processor->getOriginalFormat($imageId);

        $url1x = $this->processor->process($imageId, $config, $originalFormat);
        if (!$url1x) {
            return '';
        }

        $srcset = esc_url($url1x) . ' 1x';

        if ($this->options['retina']) {
            $retinaConfig = array_merge($config, [
                'w' => $config['w'] * 2,
                'h' => $config['h'] * 2,
            ]);
            $url2x = $this->processor->process($imageId, $retinaConfig, $originalFormat);
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
}
