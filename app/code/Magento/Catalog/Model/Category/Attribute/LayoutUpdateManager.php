<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Catalog\Model\Category\Attribute;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\DataObject;
use Magento\Framework\View\Design\Theme\FlyweightFactory;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Model\Layout\Merge as LayoutProcessor;
use Magento\Framework\View\Model\Layout\MergeFactory as LayoutProcessorFactory;
use Magento\Framework\View\Result\Page as PageLayout;

/**
 * Manage available layout updates for categories.
 */
class LayoutUpdateManager
{

    /**
     * @var FlyweightFactory
     */
    private $themeFactory;

    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @var LayoutProcessorFactory
     */
    private $layoutProcessorFactory;

    /**
     * @var LayoutProcessor|null
     */
    private $layoutProcessor;

    /**
     * @param FlyweightFactory $themeFactory
     * @param DesignInterface $design
     * @param LayoutProcessorFactory $layoutProcessorFactory
     */
    public function __construct(
        FlyweightFactory $themeFactory,
        DesignInterface $design,
        LayoutProcessorFactory $layoutProcessorFactory
    ) {
        $this->themeFactory = $themeFactory;
        $this->design = $design;
        $this->layoutProcessorFactory = $layoutProcessorFactory;
    }

    /**
     * Get the processor instance.
     *
     * @return LayoutProcessor
     */
    private function getLayoutProcessor(): LayoutProcessor
    {
        if (!$this->layoutProcessor) {
            $this->layoutProcessor = $this->layoutProcessorFactory->create(
                [
                    'theme' => $this->themeFactory->create(
                        $this->design->getConfigurationDesignTheme(Area::AREA_FRONTEND)
                    )
                ]
            );
            $this->themeFactory = null;
            $this->design = null;
        }

        return $this->layoutProcessor;
    }

    /**
     * Fetch list of available files/handles for the category.
     *
     * @param CategoryInterface $category
     * @return string[]
     */
    public function fetchAvailableFiles(CategoryInterface $category): array
    {
        $handles = $this->getLayoutProcessor()->getAvailableHandles();

        return array_filter(
            array_map(
                function (string $handle) use ($category) : ?string {
                    preg_match(
                        '/^catalog\_category\_view\_selectable\_' .$category->getId() .'\_([a-z0-9]+)/i',
                        $handle,
                        $selectable
                    );
                    if (!empty($selectable[1])) {
                        return $selectable[1];
                    }

                    return null;
                },
                $handles
            )
        );
    }

    /**
     * Extract selected custom layout settings.
     *
     * If no update is selected none will apply.
     *
     * @param CategoryInterface $category
     * @param DataObject $intoSettings
     * @return void
     */
    public function extractCustomSettings(CategoryInterface $category, DataObject $intoSettings): void
    {
        if ($attribute = $category->getCustomAttribute('custom_layout_update_file')) {
            $handles = $intoSettings->getPageLayoutHandles() ?? [];
            $handles = array_merge_recursive(
                $handles,
                ['selectable' => $category->getId() . '_' . $attribute->getValue()]
            );
            $intoSettings->setPageLayoutHandles($handles);
        }
    }
}
