<?php

namespace Oro\Bundle\MoneyOrderBundle\Method;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;

class MoneyOrderMethodProvider implements PaymentMethodProviderInterface
{
     /**
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods()
    {
        $paymentMethod = new MoneyOrder();
        return [$this->getType() => $paymentMethod];
    }

    /**
     * @param string $identifier
     * @return PaymentMethodInterface
     */
    public function getPaymentMethod($identifier)
    {
        if ($this->hasPaymentMethod($identifier)) {
            return $this->getPaymentMethods()[$identifier];
        }
        return null;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethod($identifier)
    {
        return $this->getType() === $identifier;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return MoneyOrder::TYPE;
    }
}
