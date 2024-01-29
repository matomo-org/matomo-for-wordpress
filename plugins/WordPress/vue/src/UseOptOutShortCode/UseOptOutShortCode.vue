<!--
  Matomo - free/libre analytics platform
  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="WordPressOptOutCustomizer">
    <h3>{{ translate('WordPress_UseShortCode') }}</h3>
    <p>
      <span v-html="$sanitize(shortCodeDesc1)"></span>
      <br>
      {{ translate('WordPress_UseShortCodeDesc2') }}:</p>
    <ul style="margin:20px;">
      <li style="list-style: disc">
        {{ translate('WordPress_UseShortCodeOptionLanguage') }}
      </li>
    </ul>
    <p>{{ translate('WordPress_Example') }}: <code>[matomo_opt_out language=de]</code></p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { Matomo, translate } from 'CoreHome';

export default defineComponent({
  computed: {
    shortCodeDesc1() {
      return translate('WordPress_UseShortCodeDesc1', '<code>[matomo_opt_out]</code>');
    },
  },
});

Matomo.on('PrivacyManager.UsersOptOut.preface', (components: { plugin: string, component: string }[]) => {
  components.push({
    plugin: 'WordPress',
    component: 'UseOptOutShortCode',
  });
});
</script>
