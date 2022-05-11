/**
 * Matomo - free/libre analytics platform
 *
 * Visitor profile popup control.
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

(function ($, require) {

    var exports = require('piwik/UI');

    var ARRAY_PARAM_NAMES = ['compareDates', 'comparePeriods', 'compareSegments'];

    /**
     * Base type for Piwik UI controls. Provides functionality that all controls need (such as
     * cleanup on destruction).
     *
     * @param {Element} element The root element of the control.
     */
    var UIControl = function (element) {
        if (!element) {
            throw new Error("no element passed to UIControl constructor");
        }

        this._controlId = UIControl._nextControlId++;
        UIControl._controls.push(this);

        var $element = this.$element = $(element);
        $element.data('uiControlObject', this);

        var params = JSON.parse($element.attr('data-params') || '{}');
        for (var key in params) { // convert values in params that are arrays to comma separated string lists
            if (params[key] instanceof Array
                && ARRAY_PARAM_NAMES.indexOf(key) === -1
            ) {
                params[key] = params[key].join(',');
            }
        }
        this.param = params;

        this.props = JSON.parse($element.attr('data-props') || '{}');
    };

    /**
     * Contains all active control instances.
     */
    UIControl._controls = [];

    /**
     * Specifies the next unique control ID to use.
     */
    UIControl._nextControlId = 0;

    /**
     * Utility method that will clean up all piwik UI controls whose elements are not attached
     * to the DOM.
     *
     * TODO: instead of having other pieces of the UI manually calling cleanupUnusedControls,
     *       MutationObservers should be used
     */
    UIControl.cleanupUnusedControls = function () {
        var controls = UIControl._controls;
        // reset _controls; we will repopulate it with only active
        // controls in the loop below.
        var activeControls = UIControl._controls = [];

        for (var i = 0; i != controls.length; ++i) {
            var control = controls[i];
            if (control
                && control.$element
                && !$.contains(document.documentElement, control.$element[0])
            ) {
                controls[i] = null;
                control._destroy();

                if (!control._baseDestroyCalled) {
                    throw new Error("Error: " + control.constructor.name + "'s destroy method does not call " +
                                    "UIControl.destroy. You may have a memory leak.");
                }
            } else {
                // Control is still active / used.
                activeControls.push(control);
            }
        }
    };

    UIControl.initElements = function (klass, selector) {
        $(selector).each(function () {
            if (!$(this).attr('data-inited')) {
                var control = new klass(this);
                $(this).attr('data-inited', 1);
            }
        });
    };

    UIControl.prototype = {

        /**
         * Perform cleanup. Called when the control has been removed from the DOM. Derived
         * classes should overload this function to perform their own cleanup.
         */
        _destroy: function () {
            this.$element.removeData('uiControlObject');
            delete this.$element;

            this._baseDestroyCalled = true;
        },

        /**
         * Handle the widget resize event, if we're currently in a widget.
         *
         * TODO: should use proper resize detection (see
         * http://www.backalleycoder.com/2013/03/18/cross-browser-event-based-element-resize-detection/ )
         * with timeouts (since resizing widgets can be expensive)
         */
        onWidgetResize: function (handler) {
            var $widget = this.$element.closest('.widgetContent');
            $widget.on('widget:maximise', handler)
                   .on('widget:minimise', handler)
                   .on('widget:resize', handler);
        }
    };

    exports.UIControl = UIControl;

})(jQuery, require);