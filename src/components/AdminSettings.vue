<template>
	<div id="signature_prefs" class="section">
		<h2>
			{{ t('pdfsignature', 'Signature integration') }}
		</h2>
		<div id="signature-content">
			<div class="line">
				<label for="signature-api-host">
					<KeyIcon :size="20" class="icon" />
					{{ t('pdfsignature', 'Signature API host') }}
				</label>
				<input id="signature-api-host"
					v-model="state.api_host"
					type="url"
					:placeholder="t('pdfsignature', 'server url')"
					@input="inputChanged = true">
				<label for="signature-api-key">
					<KeyIcon :size="20" class="icon" />
					{{ t('pdfsignature', 'Signature API key') }}
				</label>
				<input id="signature-api-key"
					v-model="state.api_key"
					type="password"
					:placeholder="t('pdfsignature', '...')"
					@input="inputChanged = true">
				<NcButton v-if="inputChanged"
					type="primary"
					@click="onSave">
					<template #icon>
						<NcLoadingIcon v-if="loading" />
						<ArrowRightIcon v-else />
					</template>
					{{ t('pdfsignature', 'Save') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>
<script>
import KeyIcon from 'vue-material-design-icons/Key.vue'
import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue'

import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'AdminSettings',

	components: {
		KeyIcon,
		NcButton,
		NcLoadingIcon,
		ArrowRightIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('pdfsignature', 'admin-config'),
			loading: false,
			inputChanged: false,
		}
	},
	methods: {
		onSave() {
			this.saveOptions({
				api_host: this.state.api_host,
				api_key: this.state.api_key,
			})
		},
		saveOptions(values) {
			this.loading = true
			const req = {
				values,
			}
			const url = generateUrl('/apps/pdfsignature/admin-config')
			axios.put(url, req).then((response) => {
				showSuccess(t('pdfsignature', 'PdfSignature options saved'))
				this.inputChanged = false
			}).catch((error) => {
				showError(
					t('pdfsignature', 'Failed to save PdfSignature options')
					+ ': ' + (error.response?.data?.error ?? ''),
				)
				console.error(error)
			}).then(() => {
				this.loading = false
			})
		},
	},
}
</script>
<style scoped lang="scss">
#signature_prefs {
	#signature-content {
		margin-left: 5px;
	}
	h2,
	.line,
	.settings-hint {
		display: flex;
		flex-flow: column wrap;
		align-items: center;
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 300px;
		}
	}
}
</style>
