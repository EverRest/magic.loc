<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\CatalogUrlRewrite\Model;

use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Model\OptionProvider;


/**
 * @magentoAppArea adminhtml
 */
class CategoryUrlRewriteGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    public function tearDown()
    {
        $category = $this->objectManager->create('Magento\Catalog\Model\Category');
        $category->load(3);
        $category->delete();
    }

    /**
     * @magentoDataFixture Magento/CatalogUrlRewrite/_files/categories.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGenerateUrlRewritesWithoutSaveHistory()
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->objectManager->create('Magento\Catalog\Model\Category');
        $category->load(3);
        $category->setData('save_rewrites_history', false);
        $category->setUrlKey('new-url');
        $category->save();

        $categoryFilter = [
            UrlRewrite::ENTITY_TYPE => CategoryUrlRewriteGenerator::ENTITY_TYPE,
            UrlRewrite::ENTITY_ID => [3, 4, 5]
        ];
        $actualResults = $this->getActualResults($categoryFilter);
        $categoryExpectedResult = [
            ['new-url.html', 'catalog/category/view/id/3', 1, 0],
            ['new-url/category-1-1.html', 'catalog/category/view/id/4', 1, 0],
            ['new-url/category-1-1/category-1-1-1.html', 'catalog/category/view/id/5', 1, 0],
        ];

        $this->assertResults($categoryExpectedResult, $actualResults);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/CatalogUrlRewrite/_files/categories.php
     * @magentoAppIsolation enabled
     */
    public function testGenerateUrlRewritesWithSaveHistory()
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->objectManager->create('Magento\Catalog\Model\Category');
        $category->load(3);
        $category->setData('save_rewrites_history', true);
        $category->setUrlKey('new-url');
        $category->save();

        $categoryFilter = [
            UrlRewrite::ENTITY_TYPE => CategoryUrlRewriteGenerator::ENTITY_TYPE,
            UrlRewrite::ENTITY_ID => [3, 4, 5]
        ];
        $actualResults = $this->getActualResults($categoryFilter);
        $categoryExpectedResult = [
            ['new-url.html', 'catalog/category/view/id/3', 1, 0],
            ['new-url/category-1-1.html', 'catalog/category/view/id/4', 1, 0],
            ['new-url/category-1-1/category-1-1-1.html', 'catalog/category/view/id/5', 1, 0],
            ['category-1.html', 'new-url.html', 0, OptionProvider::PERMANENT],
            ['category-1/category-1-1.html', 'new-url/category-1-1.html', 0, OptionProvider::PERMANENT],
            [
                'category-1/category-1-1/category-1-1-1.html',
                'new-url/category-1-1/category-1-1-1.html',
                0,
                OptionProvider::PERMANENT
            ],
        ];

        $this->assertResults($categoryExpectedResult, $actualResults);
    }

    /**
     * @param array $filter
     * @return array
     */
    protected function getActualResults(array $filter)
    {
        /** @var \Magento\UrlRewrite\Model\UrlFinderInterface $urlFinder */
        $urlFinder = $this->objectManager->get('\Magento\UrlRewrite\Model\UrlFinderInterface');
        $actualResults = [];
        foreach ($urlFinder->findAllByData($filter) as $url) {
            $actualResults[] = [
                $url->getRequestPath(),
                $url->getTargetPath(),
                (int)$url->getIsAutogenerated(),
                $url->getRedirectType()
            ];
        }
        return $actualResults;
    }

    /**
     * @param array $expected
     * @param array $actual
     */
    protected function assertResults($expected, $actual)
    {
        foreach ($expected as $row) {
            $this->assertContains(
                $row,
                $actual,
                'Expected: ' . var_export($row, true) . "\nIn Actual: " . var_export($actual, true)
            );
        }

    }
}
