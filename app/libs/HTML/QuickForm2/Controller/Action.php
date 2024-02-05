<?php

namespace {
    /**
     * Interface for Controller action handlers
     *
     * PHP version 5
     *
     * LICENSE:
     *
     * Copyright (c) 2006-2010, Alexey Borzov <avb@php.net>,
     *                          Bertrand Mansion <golgote@mamasam.com>
     * All rights reserved.
     *
     * Redistribution and use in source and binary forms, with or without
     * modification, are permitted provided that the following conditions
     * are met:
     *
     *    * Redistributions of source code must retain the above copyright
     *      notice, this list of conditions and the following disclaimer.
     *    * Redistributions in binary form must reproduce the above copyright
     *      notice, this list of conditions and the following disclaimer in the
     *      documentation and/or other materials provided with the distribution.
     *    * The names of the authors may not be used to endorse or promote products
     *      derived from this software without specific prior written permission.
     *
     * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
     * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
     * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
     * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
     * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
     * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
     * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
     * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
     * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
     * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
     * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
     *
     * @category   HTML
     * @package    HTML_QuickForm2
     * @author     Alexey Borzov <avb@php.net>
     * @author     Bertrand Mansion <golgote@mamasam.com>
     * @license    http://opensource.org/licenses/bsd-license.php New BSD License
     * @version    SVN: $Id: Action.php 293335 2010-01-09 20:14:38Z avb $
     * @link       http://pear.php.net/package/HTML_QuickForm2
     */
    /**
     * Interface for Controller action handlers
     *
     * @category   HTML
     * @package    HTML_QuickForm2
     * @author     Alexey Borzov <avb@php.net>
     * @author     Bertrand Mansion <golgote@mamasam.com>
     * @version    Release: @package_version@
     */
    interface HTML_QuickForm2_Controller_Action
    {
        /**
         * Performs the given action upon the given page
         *
         * @param    HTML_QuickForm2_Controller_Page page of the multipage form
         * @param    string                          action name, some action handlers
         *                                           may perform different tasks depending
         *                                           on this
         */
        public function perform(\HTML_QuickForm2_Controller_Page $page, $name);
    }
}
