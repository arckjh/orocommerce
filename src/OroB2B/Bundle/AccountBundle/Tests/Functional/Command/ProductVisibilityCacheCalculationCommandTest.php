<?php

namespace OroB2B\Bundle\AccountBundleTests\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Command\ProductVisibilityCacheCalculationCommand;

/**
 * @dbIsolation
 */
class ProductVisibilityCacheCalculationCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([]);
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $params
     * @param array $expectedMessages
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expectedBuildCacheCall
     * @param \PHPUnit_Framework_Constraint_IsInstanceOf|null $expectedArgument
     */
    public function testExecute(
        array $params,
        array $expectedMessages,
        \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expectedBuildCacheCall,
        $expectedArgument
    ) {
        $cacheBuilder = $this->getMock('OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilder');
        $cacheBuilder->expects($expectedBuildCacheCall)->method('buildCache')->with($expectedArgument);
        $this->client->getContainer()->set('orob2b_account.visibility.cache.cache_builder', $cacheBuilder);
        $result = $this->runCommand(ProductVisibilityCacheCalculationCommand::NAME, $params);
        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }
    }

    public function executeDataProvider()
    {
        return [
            'withoutParam' => [
                'params' => [],
                'expectedMessages' =>
                    [
                        'Start the process of building the cache for all websites',
                        'The cache is updated successfully',
                    ],
                'expectedBuildCacheCall' => $this->once(),
                'expectedArgument' => null,
            ],
            'withExitsIdParam' => [
                'params' => ['--website_id=1'],
                'expectedMessages' =>
                    [
                        'Start the process of building the cache for website "Default"',
                        'The cache is updated successfully',
                    ],
                'expectedBuildCacheCall' => $this->once(),
                'expectedArgument' => $this->isInstanceOf('OroB2B\Bundle\WebsiteBundle\Entity\Website'),
            ],
            'withWrongIdParam' => [
                'params' => ['--website_id=2'],
                'expectedMessages' =>
                    [
                        'Website id is not valid',
                    ],
                'expectedBuildCacheCall' => $this->never(),
                'expectedArgument' => null,
            ],
        ];
    }
}
