module.exports = {
  "extends": [
    "../../app/.eslintrc.js"
  ],
  rules: {
    // eslint is not able to locate dependencies in app/node_modules since the
    // WordPress plugin is located outside of app/
    'import/no-extraneous-dependencies': 'off',
  }
};
