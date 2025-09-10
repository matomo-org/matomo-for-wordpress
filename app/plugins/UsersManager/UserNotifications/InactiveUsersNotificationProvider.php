<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\UsersManager\UserNotifications;

use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Piwik;
use Piwik\Plugins\UsersManager\SystemSettings;
final class InactiveUsersNotificationProvider extends \Piwik\Plugins\UsersManager\UserNotifications\UserNotificationProvider
{
    protected function createNotification(array $users) : \Piwik\Plugins\UsersManager\UserNotifications\UserNotificationInterface
    {
        return new \Piwik\Plugins\UsersManager\UserNotifications\InactiveUsersEmailNotification($users, Piwik::getAllSuperUserAccessEmailAddresses());
    }
    protected function getSetsOfUsersToNotify() : array
    {
        $settings = StaticContainer::get(SystemSettings::class);
        if (!$settings->enableInactiveUsersNotifications->getValue()) {
            return [];
        }
        return [$this->userModel->getUsersWithoutActivityForDays()];
    }
    public function setUserNotificationDispatched(array $users) : void
    {
        $this->userModel->setInactiveUserNotificationWasSentForUsers($users, Date::factory('now')->getDatetime());
    }
}
