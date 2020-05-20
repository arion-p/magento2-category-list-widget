<?php
namespace MageMontreal\CategoryWidget\Block\Widget;
use Magento\Framework\Registry;

class CategoryWidget extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    protected $_template = 'widget/categorywidget.phtml';

    const DEFAULT_IMAGE_WIDTH = 250;
    const DEFAULT_IMAGE_HEIGHT = 250;

    /**
     * Registry
     */
    protected $_registry;

    /**
     * \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    protected $_categoryFactory;

    /**
     * \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Registry $registry
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
    Registry $registry,
    \Magento\Catalog\Model\CategoryFactory $categoryFactory,
    \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
    array $data = []
    ) {
        $this->_registry = $registry;
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current category model object
     *
     * @return \Magento\Catalog\Model\Category
     */
    private function getCurrentCategory()
    {
        if (!$this->hasData('current_category')) {
            $this->setData('current_category', $this->_registry->registry('current_category'));
        }

        return $this->getData('current_category');
    }

    private function getParentCategory() {
        $category = $this->_categoryFactory->create();

        if ($this->getData('parentcat') > 0) {
            return $category->load($this->getData('parentcat'));
        } elseif($this->getCurrentCategory()) {
            return $this->getCurrentCategory();
        } else {
            return $category->load($this->_storeManager->getStore()->getRootCategoryId());
        }
    }

    private function getSubCategoryIds() {
        if (!$this->hasData('sub_category_ids')) {
            if ($this->getData('childrencat')) {
                $categoryIds = array_map('trim',explode(',', $this->getData('childrencat')));
            }
            else {
                $parentCategory = $this->getParentCategory();
                $categoryIds = $parentCategory->getAllChildren(true);
            }

            // Remove parent category
            if (($parentCategoryKey = array_search($parentCategory->getId(), $categoryIds)) !== false) {
                unset($categoryIds[$parentCategoryKey]);
            }

            $this->setData('sub_category_ids', $categoryIds);
        }

        return $this->getData('sub_category_ids');
    }

    /**
     * Retrieve current store categories
     *
     * @return \Magento\Framework\Data\Tree\Node\Collection|\Magento\Catalog\Model\Resource\Category\Collection|array
     */
    public function getCategoryCollection()
    {
        $categoryIds = $this->getSubCategoryIds();

        $childCategories = $this->_categoryCollectionFactory->create();

        $childCategories
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToSelect(['name', 'image'])
            ->setOrder('position', 'ASC');

        if($this->getMenuOnly()) {
            $childCategories->addAttributeToFilter('include_in_menu', 1);
        }

        return $childCategories;
    }

    private function getMenuOnly()
    {
        if (empty($this->getData('menu_only'))) {
            return true;
        }
        return $this->getData('menu_only') === '1';
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