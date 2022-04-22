/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { createAngularJsAdapter } from 'CoreHome';
import TagList from './TagList.vue';

export default createAngularJsAdapter({
  component: TagList,
  scope: {
    idContainer: {
      angularJsBind: '=',
    },
    idContainerVersion: {
      angularJsBind: '=',
    },
  },
  directiveName: 'piwikTagList',
});
