/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

import template from '@babel/template';

const ast = template.default.ast;

export default function ({types: t}) {
  return {
    visitor: {
      Program(path) {
        const surrounding = ast(`(function() {
        })();`);

        surrounding.expression.callee.body.body = [
          ...Object.keys(path.scope.globals).map((g) => {
            if (g === 'window') {
              return ast('var window = mtmGlobals.window;');
            } else {
              return ast(`var ${g} = mtmGlobals.window.${g};`);
            }
          }),
          ...path.node.body,
        ];
        path.node.body = [
          ast(`var mtmGlobals = {
            'window': new Proxy(window, {
              get: function (target, prop, receiver) {
                if (mtmGlobals[prop]) {
                  return mtmGlobals[prop];
                }

                return target[prop];
              },
              set: function (obj, prop, value) {
                mtmGlobals[prop] = value;
                return true;
              },
            })
          };`),
          surrounding,
        ];
      },
    },
  };
};
