<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves priceListId from ProductPrice entity to the context for later use.
 */
class StorePriceListInContextByProductPrice implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $productPrice = $context->getResult();
        if (!$productPrice instanceof ProductPrice) {
            return;
        }

        $priceList = $productPrice->getPriceList();
        if (null === $priceList || !$priceList->getId()) {
            return;
        }

        PriceListIdContextUtil::storePriceListId($context, $priceList->getId());
    }
}
