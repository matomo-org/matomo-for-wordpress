<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\UsersManager\UserNotifications;

use Piwik\Plugins\UsersManager\Model as UserModel;
abstract class UserNotificationProvider implements \Piwik\Plugins\UsersManager\UserNotifications\UserNotificationProviderInterface
{
    /** @var UserModel */
    protected $userModel;
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    protected abstract function createNotification(array $users) : \Piwik\Plugins\UsersManager\UserNotifications\UserNotificationInterface;
    protected abstract function getSetsOfUsersToNotify() : array;
    public function getUserNotificationsForDispatch() : array
    {
        $notifications = [];
        foreach (array_filter($this->getSetsOfUsersToNotify()) as $setOfUsers) {
            $notifications[] = $this->createNotification($setOfUsers);
        }
        return $notifications;
    }
}
