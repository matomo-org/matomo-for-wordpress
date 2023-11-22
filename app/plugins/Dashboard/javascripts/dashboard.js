/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

function createDashboard() {
    $(makeSelectorLastId('createDashboardName')).val('');

    piwikHelper.modalConfirm(makeSelectorLastId('createDashboardConfirm'), {yes: function () {
        var dashboardName = $(makeSelectorLastId('createDashboardName')).val();
        var addDefaultWidgets = ($('[id=dashboard_type_empty]:last:checked').length > 0) ? 0 : 1;

        var ajaxRequest = new ajaxHelper();
        ajaxRequest.setLoadingElement();
        ajaxRequest.withTokenInUrl();
        ajaxRequest.addParams({
            module: 'API',
            method: 'Dashboard.createNewDashboardForUser',
            format: 'json'
        }, 'get');
        ajaxRequest.addParams({
            dashboardName: dashboardName,
            addDefaultWidgets: addDefaultWidgets,
            login: piwik.userLogin
        }, 'post');
        ajaxRequest.setCallback(
            function (response) {
                var id = response.value;
                angular.element(document).injector().invoke(function ($location, reportingMenuModel) {
                  Promise.all([
                    Dashboard.DashboardStore.reloadAllDashboards(),
                    reportingMenuModel.reloadMenuItems(),
                  ]).then(function () {
                    $('#dashboardWidgetsArea').dashboard('loadDashboard', id);
                    $('#dashboardWidgetsArea').dashboard('rebuildMenu');
                  });
                });
            }
        );
        ajaxRequest.send();
    }});
}

function makeSelectorLastId(domElementId)
{
    // there can be many elements with this id, we prefer the last one
    return '[id=' + domElementId + ']:last';
}

function resetDashboard() {
    piwikHelper.modalConfirm(makeSelectorLastId('resetDashboardConfirm'), {yes:
        function () { $('#dashboardWidgetsArea').dashboard('resetLayout');
    }});
}

function renameDashboard() {
    $(makeSelectorLastId('newDashboardName')).val($('#dashboardWidgetsArea').dashboard('getDashboardName'));

    piwikHelper.modalConfirm(makeSelectorLastId('renameDashboardConfirm'), {yes: function () {
        var newDashboardName = $(makeSelectorLastId('newDashboardName')).val();
        $('#dashboardWidgetsArea').dashboard('setDashboardName', newDashboardName);
    }});
}

function removeDashboard() {
    $(makeSelectorLastId('removeDashboardConfirm')).find('h2 span').text($('#dashboardWidgetsArea').dashboard('getDashboardName'));

    piwikHelper.modalConfirm(makeSelectorLastId('removeDashboardConfirm'), {yes: function () {
        $('#dashboardWidgetsArea').dashboard('removeDashboard');
    }});
}

function showChangeDashboardLayoutDialog() {
    $('#columnPreview').find('>div').removeClass('choosen');
    $('#columnPreview').find('>div[layout=' + $('#dashboardWidgetsArea').dashboard('getColumnLayout') + ']').addClass('choosen');

    var id = makeSelectorLastId('changeDashboardLayout');
    piwikHelper.modalConfirm(id, {yes: function () {
        var layout = $(id).find('.choosen').attr('layout');
        $('#dashboardWidgetsArea').dashboard('setColumnLayout', layout);
    }}, {fixedFooter: true});
}

function showEmptyDashboardNotification() {
    piwikHelper.modalConfirm(makeSelectorLastId('dashboardEmptyNotification'), {
        resetDashboard: function () { $('#dashboardWidgetsArea').dashboard('resetLayout'); },
        addWidget: function () {
          $('.dashboardSettings > a').trigger('click');
        }
    });
}

function setAsDefaultWidgets() {
    piwikHelper.modalConfirm(makeSelectorLastId('setAsDefaultWidgetsConfirm'), {
        yes: function () {
            $('#dashboardWidgetsArea').dashboard('saveLayoutAsDefaultWidgetLayout');
        }
    });
}

function copyDashboardToUser() {
    $(makeSelectorLastId('copyDashboardName')).val($('#dashboardWidgetsArea').dashboard('getDashboardName'));
    var ajaxRequest = new ajaxHelper();
    ajaxRequest.addParams({
        module: 'API',
        method: 'UsersManager.getUsers',
        format: 'json',
        filter_limit: '-1'
    }, 'get');
    ajaxRequest.setCallback(
        function (availableUsers) {
            $(makeSelectorLastId('copyDashboardUser')).empty();
            $(makeSelectorLastId('copyDashboardUser')).append(
                $('<option></option>').val(piwik.userLogin).text(piwik.userLogin)
            );
            $.each(availableUsers, function (index, user) {
                if (user.login != 'anonymous' && user.login != piwik.userLogin) {
                    $(makeSelectorLastId('copyDashboardUser')).append(
                        $('<option></option>').val(user.login).text(user.login)
                    );
                }
            });
        }
    );
    ajaxRequest.send();

    piwikHelper.modalConfirm(makeSelectorLastId('copyDashboardToUserConfirm'), {
        yes: function () {
            var copyDashboardName = $(makeSelectorLastId('copyDashboardName')).val();
            var copyDashboardUser = $(makeSelectorLastId('copyDashboardUser')).val();

            var ajaxRequest = new ajaxHelper();
            ajaxRequest.addParams({
                module: 'API',
                method: 'Dashboard.copyDashboardToUser',
                format: 'json'
            }, 'get');
            ajaxRequest.addParams({
                dashboardName: copyDashboardName,
                idDashboard: $('#dashboardWidgetsArea').dashboard('getDashboardId'),
                copyToUser: copyDashboardUser
            }, 'post');
            ajaxRequest.setCallback(
                function (response) {
                    $('#alert').find('h2').text(_pk_translate('Dashboard_DashboardCopied'));
                    piwikHelper.modalConfirm('#alert', {});
                }
            );
            ajaxRequest.withTokenInUrl();
            ajaxRequest.send();
        }
    });
}

