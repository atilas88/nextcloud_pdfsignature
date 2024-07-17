import SignerForm from './components/SignerForm.vue'
import InfoModal from './components/InfoModal.vue'
import Vue from 'vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import { registerFileAction, FileAction, FileType, Permission } from '@nextcloud/files'
import SignatureSvg from '@mdi/svg/svg/draw.svg'
import ValidateSvg from '@mdi/svg/svg/text-box-check.svg'

import { showInfo, showError } from '@nextcloud/dialogs'

import CustomLoadingIcon from './components/CustomLoadingIcon.vue'

if (!OCA.Pdfsignature) {
	OCA.Pdfsignature = {
		actionIgnoreLists: [
			'trashbin',
			'files.public',
		],
	}
}

const loadingId = 'pdfsignatureLoader'
const loadingEl = document.createElement('div')
loadingEl.id = loadingId
document.body.append(loadingEl)

const Loader = Vue.extend(CustomLoadingIcon)
const LoadingIcon = new Loader({
	propsData: { loading: false },
})

LoadingIcon.$mount(loadingEl)

const modalId = 'pdfsignatureModal'
const modalElement = document.createElement('div')
modalElement.id = modalId
document.body.append(modalElement)

const View = Vue.extend(SignerForm)

OCA.Pdfsignature.PdfsignatureModal = new View().$mount(modalElement)
const modalForm = OCA.Pdfsignature.PdfsignatureModal

const signAction = new FileAction({
	id: 'signatureAction',
	displayName: (nodes) => {
		return t('pdfsignature', 'Sign document')
	},
	enabled(nodes, view) {
		return !OCA.Pdfsignature.actionIgnoreLists.includes(view.id)
			&& nodes.length > 0
			&& !nodes.some(({ permissions }) => (permissions & Permission.READ) === 0)
			&& nodes.every(({ type }) => type === FileType.File)
			&& nodes.every(({ mime }) => mime === 'application/pdf')
	},
	iconSvgInline: () => SignatureSvg,
	async exec(node) {
		modalForm.showModal(node)
		return null
	},
})

registerFileAction(signAction)

const modalvalidId = 'pdfsignatureModal'
const modalValdElement = document.createElement('div')
modalValdElement.id = modalvalidId
document.body.append(modalValdElement)

const View1 = Vue.extend(InfoModal)

OCA.Pdfsignature.PdfsignatureValdModal = new View1().$mount(modalElement)
const modalInfo = OCA.Pdfsignature.PdfsignatureValdModal

const validateAction = new FileAction({
	id: 'validateAction',
	displayName: (nodes) => {
		return t('pdfsignature', 'Validate pdf signature')
	},
	enabled(nodes, view) {
		return !OCA.Pdfsignature.actionIgnoreLists.includes(view.id)
			&& nodes.length > 0
			&& !nodes.some(({ permissions }) => (permissions & Permission.READ) === 0)
			&& nodes.every(({ type }) => type === FileType.File)
			&& nodes.every(({ mime }) => mime === 'application/pdf')
	},
	iconSvgInline: () => ValidateSvg,
	async exec(node) {
		LoadingIcon._props.loading = true
		const url = generateUrl('/apps/pdfsignature/request-validate')
		axios.post(url, {
			document: node.basename,
			path: node.dirname,
		}).then((response) => {
			LoadingIcon._props.loading = false
			if (response.data.ns) {
				showInfo(response.data.ns)
			} else {

				const treeData = {
					name: t('pdfsignature', 'Signature Information'),
					children: [],
				}
				response.data.forEach((item, index) => {
					treeData.children.push({
						name: t('pdfsignature', 'Signature ') + `${index + 1}`,
						children: [
							{
								name: t('pdfsignature', 'Signer :  ') + `${item.signer}`,
							},
							{
								name: t('pdfsignature', 'Certificate info :  ') + `${item.crt}`,
							},
							{
								name: t('pdfsignature', 'Integrity :  ') + `${item.integrity}`,
							},
							{
								name: t('pdfsignature', 'Signature time :  ') + `${item.time}`,
							},
							{
								name: t('pdfsignature', 'Modification :  ') + `${item.modification}`,
							},
							{
								name: t('pdfsignature', 'Summary :  ') + `${item.sumary}`,
							},
						],
					})
				})
				modalInfo.showModal(treeData)
			}

		}).catch(() => {
			showError(t('pdfsignature', 'An error occurred with signature API'))
		})
		return null
	},
})

registerFileAction(validateAction)
