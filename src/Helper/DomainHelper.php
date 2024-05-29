<?php

namespace Mondu\Helper;

use Plenty\Modules\Helper\Services\WebstoreHelper;

class DomainHelper
{
    public function getDomain(): string
    {
        /** @var WebstoreHelper $webstoreHelper */
        $webstoreHelper = pluginApp(WebstoreHelper::class);

        $webstoreConfig = $webstoreHelper->getCurrentWebstoreConfiguration();

        $domain = $webstoreConfig->domainSsl;
        if ($domain == 'http://dbmaster.plenty-showcase.de' || $domain == 'http://dbmaster-beta7.plentymarkets.eu' || $domain == 'http://dbmaster-stable7.plentymarkets.eu') {
            $domain = 'https://master.plentymarkets.com';
        }

        return $domain;
    }
}
