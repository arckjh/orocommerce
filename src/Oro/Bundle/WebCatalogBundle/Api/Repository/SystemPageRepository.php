<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Repository;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Api\Model\SystemPage;
use Oro\Component\Routing\UrlMatcherUtil;
use Oro\Component\Routing\UrlUtil;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * The repository to get system pages.
 */
class SystemPageRepository
{
    /** @var RouterInterface */
    private $router;

    /** @var FrontendHelper */
    private $frontendHelper;

    /**
     * @param RouterInterface $router
     * @param FrontendHelper  $frontendHelper
     */
    public function __construct(
        RouterInterface $router,
        FrontendHelper $frontendHelper
    ) {
        $this->router = $router;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * Gets a system page by its route if the given route is available on the storefront.
     *
     * @param string $routeName
     *
     * @return SystemPage|null
     */
    public function findSystemPage(string $routeName): ?SystemPage
    {
        try {
            $url = $this->getUrl($routeName);
        } catch (RoutingException $e) {
            return null;
        }

        return new SystemPage($routeName, $url);
    }

    /**
     * @param string $routeName
     *
     * @return string
     *
     * @throws RoutingException if the URL cannot be retrieved
     */
    private function getUrl(string $routeName): string
    {
        $url = $this->router->generate($routeName);
        if (!$this->isFrontendUrl(UrlUtil::getPathInfo($url, $this->router->getContext()->getBaseUrl()))) {
            throw new RouteNotFoundException(sprintf(
                'The route "%s" is not allowed on the storefront.',
                $routeName
            ));
        }

        return $url;
    }

    /**
     * @param string $pathInfo
     *
     * @return bool
     */
    private function isFrontendUrl(string $pathInfo): bool
    {
        return
            $this->frontendHelper->isFrontendUrl($pathInfo)
            && $this->isGetMethodAllowed($pathInfo);
    }

    /**
     * @param string $pathInfo
     *
     * @return bool
     */
    private function isGetMethodAllowed(string $pathInfo): bool
    {
        try {
            UrlMatcherUtil::matchForGetMethod($pathInfo, $this->router);
        } catch (RoutingException $e) {
            return false;
        }

        return true;
    }
}
