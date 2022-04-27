<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\View;

use Piwik\Common;

/**
 * Post-update view
 *
 * During a Piwik software update, there will be instances of old classes
 * loaded in memory.  This is problematic as we will start to instantiate
 * new classes which may not be backward compatible.  This class provides
 * a clean bridge/transition by forcing a new request.
 *
 * This class needs to be self-contained, with no external dependencies.
 *
 */
class OneClickDone
{
    /**
     * @var string
     */
    private $tokenAuth;

    /**
     * @var string
     */
    public $error = '';

    /**
     * @var array
     */
    public $feedbackMessages;

    /**
     * Did the download over HTTPS fail?
     *
     * @var bool
     */
    public $httpsFail = false;

    public function __construct($tokenAuth)
    {
        $this->tokenAuth = $tokenAuth;
    }

    /**
     * Outputs the data.
     *
     * @return string  html
     */
    public function render()
    {
        // set response headers
        @Common::stripHeader('Pragma');
        @Common::stripHeader('Expires');
        @Common::sendHeader('Content-Type: text/html; charset=UTF-8');
        @Common::sendHeader('Cache-Control: no-store');
        @Common::sendHeader('X-Frame-Options: deny');

        $error = htmlspecialchars($this->error, ENT_QUOTES, 'UTF-8');
        $messages = htmlspecialchars(serialize($this->feedbackMessages), ENT_QUOTES, 'UTF-8');
        $tokenAuth = $this->tokenAuth;
        $httpsFail = (int) $this->httpsFail;

        // use a heredoc instead of an external file
        echo <<<END_OF_TEMPLATE
<!DOCTYPE html>
<html>
 <head>
  <meta name="robots" content="noindex,nofollow">
  <meta charset="utf-8">
  <title></title>
 </head>
 <body>
  <form name="myform" method="post" action="?module=CoreUpdater&amp;action=oneClickResults">
   <input type="hidden" name="token_auth" value="$tokenAuth" />
   <input type="hidden" name="error" value="$error" />
   <input type="hidden" name="messages" value="$messages" />
   <input type="hidden" name="httpsFail" value="$httpsFail" />
   <noscript>
    <button type="submit">Continue</button>
   </noscript>
  </form>
  <script type="text/javascript">
   document.myform.submit();
  </script>
 </body>
</html>
END_OF_TEMPLATE;
    }
}
