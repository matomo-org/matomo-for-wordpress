<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\UsersManager\TokenNotifications;

use Piwik\Date;
use Piwik\Plugins\UsersManager\Model as UserModel;
abstract class TokenNotificationProvider implements \Piwik\Plugins\UsersManager\TokenNotifications\TokenNotificationProviderInterface
{
    /** @var UserModel */
    protected $userModel;
    /** @var string */
    protected $today;
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->today = Date::factory('now')->getDatetime();
    }
    protected abstract function getPeriodThreshold() : ?string;
    protected abstract function getTokensToNotify(string $periodThreshold) : array;
    protected abstract function createNotification(string $login, array $tokens) : \Piwik\Plugins\UsersManager\TokenNotifications\TokenNotification;
    public function getTokenNotificationsForDispatch() : array
    {
        $periodThreshold = $this->getPeriodThreshold();
        if (null === $periodThreshold) {
            return [];
        }
        $tokensToNotify = $this->getTokensToNotify($periodThreshold);
        $tokensToNotifyPerUser = [];
        foreach ($tokensToNotify as $t) {
            $tokensToNotifyPerUser[$t['login']][] = $t;
        }
        $notifications = [];
        foreach ($tokensToNotifyPerUser as $login => $tokens) {
            $notifications[] = $this->createNotification($login, $tokens);
        }
        return $notifications;
    }
}
