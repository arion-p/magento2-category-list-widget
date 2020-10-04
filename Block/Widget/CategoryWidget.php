<?php
namespace MageMontreal\CategoryWidget\Block\Widget;
use Magento\Framework\Registry;

class CategoryWidget extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    const TEMPLATES = [
        'default' => 'widget/categorywidget.phtml',
        'alphabet-list' => 'widget/alphabet_list.phtml',
        'alphabet-group' => 'widget/alphabet_group.phtml',
        'menu' => 'widget/menu.phtml',
    ];

    protected $_template = self::TEMPLATES['default'];

    const DEFAULT_IMAGE_WIDTH = 250;
    const DEFAULT_IMAGE_HEIGHT = 250;

    const ALPHABET_GROUPS = [
        '#' => ['#'],
        'A-C' => ['A', 'B', 'C'],
        'D-G' => ['D', 'E', 'F', 'G'],
        'H-K' => ['H', 'I', 'J', 'K'],
        'L-O' => ['L', 'M', 'N', 'O'],
        'P-S' => ['P', 'Q', 'R', 'S'],
        'T-W' => ['T', 'U', 'V', 'W'],
        'X-Z' => ['X', 'Y', 'Z'],
    ];

    const ORDER_BY = ['name', 'position','id'];

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

    public function getTemplate()
    {
        if (!$this->hasData('template')) {
            $this->setData('template', self::TEMPLATES[$this->getDisplayType()]);
        }

        return $this->getData('template');
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
                $categoryIds = explode(',',  $parentCategory->getChildren());
                // Remove parent category
                if (($parentCategoryKey = array_search($parentCategory->getId(), $categoryIds)) !== false) {
                    unset($categoryIds[$parentCategoryKey]);
                }
            }

            $this->setData('sub_category_ids', $categoryIds);
        }

        return $this->getData('sub_category_ids');
    }

    /**
     * Retrieve current store categories
     *
     * @return \Magento\Framework\Data\Tree\Node\Collection|\Magento\Catalog\Model\Resource\Category\Collection
     */
    public function getCategoryCollection()
    {
        if (!$this->hasData('category_collection')) {
            $categoryIds = $this->getSubCategoryIds();

            $childCategories = $this->_categoryCollectionFactory->create();

            $childCategories
                ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToSelect(['name', 'image','mm_cat_icon'])
                ->setOrder($this->getOrderBy(), 'ASC');

            if($this->getMenuOnly()) {
                $childCategories->addAttributeToFilter('include_in_menu', 1);
            }

            $this->setData('category_collection', $childCategories);
        }

        return $this->getData('category_collection');
    }

    public function getAlphabetList() {
        $collection = $this->getCategoryCollection();

        $ordered_categories = [];

        foreach ($collection as $category) {
            $letter = strtoupper(iconv('UTF-8','ASCII//TRANSLIT',$category->getName()[0]));
            if (!ctype_alpha($letter)) {
                $letter = '#';
            }

            $ordered_categories[$letter][] = $category;
            ksort($ordered_categories[$letter]);
        }
        ksort($ordered_categories);

        return $ordered_categories;
    }

    public function getAlphabetGrouped() {

        $list = $this->getAlphabetList();

        $grouped_categories = [];

        foreach (self::ALPHABET_GROUPS as $group_name => $group) {
            foreach ($group as $letter) {
                if (isset($list[$letter])) {
                    $grouped_categories[$group_name][$letter] = $list[$letter];
                }
            }
        }

        return $grouped_categories;
    }

    private function getMenuOnly()
    {
        if (empty($this->getData('menu_only'))) {
            return true;
        }
        return $this->getData('menu_only') === '1';
    }

    public function getOrderBy()
    {
        if(!$this->hasData('order_by') || !in_array($this->getData('order_by'), self::ORDER_BY)) {
            $this->setData('order_by', 'name');
        }

        return $this->getData('order_by');
    }

    public function getDisplayType() {
        if(!$this->hasData('display_type') || !isset(self::TEMPLATES[$this->getData('display_type')])) {
            $type = 'default';
            $this->setData('display_type', $type);
        }

        return $this->getData('display_type');
    }

    public function canShowImage()
    {
        return $this->getData('show_image') === '1' || in_array($this->getData('display'), ['image', 'image-name']);
    }

    public function canShowName()
    {
        return $this->getData('show_name') === '1' || in_array($this->getData('display'), ['name', 'image-name']);
    }

    public function getImageType()
    {
        return $this->hasData('image_type') && in_array($this->getData('image_type'), ['image', 'mm_cat_icon']) ? $this->getData('image_type') : 'image';
    }

}