<template>
	<div>
		<NcModal
			v-if="modal"
			title=""
			@close="closeModal">
			<div class="modal__content">
				<h2>{{ title }}</h2>
				<FileUpload
					:label-placeholder="labelP12"
					accept=".p12, .pfx"
					only="pkcs12"
					@file-uploaded="getUploadedData" />
				<NcPasswordField :value.sync="passwd" :label="label" />
				<NcCheckboxRadioSwitch :checked.sync="logo" value="img" name="logo">
					{{ check_logo }}
				</NcCheckboxRadioSwitch>
				<div v-show="logo.length > 0" class="container">
					<div class="container__form">
						<NcSelect v-model="pageValue"
							class="container__select"
							:placeholder="label_page"
							:options="[{id:'fp',label:firstPage}, {id:'lp', label:lastPage}]" />

						<NcTextField id="coord"
							:value.sync="positionValue"
							:placeholder="label_position"
							:helper-text="helperText"
							@click="showCoordsWindow" />
						<CustomLoadingIcon :loading="loading_pdf" />
						<NcModal
							v-if="viewer"
							@close="closeViewer">
							<PdfCanvas :image-data="pdfImgSrc" @set-logo-place="handleLogoCoords" />
						</NcModal>

						<NcCheckboxRadioSwitch
							class="container__select"
							:checked.sync="search"
							value=""
							name="search">
							{{ check_search_img }}
						</NcCheckboxRadioSwitch>
						<FileUpload v-if="search.length > 0"
							:label-placeholder="labelImage"
							only="image"
							accept=".jpg, .jpeg, .png"
							class="container__select"
							@file-uploaded="getUploadedImg" />
						<NcPopover v-else
							:shown.sync="open_popup"
							class="container__select"
							popup-role="dialog">
							<template #trigger>
								<NcTextField id="cloud_image"
									:value.sync="imageValue"
									:placeholder="labelOnCloud"
									@click="initialLoading" />
							</template>
							<template #default>
								<div class="container__select">
									<div class="browser_bar">
										<NcButton
											type="tertiary-no-background"
											@click="goHome">
											<template #icon>
												<NcIconSvgWrapper :svg="homeSvg" />
											</template>
										</NcButton>
										<NcButton
											type="tertiary-no-background"
											:disabled="isRootPath"
											@click="goBack">
											<template #icon>
												<NcIconSvgWrapper :svg="backSvg" />
											</template>
										</NcButton>
									</div>
									<div role="dialog" class="container_dialog">
										<span v-if="browser_data.length === 0" class="empty_item">
											{{ label_files }}
										</span>
										<div v-for="item in browser_data" v-else :key="item.etag">
											<span class="item_wrapper" @click="getItemValue(item)">
												<NcIconSvgWrapper size="40" :svg="item.type === 'directory' ? folderSvg: imageSvg" />{{ item.basename }}
											</span>
										</div>
										<CustomLoadingIcon :loading="loading" />
									</div>
								</div>
							</template>
						</NcPopover>
					</div>
				</div>
				<NcButton style="margin-top: 0.8rem;"
					:disabled="canEnableButton()"
					type="primary"
					@click="signDoc">
					{{ btnLabel }}
				</NcButton>
			</div>
		</NcModal>
		<CustomLoadingIcon :loading="loading" />
	</div>
</template>
<script>
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import FileUpload from './FileUpload.vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcPopover from '@nextcloud/vue/dist/Components/NcPopover.js'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { emit } from '@nextcloud/event-bus'

import { showInfo, showError, showWarning } from '@nextcloud/dialogs'
import CustomLoadingIcon from './CustomLoadingIcon.vue'

import { davGetClient, davGetDefaultPropfind, davResultToNode, davRootPath } from '@nextcloud/files'

import folderSvg from '@mdi/svg/svg/folder.svg'
import imageSvg from '@mdi/svg/svg/image.svg'
import homeSvg from '@mdi/svg/svg/home.svg'
import backSvg from '@mdi/svg/svg/step-backward.svg'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import PdfCanvas from './PdfCanvas.vue'

