<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Handler;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestProductHandlerTest extends \PHPUnit\Framework\TestCase
{
    const ROOT_CATEGORY_ID = 1;

    /** @var  Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var  RequestProductHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestProductHandler;

    public function setUp()
    {
        $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');

        /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->requestProductHandler = new RequestProductHandler($requestStack);
    }

    /**
     * @dataProvider getCategoryIdDataProvider
     *
     * @param $value
     * @param $expected
     */
    public function testGetCategoryId($value, $expected)
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::CATEGORY_ID_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getCategoryId();
        $this->assertEquals($expected, $actual);
    }

    public function testGetCategoryIdWithoutRequest()
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertFalse($requestProductHandler->getCategoryId());
    }

    /**
     * @return array
     */
    public function getCategoryIdDataProvider()
    {
        return [
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
            [
                'value' => true,
                'expected' => false,
            ],
            [
                'value' => 'true',
                'expected' => false,
            ],
            [
                'value' => 'false',
                'expected' => false,
            ],
            [
                'value' => 1,
                'expected' => 1,
            ],
            [
                'value' => 0,
                'expected' => false,
            ],
            [
                'value' => -1,
                'expected' => false,
            ],
            [
                'value' => '1',
                'expected' => true,
            ],
            [
                'value' => '0',
                'expected' => false,
            ],
            [
                'value' => '-1',
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider getIncludeSubcategoriesChoiceDataProvider
     *
     * @param $value
     * @param $expected
     */
    public function testGetIncludeSubcategoriesChoice($value, $expected)
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY, false)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getIncludeSubcategoriesChoice();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getIncludeSubcategoriesChoiceDataProvider()
    {
        return [
            [
                'value' => true,
                'expected' => true,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
            [
                'value' => 'true',
                'expected' => true,
            ],
            [
                'value' => 'false',
                'expected' => false,
            ],
            [
                'value' => 1,
                'expected' => true,
            ],
            [
                'value' => 0,
                'expected' => false,
            ],
            [
                'value' => -1,
                'expected' => RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            ],
            [
                'value' => '1',
                'expected' => true,
            ],
            [
                'value' => '0',
                'expected' => false,
            ],
            [
                'value' => '-1',
                'expected' => RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            ],
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => 'test',
                'expected' => RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            ],
        ];
    }

    public function testGetIncludeSubcategoriesChoiceWithTrueOption()
    {
        // string value is used only for showing correct value passing
        $trueValue = 'trueValue';

        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY, $trueValue)
            ->willReturn(true);
        $actual = $this->requestProductHandler->getIncludeSubcategoriesChoice($trueValue);
        $this->assertTrue($actual);
    }

    /**
     * @dataProvider getIncludeNotCategorizedProductsChoiceDataProvider
     *
     * @param $value
     * @param $expected
     */
    public function testGetIncludeNotCategorizedProductsChoice($value, $expected)
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getIncludeNotCategorizedProductsChoice();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getIncludeNotCategorizedProductsChoiceDataProvider()
    {
        return [
            [
                'value' => true,
                'expected' => true,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
            [
                'value' => 'true',
                'expected' => true,
            ],
            [
                'value' => 'false',
                'expected' => false,
            ],
            [
                'value' => 1,
                'expected' => true,
            ],
            [
                'value' => 0,
                'expected' => false,
            ],
            [
                'value' => -1,
                'expected' => RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE,
            ],
            [
                'value' => '1',
                'expected' => true,
            ],
            [
                'value' => '0',
                'expected' => false,
            ],
            [
                'value' => '-1',
                'expected' => RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE,
            ],
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => 'test',
                'expected' => RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE,
            ],
        ];
    }

    /**
     * @dataProvider getOverrideVariantConfigurationDataProvider
     *
     * @param string|int|bool $value
     * @param bool $expected
     */
    public function testGetOverrideVariantConfiguration($value, $expected)
    {
        $this->request->expects($this->once())
            ->method('get')
            ->with(RequestProductHandler::OVERRIDE_VARIANT_CONFIGURATION_KEY)
            ->willReturn($value);
        $actual = $this->requestProductHandler->getOverrideVariantConfiguration();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getOverrideVariantConfigurationDataProvider()
    {
        return [
            [
                'value' => true,
                'expected' => true,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
            [
                'value' => 'true',
                'expected' => true,
            ],
            [
                'value' => 'false',
                'expected' => false,
            ],
            [
                'value' => 1,
                'expected' => true,
            ],
            [
                'value' => 0,
                'expected' => false,
            ],
            [
                'value' => -1,
                'expected' => false,
            ],
            [
                'value' => '1',
                'expected' => true,
            ],
            [
                'value' => '0',
                'expected' => false,
            ],
            [
                'value' => '-1',
                'expected' => false,
            ],
            [
                'value' => null,
                'expected' => false,
            ],
            [
                'value' => 'test',
                'expected' => false,
            ],
        ];
    }

    public function testGetIncludeSubcategoriesChoiceWithEmptyRequest()
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertEquals(
            RequestProductHandler::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE,
            $requestProductHandler->getIncludeSubcategoriesChoice()
        );
    }

    public function testGetIncludeNotCategorizedProductsChoiceWithEmptyRequest()
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertEquals(
            RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE,
            $requestProductHandler->getIncludeNotCategorizedProductsChoice()
        );
    }

    public function testGetOverrideVariantConfigurationWithEmptyRequest()
    {
        $requestProductHandler = new RequestProductHandler(new RequestStack());
        $this->assertFalse($requestProductHandler->getOverrideVariantConfiguration());
    }
}