(function () {
    var exports = window.require('piwik/UI');
    var UIControl = exports.UIControl;

    /**
     * Contains logic common to all dashboard management controls. This is the JavaScript analog of
     * the DashboardSettingsControlBase PHP class.
     *
     * @param {Element} element The HTML element generated by the SegmentSelectorControl PHP class. Should
     *                          have the CSS class 'segmentEditorPanel'.
     * @constructor
     */
    var DashboardSettingsControlBase = function (element) {
        UIControl.call(this, element);

        window.CoreHome.Matomo.postEvent('Dashboard.DashboardSettings.mounted', $(element)[0]);

        // on menu item click, trigger action event on this
        var self = this;
        this.$element.on('click', 'ul.submenu li[data-action]', function (e) {
            if (!$(this).attr('disabled')) {
                self.$element.removeClass('expanded');
                $(self).trigger($(this).attr('data-action'));
            }
        });

        // open manager on open
        this.$element.on('click', function (e) {
            if ($(e.target).is('.dashboardSettings') || $(e.target).closest('.dashboardSettings').length) {
                self.onOpen();
            }
        });

        // handle manager close
        this.onBodyMouseUp = function (e) {
            if (!$(e.target).closest('.dashboardSettings').length
                && !$(e.target).is('.dashboardSettings')
            ) {
                self.$element.widgetPreview('reset');
                self.$element.removeClass('expanded');
            }
        };

        $('body').on('mouseup', this.onBodyMouseUp);

        // setup widgetPreview
        this.$element.widgetPreview({
            isWidgetAvailable: function (widgetUniqueId) {
                return self.isWidgetAvailable(widgetUniqueId);
            },
            onSelect: function (widgetUniqueId) {
                widgetsHelper.getWidgetObjectFromUniqueId(widgetUniqueId, function(widget){
                    self.$element.removeClass('expanded');
                    self.widgetSelected(widget);
                });

            },
            resetOnSelect: true
        });

        // on enter widget list category, reset widget preview
        this.$element.on('mouseenter', '.submenu > li', function (event) {
            if (!$('.widgetpreview-categorylist', event.target).length) {
                self.$element.widgetPreview('reset');
            }
        });
    };

    $.extend(DashboardSettingsControlBase.prototype, UIControl.prototype, {
        _destroy: function () {
            window.CoreHome.Matomo.postEvent('Dashboard.DashboardSettings.unmounted', this.$element[0]);

            UIControl.prototype._destroy.call(this);

            $('body').off('mouseup', null, this.onBodyMouseUp);
        }
    });

    exports.DashboardSettingsControlBase = DashboardSettingsControlBase;

    /**
     * Sets up and handles events for the dashboard manager control.
     *
     * @param {Element} element The HTML element generated by the SegmentSelectorControl PHP class. Should
     *                          have the CSS class 'segmentEditorPanel'.
     * @constructor
     */
    var DashboardManagerControl = function (element) {
        DashboardSettingsControlBase.call(this, element);

        $(this).on('resetDashboard', function () {
            this.hide();
            resetDashboard();
        });

        $(this).on('showChangeDashboardLayoutDialog', function () {
            this.hide();
            showChangeDashboardLayoutDialog();
        });

        $(this).on('renameDashboard', function () {
            this.hide();
            renameDashboard();
        });

        $(this).on('removeDashboard', function () {
            this.hide();
            removeDashboard();
        });

        $(this).on('setAsDefaultWidgets', function () {
            this.hide();
            setAsDefaultWidgets();
        });

        $(this).on('copyDashboardToUser', function () {
            this.hide();
            copyDashboardToUser();
        });

        $(this).on('createDashboard', function () {
            this.hide();
            createDashboard();
        });
    };

    $.extend(DashboardManagerControl.prototype, DashboardSettingsControlBase.prototype, {
        onOpen: function () {
            if ($('#dashboardWidgetsArea').dashboard('isDefaultDashboard')) {
                $('[data-action=removeDashboard]', this.$element).attr('disabled', 'disabled');
                $(this.$element).tooltip({
                    items: '[data-action=removeDashboard]',
                    show: false,
                    hide: false,
                    track: true,
                    content: function() {
                        return _pk_translate('Dashboard_RemoveDefaultDashboardNotPossible')
                    },
                    tooltipClass: 'small'
                });
            } else {
                $('[data-action=removeDashboard]', this.$element).removeAttr('disabled');
                // try to remove tooltip if any
                try {
                    $(this.$element).tooltip('destroy');
                } catch (e) { }
             }
        },

        hide: function () {
            this.$element.removeClass('expanded');
        },

        isWidgetAvailable: function (widgetUniqueId) {
            return !$('#dashboardWidgetsArea').find('[widgetId="' + widgetUniqueId + '"]').length;
        },

        widgetSelected: function (widget) {
            $('#dashboardWidgetsArea').dashboard('addWidget', widget.uniqueId, 1, widget.parameters, true, false);
        }
    });

    DashboardManagerControl.initElements = function () {
        UIControl.initElements(this, '.dashboard-manager');
        $('.top_controls .dashboard-manager').hide(); // initially hide the manager
    };

    exports.DashboardManagerControl = DashboardManagerControl;
}());
