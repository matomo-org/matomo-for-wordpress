<!--
  Matomo - free/libre analytics platform

  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="pluginMeasurableSettings">
    <GroupedSettings
        :group-name="pluginName"
        :settings="measurableSettings"
        :all-setting-values="settingValues"
        @change="settingValues[`${settingsPerPlugin.pluginName}.${$event.name}`] = $event.value"
    />

    <div class="settingsFormFooter row">
      <div class="col s12">
        <input
            v-show="!isLoading"
            :disabled="isSaving"
            type="submit"
            class="button-primary"
            :value="translate('WordPress_SaveChanges')"
            @click="saveSettings()"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, DeepReadonly } from 'vue';
import { AjaxHelper } from 'CoreHome';
import {
  GroupedSettings,
  SettingsForSinglePlugin,
  Setting,
} from 'CorePluginsAdmin';

interface PluginMeasurableSettingsData {
  isLoading: boolean;
  isSaving: boolean;
  measurableSettings: DeepReadonly<Setting[]>;
  settingValues: Record<string, unknown>;
}

export default defineComponent({
  props: {
    idSite: {
      type: Number,
      required: true,
    },
    pluginName: {
      type: String,
      required: true,
    },
  },
  components: {
    GroupedSettings,
  },
  data(): PluginMeasurableSettingsData {
    return {
      isSaving: false,
      isLoading: true,
      measurableSettings: [],
      settingValues: {},
    };
  },
  created() {
    this.isLoading = true;
    AjaxHelper.fetch<SettingsForSinglePlugin[]>({
      method: 'SitesManager.getSiteSettings',
      idSite: this.idSite,
    }).then((settings) => {
      const settingsForPlugin = settings
        .find((settingsPerPlugin) => settingsPerPlugin.pluginName === this.pluginName);
      this.measurableSettings = settingsForPlugin?.settings || [];
    }).finally(() => {
      this.isLoading = false;
    });
  },
  watch: {
    measurableSettings(settings: Setting[]) {
      if (!settings.length) {
        return;
      }

      const settingValues: Record<string, unknown> = {};
      settings.forEach((setting) => {
        settingValues[`${this.pluginName}.${setting.name}`] = setting.value;
      });
      this.settingValues = settingValues;
    },
  },
  methods: {
    saveSettings() {
      // TODO
    },
  },
});
</script>
