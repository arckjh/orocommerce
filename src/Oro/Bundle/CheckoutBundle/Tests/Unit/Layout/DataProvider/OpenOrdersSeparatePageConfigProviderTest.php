<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Layout\DataProvider\OpenOrdersSeparatePageConfigProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class OpenOrdersSeparatePageConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var OpenOrdersSeparatePageConfigProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new OpenOrdersSeparatePageConfigProvider($this->configManager);
    }

    /**
     * @dataProvider getOpenOrdersSeparatePageConfigProvider
     * @param bool $value
     */
    public function testGetOpenOrdersSeparatePageConfig(bool $value)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.frontend_open_orders_separate_page')
            ->willReturn($value);

        $this->assertEquals($value, $this->provider->getOpenOrdersSeparatePageConfig());
    }

    /**
     * @return array
     */
    public function getOpenOrdersSeparatePageConfigProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider getShowOpenOrdersConfigProvider
     * @param bool $value
     */
    public function testGetShowOpenOrdersConfig(bool $value)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.frontend_show_open_orders')
            ->willReturn($value);

        $this->assertEquals($value, $this->provider->getShowOpenOrdersConfig());
    }

    /**
     * @return array
     */
    public function getShowOpenOrdersConfigProvider()
    {
        return [
            [true],
            [false],
        ];
    }
}
