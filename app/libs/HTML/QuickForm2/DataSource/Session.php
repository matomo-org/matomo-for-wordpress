<?php

namespace {
    /**
     * Class presenting the values stored in session by Controller as submitted ones
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
     * @version    SVN: $Id: Session.php 293411 2010-01-11 16:51:32Z avb $
     * @link       http://pear.php.net/package/HTML_QuickForm2
     */
    /** Interface for data sources containing submitted values */
    // require_once 'HTML/QuickForm2/DataSource/Submit.php';
    /** Array-based data source for HTML_QuickForm2 objects */
    // require_once 'HTML/QuickForm2/DataSource/Array.php';
    /**
     * Class presenting the values stored in session by Controller as submitted ones
     *
     * This is a less hackish implementation of loadValues() method in old
     * HTML_QuickForm_Controller. The values need to be presented as submitted so
     * that elements like checkboxes and multiselects do not try to use default
     * values from subsequent datasources.
     *
     * @category   HTML
     * @package    HTML_QuickForm2
     * @author     Alexey Borzov <avb@php.net>
     * @author     Bertrand Mansion <golgote@mamasam.com>
     * @version    Release: @package_version@
     */
    class HTML_QuickForm2_DataSource_Session extends \HTML_QuickForm2_DataSource_Array implements \HTML_QuickForm2_DataSource_Submit
    {
        /**
         * File upload data is not stored in the session
         */
        public function getUpload($name)
        {
            return null;
        }
    }
}
