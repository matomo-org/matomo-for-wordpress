/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

/**
 * If the given text or resolved expression matches any text within the element, the matching text will be wrapped
 * with a class.
 *
 * Example:
 * <div piwik-autocomplete-matched="'text'">My text</div> ==> <div>My <span class="autocompleteMatched">text</span></div>
 *
 * <div piwik-autocomplete-matched="searchTerm">{{ name }}</div>
 * <input type="text" ng-model="searchTerm">
 *
 * @deprecated
 */
(function () {
    angular.module('piwikApp.directive').directive('piwikAutocompleteMatched', piwikAutocompleteMatched);

    piwikAutocompleteMatched.$inject = ['piwik', '$sanitize'];

    /**
     * @deprecated
     */
    function piwikAutocompleteMatched(piwik, $sanitize) {

        return {
            priority: 10, // makes sure to render after other directives, otherwise the content might be overwritten again see https://github.com/piwik/piwik/pull/8467
            link: function (scope, element, attrs) {
                var searchTerm;

                scope.$watch(attrs.piwikAutocompleteMatched, function (value) {
                    searchTerm = value;
                    updateText();
                });

                function updateText() {
                    if (!searchTerm || !element) {
                        return;
                    }

                    var content = piwik.helper.htmlEntities(element.text());
                    var startTerm = content.toLowerCase().indexOf(searchTerm.toLowerCase());

                    if (-1 !== startTerm) {
                        var word = content.slice(startTerm, startTerm + searchTerm.length);
                        var escapedword = $sanitize(piwik.helper.htmlEntities(word));
                        content = content.replace(word, '<span class="autocompleteMatched">' + escapedword + '</span>');
                        element.html(content);
                    }
                }
            }
        };
    }
})();