export default {
	name: 'SignerForm',
	components: {
		NcModal,
		NcButton,
		NcPasswordField,
		FileUpload,
		CustomLoadingIcon,
		NcCheckboxRadioSwitch,
		NcSelect,
		NcPopover,
		NcIconSvgWrapper,
		NcTextField,
		PdfCanvas,
	},
	data() {
		return {
			modal: false,
			viewer: false,
			open_popup: false,
			open_viewer: false,
			current_filename: davRootPath,
			file: {},
			img_file: {},
			passwd: '',
			pdf: '',
			path: '',
			loading: false,
			loading_pdf: false,
			label: t('pdfsignature', 'Digital id password'),
			label_page: t('pdfsignature', 'Select page'),
			label_position: t('pdfsignature', 'Select position'),
			label_image: t('pdfsignature', 'Select image'),
			label_files: t('pdfsignature', 'No images in here'),
			title: t('pdfsignature', 'Sign document'),
			btnLabel: t('pdfsignature', 'Sign document'),
			labelOnCloud: t('pdfsignature', 'Find a image on cloud...'),
			labelP12: t('pdfsignature', 'Pick your id digital...'),
			labelImage: t('pdfsignature', 'Pick your logo...'),
			check_logo: t('pdfsignature', 'Sign with logo'),
			check_search_img: t('pdfsignature', 'Search image on computer'),
			helperText: t('pdfsignature', '* Move the box to desire place, then press doble click *'),
			logo: [],
			search: [],
			firstPage: t('pdfsignature', 'First page'),
			lastPage: t('pdfsignature', 'Last page'),
			options_image: [],
			pageValue: null,
			positionValue: '',
			imageValue: '',
			browser_data: [],
			cache: new Map(),
			folderSvg,
			imageSvg,
			homeSvg,
			backSvg,
			pdfImgSrc: '',
		}
	},
	computed: {
		getRootNode() {
			return davRootPath
		},
		isRootPath() {
			return this.current_filename === davRootPath
		},
	},
	methods: {
		showModal(data) {
			this.passwd = ''
			this.file = {}
			this.img_file = {}
			this.modal = true
			this.pdf = data.basename
			this.path = data.dirname
			this.pageValue = null
			this.positionValue = null
			this.imageValue = ''
			this.logo = ['img']
			this.search = []
			this.cache.clear()
			this.pdfImgSrc = ''
		},
		showCoordsWindow() {
			this.loading_pdf = true
			const url = generateUrl('/apps/pdfsignature/request-pdfpage')
			axios.post(url, {
				path: this.path,
				document: this.pdf,
				pdf_page: this.pageValue?.id,
			}).then(async (response) => {
				this.pdfImgSrc = await response.data
				this.loading_pdf = false
				this.viewer = true
			})
		},
		handleLogoCoords(x, y) {
			this.closeViewer()
			this.positionValue = `${x},${y}`
		},
		async getUserImages(path = davRootPath) {
			const client = davGetClient()

			const results = await client.getDirectoryContents(`${path}`, {
				details: true,
				data: davGetDefaultPropfind(),
			})
			return results.data.filter((result) => result.basename.endsWith('.jpg') || result.basename.endsWith('.png') || result.type === 'directory')
		},
		async getItemValue(item) {
			if (item.type === 'directory') {
				this.current_filename = item.filename
				if (this.cache.has(item.filename)) {
					this.browser_data = this.cache.get(item.filename)
				} else {
					this.loading = true
					this.getUserImages(item.filename).then((response) => {
						this.browser_data = response
						this.loading = false
						this.cache.set(item.filename, response)
					})
				}
			} else {
				this.imageValue = item.filename.slice(davRootPath.length)
				this.open_popup = false
			}
		},
		async initialLoading() {
			const rootNode = this.getRootNode
			this.current_filename = davRootPath
			if (this.cache.has(rootNode)) {
				this.browser_data = this.cache.get(rootNode)
			} else {
				this.loading = true
				this.getUserImages().then((response) => {
					this.browser_data = response
					this.loading = false
					this.cache.set(rootNode, response)
				})
			}
		},
		goHome() {
			const rootNode = this.getRootNode
			this.current_filename = davRootPath
			this.browser_data = this.cache.get(rootNode)
		},
		goBack() {
			const indexToRemove = this.current_filename.lastIndexOf('/')
			const moveTo = this.current_filename.slice(0, indexToRemove)
			this.current_filename = moveTo
			this.browser_data = this.cache.get(this.current_filename)
		},
		closeModal() {
			this.modal = false
		},
		closeViewer() {
			this.viewer = false
		},
		canEnableButton() {
			if (this.logo.length > 0) {
				return !this.passwd || !this.file.isUploaded || !this.pageValue || !this.positionValue || !this.thereIsImage()
			} else {
				return !this.passwd || !this.file.isUploaded
			}
		},
		thereIsImage() {
			return this.imageValue || this.img_file.url
		},
		signDoc() {
			this.closeModal()
			this.loading = true
			const url = generateUrl('/apps/pdfsignature/request-sign')
			this.cache.clear()
			axios.post(url, {
				path: this.path,
				pkcs12: this.file.url,
				pkcs12_password: this.passwd,
				document: this.pdf,
				logo_position: this.logo.length > 0 ? this.positionValue : null,
				pdf_page: this.logo.length > 0 ? this.pageValue.id : null,
				logo_image: this.getLogoValue(),
			}).then(async (response) => {
				if (response.data === 'ok') {
					const client = davGetClient()
					const results = await client.getDirectoryContents(`${davRootPath}${this.path}`, {
						details: true,
						// Query all required properties for a Node
						data: davGetDefaultPropfind(),
					})

					const nodes = results.data.map((result) => davResultToNode(result))
					const replaceNodeName = this.pdf.replace('.pdf', '-signed.pdf')
					const nodeUpdated = nodes.find((node) => node.basename === replaceNodeName)

					emit('files:node:created', nodeUpdated)
					this.loading = false
					showInfo(t('pdfsignature', 'File {name} has been signed', { name: this.pdf }))
				} else {
					this.loading = false
					switch (response.data.code) {
					case 400:
						showWarning(t('pdfsignature', 'Certificate status is unknown'))
						break
					case 403:
						showWarning(t('pdfsignature', 'Certificate status is revoked'))
						break
					case 401:
						showWarning(t('pdfsignature', 'Id digital password is incorrect'))
						break
					default:
						showError(t('pdfsignature', 'An error occurred with signature API'))
					}
				}

			})
		},
		getUploadedData(file) {
			this.file = file
		},
		getUploadedImg(img) {
			this.img_file = img
		},
		getLogoValue() {
			let logoValue = null
			if (this.logo.length > 0) {
				// check file uploaded data
				if (this.search.length > 0) {
					logoValue = this.img_file.url
				} else {
					logoValue = this.imageValue
				}
			}
			return logoValue

		},
	},
}
</script>
<style scoped>
.modal__content {
	margin: 50px;
	text-align: center;
}

.input-field {
	margin: 12px 0px;
}

.container {
	display: flex;
	gap: 0 12px;
}

.container__form {
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	width: 100%;
	gap: 8px 0;
}

.container__select {
	width: 100%;
}

.container_dialog {
	margin: 8px;
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
	gap: 0.5rem;
	padding: 10px;
	border: 0.2px solid rgba(133, 130, 130, 0.2);
	background-color: rgba(82, 80, 80, 0.5);
	border-radius: 10px;
	height: 170px;
	overflow-y: scroll;
}

.browser_bar {
	display: flex;
	justify-content: space-between;
	padding-top: 0.5rem;
	padding-left: 0.5rem;
	padding-right: 0.5rem;
}

.item_wrapper {
	display: flex;
	align-items: center;
}

.item_wrapper:hover {
	cursor: pointer;
	background-color: rgba(35, 39, 37, 0.452);
}

.empty_item {
	font-size:16px;
	font-weight: 500;
}

</style>
