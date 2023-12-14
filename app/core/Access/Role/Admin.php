<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Access\Role;

use Piwik\Access\Role;
use Piwik\Piwik;
use Piwik\Url;

class Admin extends Role
{
    public const ID = 'admin';

    public function getName(): string
    {
        return Piwik::translate('UsersManager_PrivAdmin');
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getDescription(): string
    {
        return Piwik::translate('UsersManager_PrivAdminDescription', array(
            Piwik::translate('UsersManager_PrivWrite')
        ));
    }

    public function getHelpUrl(): string
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/general/faq_69/');
    }

}
