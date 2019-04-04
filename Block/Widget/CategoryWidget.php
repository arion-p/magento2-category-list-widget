<?php
namespace MageMontreal\CategoryWidget\Block\Widget;

class CategoryWidget extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    protected $_template = 'widget/categorywidget.phtml';

    const DEFAULT_IMAGE_WIDTH = 250;
    const DEFAULT_IMAGE_HEIGHT = 250;

    /**
     * \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    protected $_categoryFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
    \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        $this->_categoryFactory = $categoryFactory;
        parent::__construct($context);
    }

    /**
     * Retrieve current store categories
     *
     * @return \Magento\Framework\Data\Tree\Node\Collection|\Magento\Catalog\Model\Resource\Category\Collection|array
     */
    public function getCategoryCollection()
    {
        $category = $this->_categoryFactory->create();

        if ($this->getData('parentcat') > 0) {
            $rootCatID = $this->getData('parentcat');
        } else {
            $rootCatID = $this->_storeManager->getStore()->getRootCategoryId();
        }

        $category->load($rootCatID);
        $childCategories = $category->getChildrenCategories()->addAttributeToSelect('image');
        return $childCategories;
    }

    /**
     * Get the width of product image
     * @return int
     */
    public function getImageWidth()
    {
        if (empty($this->getData('imagewidth'))) {
            return self::DEFAULT_IMAGE_WIDTH;
        }
        return (int) $this->getData('imagewidth');
    }

    /**
     * Get the height of product image
     * @return int
     */
    public function getImageHeight()
    {
        if (empty($this->getData('imageheight'))) {
            return self::DEFAULT_IMAGE_HEIGHT;
        }
        return (int) $this->getData('imageheight');
    }

    public function canShowImage()
    {
        return in_array($this->getData('display'), ['image', 'image-name']);
    }

    public function canShowName()
    {
        return in_array($this->getData('display'), ['name', 'image-name']);
    }
}