<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
    /**
     * @var WebsiteCombinedPriceListsBuilder
     */
    protected $builder;

   /**
    * @var AccountGroupCombinedPriceListsBuilder|\PHPUnit_Framework_MockObject_MockObject
    */
    protected $accountGroupBuilder;

    /**
     * @return string
     */
    protected function getPriceListToEntityRepositoryClass()
    {
        return 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->accountGroupBuilder = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new WebsiteCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector
        );
        $this->builder->setAccountGroupCombinedPriceListsBuilder($this->accountGroupBuilder);
        $this->builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $this->builder->setCombinedPriceListClassName($this->combinedPriceListClass);
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $force
     */
    public function testBuildForAll($force)
    {
        $website = new Website();

        $this->priceListToEntityRepository->expects($this->once())
            ->method('getWebsiteIteratorByFallback')
            ->with(PriceListWebsiteFallback::CONFIG)
            ->will($this->returnValue([$website]));
        $this->garbageCollector->expects($this->never())
            ->method($this->anything());

        $this->assertRebuild($force, $website);

        $this->builder->build(null, $force);
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $force
     */
    public function testBuildForWebsite($force)
    {
        $website = new Website();

        $this->priceListToEntityRepository->expects($this->never())
            ->method('getWebsiteIteratorByFallback');
        $this->garbageCollector->expects($this->once())
            ->method('cleanCombinedPriceLists');

        $this->assertRebuild($force, $website);

        $this->builder->build($website, $force);
    }

    /**
     * @param bool $force
     * @param Website $website
     */
    protected function assertRebuild($force, Website $website)
    {
        $priceListCollection = [$this->getPriceListSequenceMember()];
        $combinedPriceList = new CombinedPriceList();

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByWebsite')
            ->with($website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListCollection, $force)
            ->will($this->returnValue($combinedPriceList));

        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $website);

        $this->accountGroupBuilder->expects($this->once())
            ->method('build')
            ->with($website, null, $force);
    }
}
